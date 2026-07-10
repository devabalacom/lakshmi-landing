<?php
require_once __DIR__ . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/queries.php';

$db = blog_db();
flip_scheduled_articles($db);

$counts = [];
foreach (['draft', 'published', 'hidden', 'scheduled'] as $status) {
    $stmt = $db->prepare('SELECT COUNT(*) AS c FROM articles WHERE status = :s');
    $stmt->execute(['s' => $status]);
    $counts[$status] = (int) $stmt->fetch()['c'];
}

$recent = $db->query('SELECT * FROM articles ORDER BY updated_at DESC LIMIT 8')->fetchAll();

$pageTitle = 'Дашборд';
$activeNav = 'dashboard';
require __DIR__ . '/includes/layout-header.php';
?>
<div class="admin-header"><h1>Дашборд</h1></div>

<div class="card stat-row">
    <div><div class="stat-value"><?= $counts['published'] ?></div><div class="stat-label">Опубликовано</div></div>
    <div><div class="stat-value"><?= $counts['draft'] ?></div><div class="stat-label">Черновики</div></div>
    <div><div class="stat-value"><?= $counts['scheduled'] ?></div><div class="stat-label">Запланировано</div></div>
    <div><div class="stat-value"><?= $counts['hidden'] ?></div><div class="stat-label">Скрыто</div></div>
</div>

<div class="card">
    <h3>Недавно изменённые статьи</h3>
    <div class="table-wrap">
        <table class="admin-table">
            <thead><tr><th>Название</th><th>Статус</th><th>Изменено</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($recent as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['title'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge badge-<?= $a['status'] ?>"><?= htmlspecialchars($a['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><?= htmlspecialchars($a['updated_at'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><a href="/admin/article-edit.php?id=<?= $a['id'] ?>" class="btn btn-sm">Редактировать</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$recent): ?>
                <tr><td colspan="4" class="text-muted">Пока нет статей. <a href="/admin/article-edit.php?id=new">Создать первую</a></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
