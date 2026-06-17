#!/usr/bin/env php
<?php
/**
 * CardVault — Bulk PDF Card Importer
 *
 * Converts PDF business cards to images and processes them through the
 * same AI scanning pipeline used by the web interface.
 *
 * Usage:
 *   php scripts/bulk_import.php /path/to/pdf/folder [--user-id=1] [--dry-run] [--resume]
 *
 * Each PDF = 1 card:
 *   - 1-page PDF → front side only
 *   - 2-page PDF → front + back
 *
 * Images are compressed and saved identically to manual uploads.
 */

// ── Bootstrap ─────────────────────────────────────────────────────────────────
$_SERVER['HTTP_HOST'] = 'localhost'; // Needed for APP_URL detection
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__. '/../src/Helpers/Response.php';
require_once __DIR__ . '/../src/Helpers/Validator.php';
require_once __DIR__ . '/../src/Services/AIService.php';
require_once __DIR__ . '/../src/Services/ImageService.php';
require_once __DIR__ . '/../src/Models/Contact.php';
require_once __DIR__ . '/../src/Models/Company.php';
require_once __DIR__ . '/../src/Models/ProductService.php';
require_once __DIR__ . '/../src/Models/AuditLog.php';
require_once __DIR__ . '/../src/Models/User.php';

// ── Parse CLI Arguments ───────────────────────────────────────────────────────
$args = parseArgs($argv);
$pdfFolder = $args['folder'] ?? null;
$userId = (int)($args['user-id'] ?? 1);
$dryRun = isset($args['dry-run']);
$resume = isset($args['resume']);
$delay = (int)($args['delay'] ?? 4); // seconds between API calls (rate limiting)

if (!$pdfFolder || !is_dir($pdfFolder)) {
    echo "\n";
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║           CardVault — Bulk PDF Card Importer                ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n\n";
    echo "Usage:\n";
    echo "  php scripts/bulk_import.php /path/to/pdf/folder [options]\n\n";
    echo "Options:\n";
    echo "  --user-id=N    User ID to assign cards to (default: 1)\n";
    echo "  --delay=N      Seconds between API calls (default: 4)\n";
    echo "  --dry-run      Convert PDFs to images only, don't scan/save\n";
    echo "  --resume       Skip PDFs that were already imported\n\n";
    echo "Example:\n";
    echo "  php scripts/bulk_import.php ~/Desktop/cards --user-id=1 --delay=4\n\n";
    exit(1);
}

// ── Validate User ─────────────────────────────────────────────────────────────
$db = Database::getConnection();
$userStmt = $db->prepare("SELECT id, name, department_id FROM users WHERE id = :id AND is_active = 1");
$userStmt->execute([':id' => $userId]);
$user = $userStmt->fetch();

if (!$user) {
    echo "❌ User ID {$userId} not found or inactive.\n";
    echo "   Available users:\n";
    $users = $db->query("SELECT id, name, email FROM users WHERE is_active = 1")->fetchAll();
    foreach ($users as $u) {
        echo "   - ID {$u['id']}: {$u['name']} ({$u['email']})\n";
    }
    exit(1);
}

// ── Gather PDFs ───────────────────────────────────────────────────────────────
$pdfFiles = glob(rtrim($pdfFolder, '/') . '/*.pdf');
if (empty($pdfFiles)) {
    $pdfFiles = glob(rtrim($pdfFolder, '/') . '/*.PDF');
}
sort($pdfFiles);
$totalPdfs = count($pdfFiles);

if ($totalPdfs === 0) {
    echo "❌ No PDF files found in: {$pdfFolder}\n";
    exit(1);
}

// ── Progress tracking file (for --resume) ─────────────────────────────────────
$progressFile = __DIR__ . '/.bulk_import_progress.json';
$progress = [];
if ($resume && file_exists($progressFile)) {
    $progress = json_decode(file_get_contents($progressFile), true) ?: [];
}

// ── Print banner ──────────────────────────────────────────────────────────────
echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║           CardVault — Bulk PDF Card Importer                ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";
echo "📁 Folder:     {$pdfFolder}\n";
echo "📄 PDFs found: {$totalPdfs}\n";
echo "👤 Import as:  {$user['name']} (ID: {$user['id']})\n";
echo "⏱️  Delay:      {$delay}s between cards\n";
if ($dryRun) echo "🧪 DRY RUN:    Will convert images only, no AI/DB\n";
if ($resume)  echo "🔄 RESUME:     Skipping previously imported cards\n";
echo str_repeat("─", 60) . "\n\n";

