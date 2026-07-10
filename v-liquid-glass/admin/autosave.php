<?php
require_once __DIR__ . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/queries.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
$contentJson = $_POST['content_json'] ?? null;

if ($id <= 0 || $contentJson === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing id or content_json']);
    exit;
}

$db = blog_db();
$article = get_article_by_id($db, $id);
if (!$article) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Article not found']);
    exit;
}

// Autosave only touches content — never status, slug, or SEO fields.
$contentHtml = render_editorjs_to_html($contentJson);
$db->prepare('UPDATE articles SET content_json = :j, content_html = :h, updated_at = CURRENT_TIMESTAMP WHERE id = :id')
    ->execute(['j' => $contentJson, 'h' => $contentHtml, 'id' => $id]);

echo json_encode(['success' => true, 'saved_at' => date('c')]);
