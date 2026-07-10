<?php
require_once dirname(__DIR__) . '/includes/queries.php';

header('Content-Type: application/json; charset=UTF-8');

$db = blog_db();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(get_categories($db), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

require_once dirname(__DIR__) . '/admin/includes/auth.php';
csrf_require();

$name = trim($_POST['name'] ?? '');
if ($name === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Name is required']);
    exit;
}
$id = find_or_create_category($db, $name);
echo json_encode(['success' => true, 'id' => $id]);