// ── Estimate time ─────────────────────────────────────────────────────────────
$estimatedMinutes = ceil($totalPdfs * ($delay + 2) / 60); // ~2s per API call + delay
echo "⏳ Estimated time: ~{$estimatedMinutes} minutes\n\n";

// ── Temp directory for PDF conversions ────────────────────────────────────────
$tmpDir = sys_get_temp_dir() . '/cardvault_bulk_' . getmypid();
if (!is_dir($tmpDir)) {
    mkdir($tmpDir, 0755, true);
}

// ── Process each PDF ──────────────────────────────────────────────────────────
$stats = ['success' => 0, 'skipped' => 0, 'duplicate' => 0, 'failed' => 0];
$startTime = time();

foreach ($pdfFiles as $index => $pdfPath) {
    $pdfBasename = basename($pdfPath);
    $num = $index + 1;
    $pct = round(($num / $totalPdfs) * 100);

    echo "[{$num}/{$totalPdfs}] ({$pct}%) {$pdfBasename}";

    // ── Resume check ──────────────────────────────────────────────────────
    if ($resume && in_array($pdfBasename, $progress)) {
        echo " ⏭️  Already imported\n";
        $stats['skipped']++;
        continue;
    }

    // ── Step 1: Get page count ────────────────────────────────────────────
    $pageCount = getPageCount($pdfPath);
    echo " ({$pageCount} page" . ($pageCount > 1 ? 's' : '') . ")";

    // ── Step 2: Convert PDF to images ─────────────────────────────────────
    $images = convertPdfToImages($pdfPath, $tmpDir);
    if (empty($images)) {
        echo " ❌ Failed to convert\n";
        $stats['failed']++;
        continue;
    }

    // ── Step 3: Compress and save images to uploads directory ─────────────
    $frontPath = saveCardImage($images[0], 'front');
    $backPath = isset($images[1]) ? saveCardImage($images[1], 'back') : null;

    if (!$frontPath) {
        echo " ❌ Failed to save image\n";
        $stats['failed']++;
        continue;
    }

    if ($dryRun) {
        echo " ✅ Images saved (dry-run, no AI scan)\n";
        $stats['success']++;
        $progress[] = $pdfBasename;
        continue;
    }

    // ── Step 4: AI extraction ─────────────────────────────────────────────
    $frontAbsPath = ImageService::getAbsolutePath($frontPath);
    $backAbsPath = $backPath ? ImageService::getAbsolutePath($backPath) : null;

    echo " → Scanning...";
    $extracted = AIService::extractCardData($frontAbsPath, $backAbsPath);

    if (isset($extracted['error'])) {
        echo " ❌ AI error: {$extracted['error']}\n";
        $stats['failed']++;
        // Clean up images on failure
        @unlink($frontAbsPath);
        if ($backAbsPath) @unlink($backAbsPath);
        continue;
    }

    // ── Step 5: Save to database ──────────────────────────────────────────
    $result = saveCardToDatabase($extracted, $frontPath, $backPath, $user, $db);

    if ($result === 'duplicate') {
        echo " ⚠️  Duplicate: " . ($extracted['person_name'] ?? 'Unknown') . "\n";
        $stats['duplicate']++;
        // Clean up duplicate images
        @unlink($frontAbsPath);
        if ($backAbsPath) @unlink($backAbsPath);
    } elseif ($result === false) {
        echo " ❌ DB save failed\n";
        $stats['failed']++;
    } else {
        $name = $extracted['person_name'] ?? 'Unknown';
        $company = $extracted['company_name'] ?? '';
        echo " ✅ {$name}" . ($company ? " @ {$company}" : "") . "\n";
        $stats['success']++;
    }

    $progress[] = $pdfBasename;

    // Save progress after each card
    file_put_contents($progressFile, json_encode($progress));

    // ── Rate limit delay ──────────────────────────────────────────────────
    if ($index < $totalPdfs - 1) {
        sleep($delay);
    }
}

// ── Cleanup temp files ────────────────────────────────────────────────────────
array_map('unlink', glob("{$tmpDir}/*"));
@rmdir($tmpDir);

// ── Print summary ─────────────────────────────────────────────────────────────
$elapsed = time() - $startTime;
$minutes = floor($elapsed / 60);
$seconds = $elapsed % 60;

