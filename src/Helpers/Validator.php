<?php
/**
 * Input Validation Helper
 */

class Validator {
    private array $errors = [];

    /**
     * Validate required fields
     */
    public function required(string $field, $value, string $label = ''): self {
        $label = $label ?: $field;
        if (empty(trim($value ?? ''))) {
            $this->errors[$field] = "{$label} is required.";
        }
        return $this;
    }

    /**
     * Validate email format and domain validity
     */
    public function email(string $field, $value, string $label = ''): self {
        $label = $label ?: $field;
        if (!empty($value)) {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field] = "{$label} must be a valid email.";
            } else {
                $parts = explode('@', $value);
                $domain = array_pop($parts);
                // Check if the domain has any mail exchanger (MX) or host (A) records
                if (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A')) {
                    $this->errors[$field] = "{$label} must have a valid and active email domain.";
                }
            }
        }
        return $this;
    }

    /**
     * Validate minimum length
     */
    public function minLength(string $field, $value, int $min, string $label = ''): self {
        $label = $label ?: $field;
        if (!empty($value) && strlen($value) < $min) {
            $this->errors[$field] = "{$label} must be at least {$min} characters.";
        }
        return $this;
    }

    /**
     * Validate maximum length
     */
    public function maxLength(string $field, $value, int $max, string $label = ''): self {
        $label = $label ?: $field;
        if (!empty($value) && strlen($value) > $max) {
            $this->errors[$field] = "{$label} must not exceed {$max} characters.";
        }
        return $this;
    }

    /**
     * Validate file upload
     */
    public function image(string $field, array $file, string $label = ''): self {
        $label = $label ?: $field;
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[$field] = "{$label} upload failed.";
            return $this;
        }

        if (!in_array($file['type'], ALLOWED_IMAGE_TYPES)) {
            $this->errors[$field] = "{$label} must be JPEG, PNG, or WebP.";
        }

        if ($file['size'] > MAX_UPLOAD_SIZE) {
            $this->errors[$field] = "{$label} must be under 10MB.";
        }

        return $this;
    }

    /**
     * Check if validation passed
     */
    public function passes(): bool {
        return empty($this->errors);
    }

    /**
     * Add a custom validation error
     */
    public function addError(string $field, string $message): self {
        $this->errors[$field] = $message;
        return $this;
    }

    /**
     * Get validation errors
     */
    public function errors(): array {
        return $this->errors;
    }

    /**
     * Sanitize string input
     */
    public static function sanitize(string $input): string {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize an array of inputs
     */
    public static function sanitizeAll(array $data): array {
        return array_map(function ($value) {
            return is_string($value) ? self::sanitize($value) : $value;
        }, $data);
    }
}
