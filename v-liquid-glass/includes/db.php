<?php
/**
 * PDO SQLite bootstrap. Auto-creates the data directory and schema on first run
 * so a fresh production deploy self-heals even though data/ is excluded from
 * both git and the FTP sync (see .github/workflows/deploy.yml).
 */

function blog_db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dataDir = dirname(__DIR__) . '/data';
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }

    $dbPath = $dataDir . '/blog.sqlite';
    $isNew = !file_exists($dbPath);

    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');

    if ($isNew) {
        $schema = file_get_contents(__DIR__ . '/schema.sql');
        $pdo->exec($schema);
    }

    require_once __DIR__ . '/seed-articles.php';
    seed_default_articles($pdo);

    return $pdo;
}
