<?php
require_once dirname(__DIR__) . '/includes/queries.php';

header('Content-Type: application/json; charset=UTF-8');

$db = blog_db();
$action = $_GET['action'] ?? ($_SERVER['REQUEST_METHOD'] === 'GET' ? 'list' : '');

function api_require_admin(): void
{
    require_once dirname(__DIR__) . '/admin/includes/auth.php';
}

function api_json_body(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $_POST;
}

function article_public_fields(array $a): array
{
    return [
        'id' => (int) $a['id'],
        'title' => $a['title'],
        'slug' => $a['slug'],
        'url' => article_public_url($a),
        'excerpt' => $a['excerpt'],
        'content_html' => $a['content_html'],
        'category' => $a['category_name'] ?? null,
        'author' => $a['author'],
        'cover_image' => $a['cover_path'] ? '/uploads/blog/' . $a['cover_path'] : null,
        'reading_time_minutes' => (int) $a['reading_time_minutes'],
        'published_at' => $a['published_at'] ?? $a['publish_at'],
        'updated_at' => $a['updated_at'],
    ];
}

switch ($action) {
    case 'list':
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(50, max(1, (int) ($_GET['per_page'] ?? 12)));
        $items = get_published_articles($db, $perPage, ($page - 1) * $perPage);
        echo json_encode([
            'items' => array_map('article_public_fields', $items),
            'page' => $page,
            'total' => count_published_articles($db),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;

    case 'get':
        $slug = $_GET['slug'] ?? '';
        $article = get_public_article_by_slug($db, $slug);
        if (!$article || $article['status'] !== 'published') {
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
            break;
        }
        echo json_encode(article_public_fields($article), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;

    case 'categories':
        echo json_encode(get_categories($db), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;

    case 'create':
        api_require_admin();
        csrf_require();
        $input = api_json_body();
        $id = create_article($db, $input);
        echo json_encode(['success' => true, 'id' => $id]);
        break;

    case 'update':
        api_require_admin();
        csrf_require();
        $id = (int) ($_GET['id'] ?? 0);
        update_article($db, $id, api_json_body());
        echo json_encode(['success' => true]);
        break;

    case 'delete':
        api_require_admin();
        csrf_require();
        delete_article($db, (int) ($_GET['id'] ?? 0));
        echo json_encode(['success' => true]);
        break;

    case 'publish':
        api_require_admin();
        csrf_require();
        set_article_status($db, (int) ($_GET['id'] ?? 0), 'published');
        echo json_encode(['success' => true]);
        break;

    case 'unpublish':
        api_require_admin();
        csrf_require();
        set_article_status($db, (int) ($_GET['id'] ?? 0), 'draft');
        echo json_encode(['success' => true]);
        break;

    case 'duplicate':
        api_require_admin();
        csrf_require();
        $newId = duplicate_article($db, (int) ($_GET['id'] ?? 0));
        echo json_encode(['success' => true, 'id' => $newId]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action']);
}