echo "\n" . str_repeat("═", 60) . "\n";
echo "📊 IMPORT COMPLETE\n";
echo str_repeat("─", 60) . "\n";
echo "✅ Successfully imported: {$stats['success']}\n";
echo "⚠️  Duplicates (skipped):  {$stats['duplicate']}\n";
echo "⏭️  Resumed (skipped):     {$stats['skipped']}\n";
echo "❌ Failed:                 {$stats['failed']}\n";
echo "⏱️  Total time:             {$minutes}m {$seconds}s\n";
echo str_repeat("═", 60) . "\n\n";

// Clean up progress file on complete success
if ($stats['failed'] === 0 && !$resume) {
    @unlink($progressFile);
}

// ══════════════════════════════════════════════════════════════════════════════
// HELPER FUNCTIONS
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Get the number of pages in a PDF
 */
function getPageCount(string $pdfPath): int {
    $output = shell_exec("pdfinfo " . escapeshellarg($pdfPath) . " 2>/dev/null | grep 'Pages:'");
    if ($output && preg_match('/Pages:\s+(\d+)/', $output, $m)) {
        return (int)$m[1];
    }
    // Fallback: try pdftoppm and count output files
    return 1;
}

/**
 * Convert a PDF to PNG images using pdftoppm
 * Returns array of absolute image paths [front, back?]
 */
function convertPdfToImages(string $pdfPath, string $tmpDir): array {
    $basename = pathinfo($pdfPath, PATHINFO_FILENAME);
    $outputPrefix = $tmpDir . '/' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $basename);

    // Convert at 300 DPI for good quality, output as PNG
    $cmd = sprintf(
        'pdftoppm -png -r 300 %s %s 2>/dev/null',
        escapeshellarg($pdfPath),
        escapeshellarg($outputPrefix)
    );
    exec($cmd, $output, $retCode);

    // pdftoppm creates files like prefix-1.png, prefix-2.png, etc.
    $images = glob($outputPrefix . '-*.png');
    sort($images); // Ensure page order

    // Sometimes pdftoppm uses different naming (e.g., prefix-01.png)
    if (empty($images)) {
        $images = glob($outputPrefix . '*.png');
        sort($images);
    }

    // Return only first 2 pages (front + back)
    return array_slice($images, 0, 2);
}

/**
 * Save a converted image through the same compression pipeline as manual uploads.
 * Returns the relative path (e.g., "uploads/cards/2026/06/front_xxx.jpg")
 */
function saveCardImage(string $sourcePath, string $prefix): ?string {
    $filename = $prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.jpg';

    $dateDir = date('Y/m');
    $fullDir = UPLOAD_PATH . '/' . $dateDir;
    if (!is_dir($fullDir)) {
        mkdir($fullDir, 0755, true);
    }

    $destination = $fullDir . '/' . $filename;

    // Use GD to compress and resize (same as ImageService)
    $srcImage = @imagecreatefrompng($sourcePath);
    if (!$srcImage) {
        // Try loading as generic format via ImageMagick conversion
        $tmpJpg = $sourcePath . '.jpg';
        exec(sprintf('convert %s -quality 85 %s 2>/dev/null',
            escapeshellarg($sourcePath), escapeshellarg($tmpJpg)));
        if (file_exists($tmpJpg)) {
            $srcImage = @imagecreatefromjpeg($tmpJpg);
            @unlink($tmpJpg);
        }
    }

    if (!$srcImage) {
        // Last resort: just copy the file
        if (copy($sourcePath, $destination)) {
            return 'uploads/cards/' . $dateDir . '/' . $filename;
        }
        return null;
    }

    $width = imagesx($srcImage);
    $height = imagesy($srcImage);
    $maxDim = 1600;

    $newWidth = $width;
    $newHeight = $height;

    if ($width > $maxDim || $height > $maxDim) {
        if ($width > $height) {
            $newWidth = $maxDim;
            $newHeight = (int)($height * ($maxDim / $width));
        } else {
            $newHeight = $maxDim;
            $newWidth = (int)($width * ($maxDim / $height));
        }
    }

    $dstImage = imagecreatetruecolor($newWidth, $newHeight);
    $white = imagecolorallocate($dstImage, 255, 255, 255);
    imagefill($dstImage, 0, 0, $white);
    imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    $result = imagejpeg($dstImage, $destination, 80);
    imagedestroy($srcImage);
    imagedestroy($dstImage);

    if ($result) {
        return 'uploads/cards/' . $dateDir . '/' . $filename;
    }
    return null;
}

/**
 * Save extracted card data to the database (mirrors CardController::save logic)
 * Returns contact ID on success, 'duplicate' string, or false on failure.
 */
