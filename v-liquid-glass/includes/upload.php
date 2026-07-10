<?php
require_once __DIR__ . '/db.php';

const UPLOAD_MAX_BYTES = 10 * 1024 * 1024; // 10 MB
const UPLOAD_MAX_DIMENSION = 1600; // px, longest side
const UPLOAD_ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

class UploadException extends RuntimeException {}

/**
 * Validates and stores an uploaded image from a $_FILES-style array.
 * Three independent gates: real MIME sniff (finfo), extension allowlist,
 * and a successful getimagesize() read. The image is always re-encoded via
 * GD (never just moved as-is) — that re-encode is the primary defense
 * against image-polyglot payloads smuggled past the MIME/extension checks.
 *
 * Returns the inserted `media` row.
 */
function handle_image_upload(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new UploadException('Ошибка загрузки файла (код ' . ($file['error'] ?? 'unknown') . ').');
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        throw new UploadException('Недопустимая загрузка.');
    }
    if ($file['size'] > UPLOAD_MAX_BYTES) {
        throw new UploadException('Файл превышает лимит 10 МБ.');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        throw new UploadException('Недопустимое расширение файла.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $realMime = finfo_file($finfo, $file['tmp_name']);
    if (!in_array($realMime, UPLOAD_ALLOWED_MIMES, true)) {
        throw new UploadException('Недопустимый тип файла: ' . $realMime);
    }

    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        throw new UploadException('Файл повреждён или не является изображением.');
    }
    [$origWidth, $origHeight] = $imageInfo;

    $source = match ($realMime) {
        'image/jpeg' => @imagecreatefromjpeg($file['tmp_name']),
        'image/png' => @imagecreatefrompng($file['tmp_name']),
        'image/webp' => @imagecreatefromwebp($file['tmp_name']),
        default => false,
    };
    if ($source === false) {
        throw new UploadException('Не удалось обработать изображение.');
    }

    $scale = min(1, UPLOAD_MAX_DIMENSION / max($origWidth, $origHeight));
    $targetWidth = max(1, (int) round($origWidth * $scale));
    $targetHeight = max(1, (int) round($origHeight * $scale));

    if ($scale < 1) {
        $resized = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $origWidth, $origHeight);
    } else {
        $resized = $source;
    }

    $dataDir = dirname(__DIR__) . '/uploads/blog';
    $subdir = date('Y/m');
    $targetDir = $dataDir . '/' . $subdir;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $filename = bin2hex(random_bytes(8)) . '.webp';
    $relativePath = $subdir . '/' . $filename;
    $fullPath = $targetDir . '/' . $filename;

    if (!imagewebp($resized, $fullPath, 82)) {
        throw new UploadException('Не удалось сохранить изображение.');
    }

    $db = blog_db();
    $stmt = $db->prepare(
        'INSERT INTO media (filename, original_filename, path, mime_type, file_size, width, height, alt)
         VALUES (:filename, :original, :path, :mime, :size, :width, :height, :alt)'
    );
    $stmt->execute([
        'filename' => $filename,
        'original' => basename($file['name']),
        'path' => $relativePath,
        'mime' => 'image/webp',
        'size' => filesize($fullPath),
        'width' => $targetWidth,
        'height' => $targetHeight,
        'alt' => '',
    ]);

    $id = (int) $db->lastInsertId();
    $stmt = $db->prepare('SELECT * FROM media WHERE id = :id');
    $stmt->execute(['id' => $id]);
    return $stmt->fetch();
}
