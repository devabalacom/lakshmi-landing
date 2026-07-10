<?php
require_once dirname(__DIR__) . '/includes/queries.php';
require_once dirname(__DIR__) . '/includes/upload.php';
require_once dirname(__DIR__) . '/admin/includes/auth.php'; // every media.php action is admin-only

header('Content-Type: application/json; charset=UTF-8');

$db = blog_db();
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 60)));
        $items = list_media($db, $perPage, ($page - 1) * $perPage);
        foreach ($items as &$m) {
            $m['url'] = '/uploads/blog/' . $m['path'];
        }
        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;

    case 'upload':
        if (!csrf_verify($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
            break;
        }
        if (empty($_FILES['file'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'No file uploaded']);
            break;
        }
        try {
            $media = handle_image_upload($_FILES['file']);
            echo json_encode([
                'success' => true,
                'file' => ['id' => (int) $media['id'], 'url' => '/uploads/blog/' . $media['path']],
            ]);
        } catch (UploadException $e) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'delete':
        csrf_require();
        $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        if (media_is_referenced($db, $id)) {
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => 'Media is referenced by an article']);
            break;
        }
        $media = find_media_by_id($id);
        if ($media) {
            $fullPath = dirname(__DIR__) . '/uploads/blog/' . $media['path'];
            if (is_file($fullPath)) {
                @unlink($fullPath);
            }
            $db->prepare('DELETE FROM media WHERE id = :id')->execute(['id' => $id]);
        }
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action']);
}