function saveCardToDatabase(array $data, string $frontPath, ?string $backPath, array $user, PDO $db) {
    $normalizePhone = fn($p) => preg_replace('/[\s\-(). ]+/', '', $p ?? '');

    try {
        $db->beginTransaction();

        // 1. Create or find company
        $companyId = CompanyModel::findOrCreate([
            'name'       => substr($data['company_name'] ?? 'Unknown Company', 0, 255),
            'website'    => substr($data['company_website'] ?? '', 0, 500),
            'industry'   => substr($data['company_industry'] ?? '', 0, 150),
            'address'    => $data['address'] ?? '',
            'city'       => substr($data['city'] ?? '', 0, 100),
            'state'      => substr($data['state'] ?? '', 0, 100),
            'pincode'    => substr($data['pincode'] ?? '', 0, 10),
            'country'    => substr($data['country'] ?? 'India', 0, 100),
            'gst_number' => substr($data['gst_number'] ?? '', 0, 20),
            'notes'      => $data['notes'] ?? ''
        ]);

        // 2. Check for duplicate
        $personName = substr($data['person_name'] ?? '', 0, 200);
        if (!empty($personName)) {
            $duplicate = Contact::findDuplicate($personName, $companyId);
            if ($duplicate) {
                $db->rollBack();
                return 'duplicate';
            }
        }

        // 3. Create contact
        $contactId = Contact::create([
            'company_id'            => $companyId,
            'name'                  => $personName,
            'designation'           => substr($data['designation'] ?? '', 0, 200),
            'department_in_company' => substr($data['department'] ?? '', 0, 150),
            'phone_primary'         => substr($normalizePhone($data['phone_primary'] ?? ''), 0, 20),
            'phone_secondary'       => substr($normalizePhone($data['phone_secondary'] ?? ''), 0, 20),
            'email_primary'         => substr(strtolower(trim($data['email_primary'] ?? '')), 0, 255),
            'email_secondary'       => substr(strtolower(trim($data['email_secondary'] ?? '')), 0, 255),
            'linkedin_url'          => substr($data['linkedin_url'] ?? '', 0, 500),
            'card_front_image'      => $frontPath,
            'card_back_image'       => $backPath ?? '',
            'added_by_user_id'      => $user['id'],
            'added_by_department_id'=> $user['department_id'],
            'ai_confidence_score'   => $data['confidence_score'] ?? null,
            'is_verified'           => 0
        ]);

        // 4. Process products/services
        $products = $data['products_services'] ?? [];
        if (is_string($products)) {
            $products = explode(',', $products);
        }
        foreach ($products as $productName) {
            $productName = trim($productName);
            if (!empty($productName)) {
                $productId = ProductServiceModel::findOrCreate($productName);
                ProductServiceModel::linkToCompany($companyId, $productId);
            }
        }

        // 5. Process tags
        $tags = $data['tags'] ?? [];
        if (is_string($tags)) {
            $tags = explode(',', $tags);
        }
        foreach ($tags as $tagName) {
            $tagName = strtolower(trim($tagName));
            if (!empty($tagName)) {
                $stmt = $db->prepare("INSERT IGNORE INTO tags (name) VALUES (:name)");
                $stmt->execute([':name' => $tagName]);
                $stmt = $db->prepare("SELECT id FROM tags WHERE name = :name");
                $stmt->execute([':name' => $tagName]);
                $tagId = $stmt->fetchColumn();
                if ($tagId) {
                    $stmt = $db->prepare("INSERT IGNORE INTO contact_tags (contact_id, tag_id) VALUES (:cid, :tid)");
                    $stmt->execute([':cid' => $contactId, ':tid' => $tagId]);
                }
            }
        }

        $db->commit();

        // Audit log
        AuditLog::log('create', 'contact', $contactId, [], [
            'name' => $personName,
            'source' => 'bulk_import'
        ]);

        return $contactId;

    } catch (Exception $e) {
        $db->rollBack();
        error_log("Bulk import DB error for '{$data['person_name']}': " . $e->getMessage());
        return false;
    }
}

/**
 * Parse CLI arguments into an associative array
 */
function parseArgs(array $argv): array {
    $result = [];
    foreach ($argv as $i => $arg) {
        if ($i === 0) continue; // script name
        if (str_starts_with($arg, '--')) {
            $parts = explode('=', ltrim($arg, '-'), 2);
            $result[$parts[0]] = $parts[1] ?? true;
        } elseif (!isset($result['folder'])) {
            $result['folder'] = $arg;
        }
    }
    return $result;
}
