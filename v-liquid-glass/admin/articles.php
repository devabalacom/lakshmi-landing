<?php
require_once __DIR__ . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/queries.php';

$db = blog_db();

// --- Row actions (publish/unpublish/duplicate/delete) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        try {
            switch ($action) {
                case 'publish':
                    set_article_status($db, $id, 'published');
                    break;
                case 'unpublish':
                    set_article_status($db, $id, 'draft');
                    break;
                case 'hide':
                    set_article_status($db, $id, 'hidden');
                    break;
                case 'duplicate':
                    duplicate_article($db, $id);
                    break;
                case 'delete':
                    delete_article($db, $id);
                    break;
            }
        } catch (Throwable $e) {
            // fall through — redirect still happens, list simply won't reflect a failed action
        }
    }
    header('Location: /admin/articles.php?' . http_build_query(array_filter([
        'q' => $_GET['q'] ?? null, 'status' => $_GET['status'] ?? null,
        'category_id' => $_GET['category_id'] ?? null, 'sort' => $_GET['sort'] ?? null,
        'dir' => $_GET['dir'] ?? null, 'page' => $_GET['page'] ?? null,
    ])));
    exit;
}

$filters = [
    'q' => trim($_GET['q'] ?? ''),
    'status' => $_GET['status'] ?? '',
    'category_id' => $_GET['category_id'] ?? '',
    'sort' => $_GET['sort'] ?? 'publish_date',
    'dir' => $_GET['dir'] ?? 'desc',
    'page' => (int) ($_GET['page'] ?? 1),
    'per_page' => 20,
];
$result = list_articles_admin($db, $filters);
$categories = get_categories($db);

$pageTitle = 'Статьи';
$activeNav = 'articles';
require __DIR__ . '/includes/layout-header.php';
?>
<div class="admin-header">
    <h1>Статьи</h1>
    <a href="/admin/article-edit.php?id=new" class="btn btn-gold">+ Новая статья</a>
</div>

<form class="filters" method="get">
    <input type="text" name="q" placeholder="Поиск по названию" value="<?= htmlspecialchars($filters['q'], ENT_QUOTES, 'UTF-8') ?>">
    <select name="status">
        <option value="">Любой статус</option>
        <?php foreach (['draft' => 'Черновик', 'published' => 'Опубликовано', 'hidden' => 'Скрыто', 'scheduled' => 'Запланировано'] as $val => $label): ?>
            <option value="<?= $val ?>" <?= $filters['status'] === $val ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
    </select>
    <select name="category_id">
        <option value="">Любая категория</option>
        <?php foreach ($categories as $c): ?>
            <option value="<?= $c['id'] ?>" <?= (string) $filters['category_id'] === (string) $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
    </select>
    <select name="sort">
        <option value="publish_date" <?= $filters['sort'] === 'publish_date' ? 'selected' : '' ?>>По дате публикации</option>
        <option value="updated_at" <?= $filters['sort'] === 'updated_at' ? 'selected' : '' ?>>По дате изменения</option>
        <option value="title" <?= $filters['sort'] === 'title' ? 'selected' : '' ?>>По названию</option>
    </select>
    <select name="dir">
        <option value="desc" <?= $filters['dir'] === 'desc' ? 'selected' : '' ?>>Убывание</option>
        <option value="asc" <?= $filters['dir'] === 'asc' ? 'selected' : '' ?>>Возрастание</option>
    </select>
    <button type="submit" class="btn">Применить</button>
</form>

<div class="table-wrap">
    <table class="admin-table">
        <thead>
        <tr><th>Обложка</th><th>Название</th><th>Категория</th><th>Статус</th><th>Публикация</th><th>Изменено</th><th>URL</th><th>Действия</th></tr>
        </thead>
        <tbody>
        <?php foreach ($result['items'] as $a): ?>
            <?php $url = '/pages/blog-' . $a['slug'] . '.html'; ?>
            <tr>
                <td><?php if ($a['cover_path']): ?><img class="admin-thumb" src="/uploads/blog/<?= htmlspecialchars($a['cover_path'], ENT_QUOTES, 'UTF-8') ?>" alt=""><?php else: ?><div class="admin-thumb"></div><?php endif; ?></td>
                <td><?= htmlspecialchars($a['title'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($a['category_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td><span class="badge badge-<?= $a['status'] ?>"><?= htmlspecialchars($a['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                <td><?= htmlspecialchars($a['published_at'] ?? $a['publish_at'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($a['updated_at'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="text-muted" style="font-size:.78rem"><?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?></a></td>
                <td class="admin-actions">
                    <a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn btn-sm btn-ghost">Открыть</a>
                    <a href="/admin/article-edit.php?id=<?= $a['id'] ?>" class="btn btn-sm">Редактировать</a>
                    <form method="post" class="inline-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= $a['id'] ?>">
                        <input type="hidden" name="action" value="duplicate">
                        <button type="submit" class="btn btn-sm btn-ghost">Копия</button>
                    </form>
                    <?php if ($a['status'] === 'published'): ?>
                        <form method="post" class="inline-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= $a['id'] ?>">
                            <input type="hidden" name="action" value="unpublish">
                            <button type="submit" class="btn btn-sm btn-ghost">Снять с публикации</button>
                        </form>
                    <?php else: ?>
                        <form method="post" class="inline-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= $a['id'] ?>">
                            <input type="hidden" name="action" value="publish">
                            <button type="submit" class="btn btn-sm btn-ghost">Опубликовать</button>
                        </form>
                    <?php endif; ?>
                    <form method="post" class="inline-form" onsubmit="return confirm('Удалить статью «<?= htmlspecialchars(addslashes($a['title']), ENT_QUOTES, 'UTF-8') ?>»? Это необратимо.');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= $a['id'] ?>">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" class="btn btn-sm btn-danger">Удалить</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$result['items']): ?>
            <tr><td colspan="8" class="text-muted">Ничего не найдено.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($result['pages'] > 1): ?>
    <div class="pagination">
        <?php for ($p = 1; $p <= $result['pages']; $p++): ?>
            <?php $qs = http_build_query(array_merge($_GET, ['page' => $p])); ?>
            <?php if ($p === $result['page']): ?>
                <span class="current"><?= $p ?></span>
            <?php else: ?>
                <a href="?<?= $qs ?>"><?= $p ?></a>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
