<?php
/**
 * AI Service — Google Gemini integration
 *
 * Uses gemini-2.0-flash-lite for both vision (card scanning) and text (search expansion).
 */

class AIService {

    private const API_BASE   = 'https://generativelanguage.googleapis.com/v1beta/models';
    private const MODEL      = 'gemini-3.1-flash-lite';
    private const TIMEOUT    = 60;

    // ─────────────────────────────────────────────────────────────────────────
    // Card Extraction (Vision)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Extract data from a business card image using Gemini vision.
     */
    public static function extractCardData(string $frontImagePath, ?string $backImagePath = null): array {

        $prompt = <<<PROMPT
You are an expert at extracting information from business/visiting cards.
Analyze the provided business card image(s) carefully and extract ALL visible information.

Return a JSON object with EXACTLY these fields:
{
    "person_name": "Full name of the person on the card",
    "designation": "Job title or designation",
    "department": "Department mentioned (if any)",
    "company_name": "Company or organization name",
    "company_website": "Website URL if visible",
    "company_industry": "Industry or business type (infer from company name, tagline, or products mentioned)",
    "phone_primary": "Primary phone number with country code",
    "phone_secondary": "Secondary phone number if available",
    "email_primary": "Primary email address",
    "email_secondary": "Secondary email address if available",
    "address": "Full street address",
    "city": "City name",
    "state": "State or province",
    "pincode": "PIN/ZIP code",
    "country": "Country (default to India if not specified)",
    "gst_number": "GST number if visible",
    "linkedin_url": "LinkedIn profile URL if visible",
    "products_services": ["Array of products, services, or solutions mentioned or inferred from the card. Include the company tagline items, specializations, and any products/services visible. Be thorough."],
    "tags": ["Relevant category tags for easy searching, e.g., 'electronics', 'IT services', 'construction'"],
    "notes": "Any additional information visible on the card (taglines, certifications, etc.)",
    "confidence_score": 0.95
}

IMPORTANT RULES:
- If a field is not found on the card, use an empty string "" or empty array []
- For products_services, be THOROUGH. Infer from company name, tagline, or any text on the card.
- Phone numbers should include country code if visible (e.g., +91)
- confidence_score: 0.0 to 1.0 based on image clarity and how confident you are
- Return ONLY valid JSON. No markdown formatting, no code fences, no extra text.
PROMPT;

        // Build parts array
        $parts = [['text' => $prompt]];

        // Add front image
        $frontPart = self::imageToInlinePart($frontImagePath);
        if (!$frontPart) {
            return ['error' => 'Could not read the front image file.'];
        }
        $parts[] = $frontPart;

        // Append back image if provided
        if ($backImagePath && file_exists($backImagePath)) {
            $parts[] = ['text' => 'This is the BACK side of the same visiting card:'];
            $backPart = self::imageToInlinePart($backImagePath);
            if ($backPart) {
                $parts[] = $backPart;
            }
        }

        $payload = json_encode([
            'contents' => [['parts' => $parts]],
            'generationConfig' => [
                'temperature'     => 0.1,
                'maxOutputTokens' => 1200,
                'responseMimeType' => 'application/json',
            ],
        ]);

        [$httpCode, $response, $curlError] = self::callApi($payload);

        if ($curlError) {
            error_log("Gemini cURL error: {$curlError}");
            return ['error' => 'Could not reach the AI service. Check your internet connection.'];
        }

        if ($httpCode === 200) {
            $data      = json_decode($response, true);
            $text      = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $extracted = json_decode($text, true);

            if (!$extracted) {
                error_log("Gemini JSON parse failed: " . $text);
                return ['error' => 'Failed to parse AI response. Please try again.'];
            }

            return $extracted;
        }

        // Handle known error codes
        error_log("Gemini HTTP {$httpCode}: " . $response);
        return match ($httpCode) {
            400     => ['error' => 'The image could not be processed by the AI. Try uploading a clearer photo.'],
            401, 403 => ['error' => 'Invalid Gemini API key. Please update your GEMINI_API_KEY in the .env file.'],
            429     => ['error' => 'Gemini rate limit reached. Please wait a moment and try again.'],
            default => ['error' => "Unexpected AI service error (HTTP {$httpCode}). Please try again."],
        };
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Search Query Expansion (Text only)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * AI-powered search query expansion with intent detection.
     * Results are cached in MySQL — each unique query only hits the API once.
     *
     * @return array{include: string[], exclude: string[]}
     */
    public static function expandSearchQuery(string $query): array {
        $query   = strtolower(trim($query));
        $default = ['include' => [$query], 'exclude' => []];

        if (strlen($query) < 2) {
            return $default;
        }

        $db = Database::getConnection();

        // ── Gather what actually exists in our database ─────────────────────
        $products   = $db->query("SELECT DISTINCT name FROM products_services ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
        $industries = $db->query("SELECT DISTINCT industry FROM companies WHERE industry != '' AND industry IS NOT NULL ORDER BY industry")->fetchAll(PDO::FETCH_COLUMN);
        $tags       = $db->query("SELECT DISTINCT name FROM tags ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
        $designations = $db->query("SELECT DISTINCT designation FROM contacts WHERE designation != '' AND designation IS NOT NULL AND is_deleted = 0 ORDER BY designation LIMIT 200")->fetchAll(PDO::FETCH_COLUMN);

        $catalogProducts   = implode(', ', array_slice($products, 0, 300));
        $catalogIndustries = implode(', ', array_slice($industries, 0, 100));
        $catalogTags       = implode(', ', array_slice($tags, 0, 200));

        // ── Build a content-aware cache key ────────────────────────────────
        $dbContentHash = md5($catalogProducts . $catalogIndustries . $catalogTags);
        $hash = hash('sha256', $query . ':' . $dbContentHash);

        // Check cache first
        $stmt = $db->prepare("SELECT expanded_terms FROM search_cache WHERE query_hash = :hash LIMIT 1");
        $stmt->execute([':hash' => $hash]);
        $cached = $stmt->fetchColumn();

        if ($cached) {
            $db->prepare("UPDATE search_cache SET hit_count = hit_count + 1 WHERE query_hash = :hash")
               ->execute([':hash' => $hash]);
            $result = json_decode($cached, true);
            if ($result && isset($result['include'])) {
                return $result;
            }
            return $default;
        }

        // ── Call Gemini with database context ──────────────────────────────
        $prompt = <<<PROMPT
You are a STRICT search assistant for a business card management system.
The user wants to find contacts/companies that can help with a specific need.

User's search query: "{$query}"

Here is what ACTUALLY EXISTS in our database:

PRODUCTS & SERVICES: {$catalogProducts}

INDUSTRIES: {$catalogIndustries}

TAGS: {$catalogTags}

Your task: Map the user's intent to the EXISTING items above, but be STRICT about relevance.

1. "primary": 3-6 terms that are DIRECTLY and STRONGLY relevant to the user's query.
   - These are terms where someone offering this product/service would DEFINITELY help the user.
   - For "refrigerator" → "refrigerator", "home appliances", "electronics", "consumer electronics"
   - For "ui design" → "ui design", "graphic design", "web design", "branding"
   - ONLY include terms from the database lists above that have a CLEAR, DIRECT connection.

2. "secondary": 2-4 terms that are SOMEWHAT related but less directly relevant.
   - These catch edge cases but should not dominate results.
   - For "refrigerator" → "kitchen appliances", "electrical"
   - For "ui design" → "digital marketing", "printing"

3. "exclude": 3-8 terms that look textually similar but are UNRELATED to the user's intent.
   - Be AGGRESSIVE with exclusions. If something is in a different domain, exclude it.
   - For "refrigerator" → "power solutions", "data center", "building infrastructure", "industrial batteries", "security", "automation", "solar", "UPS"
   - For "tv" → "cctv", "surveillance", "security camera"
   - Think: would the user searching for "{$query}" EVER want this? If not, EXCLUDE it.

CRITICAL RULES:
- A datacenter company does NOT sell refrigerators. EXCLUDE them.
- Only pick terms that ACTUALLY EXIST in the database lists above.

Return ONLY valid JSON. No markdown, no explanation.
PROMPT;

        $payload = json_encode([
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'temperature'     => 0.15,
                'maxOutputTokens' => 500,
                'responseMimeType' => 'application/json',
            ],
        ]);

        [$httpCode, $response] = self::callApi($payload, 15);

        if ($httpCode === 200) {
            $data   = json_decode($response, true);
            $text   = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $parsed = json_decode($text, true);

            if (is_array($parsed) && (isset($parsed['primary']) || isset($parsed['include']))) {
                $primary   = array_map('strtolower', $parsed['primary'] ?? $parsed['include'] ?? []);
                $secondary = array_map('strtolower', $parsed['secondary'] ?? []);

                $result = [
                    'primary'   => array_values(array_unique(array_merge([$query], $primary))),
                    'secondary' => array_values(array_unique($secondary)),
                    'include'   => array_values(array_unique(array_merge([$query], $primary, $secondary))),
                    'exclude'   => array_map('strtolower', $parsed['exclude'] ?? [])
                ];

                // Cache the result
                $stmt = $db->prepare("
                    INSERT INTO search_cache (query_hash, original_query, expanded_terms)
                    VALUES (:hash, :query, :terms)
                    ON DUPLICATE KEY UPDATE expanded_terms = VALUES(expanded_terms), hit_count = hit_count + 1
                ");
                $stmt->execute([
                    ':hash'  => $hash,
                    ':query' => $query,
                    ':terms' => json_encode($result)
                ]);

                return $result;
            }
        }

        error_log("Gemini search expansion failed for '{$query}' (HTTP {$httpCode})");
        return $default;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Convert an image file to a Gemini inline_data part (base64).
     */
    private static function imageToInlinePart(string $path): ?array {
        if (!file_exists($path)) return null;
        $mime   = mime_content_type($path);
        $base64 = base64_encode(file_get_contents($path));
        return [
            'inline_data' => [
                'mime_type' => $mime,
                'data'      => $base64,
            ]
        ];
    }

    /**
     * Make a POST request to the Gemini API.
     * Returns [httpCode, responseBody, curlError].
     */
    private static function callApi(string $payload, int $timeout = self::TIMEOUT): array {
        $apiKey = GEMINI_API_KEY;
        $url    = self::API_BASE . '/' . self::MODEL . ':generateContent?key=' . $apiKey;
        $ch     = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        return [$httpCode, $response, $curlError];
    }
}
