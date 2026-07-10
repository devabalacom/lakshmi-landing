<?php
require_once __DIR__ . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/db.php';

blog_db(); // ensure the DB file exists before we try to stream it

$dbPath = dirname(__DIR__) . '/data/blog.sqlite';
if (!is_file($dbPath)) {
    http_response_code(404);
    die('Database file not found.');
}

$filename = 'blog-backup-' . date('Y-m-d-His') . '.sqlite';
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($dbPath));
readfile($dbPath);
