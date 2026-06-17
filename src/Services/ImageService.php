<?php
/**
 * Image Service — Handle file uploads securely
 */

class ImageService {
    /**
     * Upload and process a card image
     * Returns the relative path to the saved image
     */
    public static function upload(array $file, string $prefix = 'front'): ?string {
        // Validate file
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        // Validate MIME type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
            return null;
        }

        // Validate file size
        if ($file['size'] > MAX_UPLOAD_SIZE) {
            return null;
        }

        // We will output all compressed images as JPEGs for maximum compression and compatibility
        $filename = $prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.jpg';
        
        // Create date-based directory
        $dateDir = date('Y/m');
        $fullDir = UPLOAD_PATH . '/' . $dateDir;
        if (!is_dir($fullDir)) {
            mkdir($fullDir, 0755, true);
        }

        $destination = $fullDir . '/' . $filename;
        
        // Try to compress and save image
        $compressed = self::compressAndResizeImage($file['tmp_name'], $destination, $mimeType);
        
        // Fallback to standard move upload if compression fails
        if (!$compressed) {
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                return 'uploads/cards/' . $dateDir . '/' . $filename;
            }
            return null;
        }

        return 'uploads/cards/' . $dateDir . '/' . $filename;
    }

    /**
     * Compress and resize image using GD library
     */
    private static function compressAndResizeImage(string $sourcePath, string $destinationPath, string $mimeType, int $maxDimension = 1600, int $quality = 80): bool {
        // Load original image based on MIME type
        switch ($mimeType) {
            case 'image/jpeg':
                $srcImage = @imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $srcImage = @imagecreatefrompng($sourcePath);
                break;
            case 'image/webp':
                $srcImage = @imagecreatefromwebp($sourcePath);
                break;
            default:
                return false;
        }

        if (!$srcImage) {
            return false;
        }

        // Get dimensions
        $width = imagesx($srcImage);
        $height = imagesy($srcImage);

        if ($width <= 0 || $height <= 0) {
            imagedestroy($srcImage);
            return false;
        }

        // Calculate shrunken dimensions keeping aspect ratio
        $newWidth = $width;
        $newHeight = $height;

        if ($width > $maxDimension || $height > $maxDimension) {
            if ($width > $height) {
                $newWidth = $maxDimension;
                $newHeight = (int)($height * ($maxDimension / $width));
            } else {
                $newHeight = $maxDimension;
                $newWidth = (int)($width * ($maxDimension / $height));
            }
        }

        // Create new shrunken image canvas
        $dstImage = imagecreatetruecolor($newWidth, $newHeight);
        if (!$dstImage) {
            imagedestroy($srcImage);
            return false;
        }

        // Fill background with white (useful for transparent PNG/WebP convert to JPEG)
        $white = imagecolorallocate($dstImage, 255, 255, 255);
        imagefill($dstImage, 0, 0, $white);

        // Copy and resample image onto canvas
        imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        // Save shrunken image as JPEG
        $result = imagejpeg($dstImage, $destinationPath, $quality);

        // Free memory
        imagedestroy($srcImage);
        imagedestroy($dstImage);

        return $result;
    }

    /**
     * Get absolute path from relative path
     */
    public static function getAbsolutePath(string $relativePath): string {
        return PUBLIC_PATH . '/' . $relativePath;
    }
}
