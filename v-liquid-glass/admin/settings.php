<?php
require_once __DIR__ . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/queries.php';

$db = blog_db();
$saved = false;

const SETTINGS_FIELDS = [
    'site_name' => 'Название сайта',
    'default_meta_description' => 'Meta description по умолчанию (если у статьи нет своего и нет краткого описания)',
    'default_og_image' => 'OG-изображение по умолчанию (URL, напр. /og-image.svg)',
    'cta_phone' => 'Телефон для CTA-блоков (напр. +7 981 817-26-49)',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    foreach (SETTINGS_FIELDS as $key => $label) {
        set_setting($db, $key, trim($_POST[$key] ?? ''));
    }
    header('Location: /admin/settings.php?saved=1');
    exit;
}

$saved = isset($_GET['saved']);
$values = [];
foreach (SETTINGS_FIELDS as $key => $label) {
    $values[$key] = get_setting($db, $key, '');
}

$pageTitle = 'Настройки';
$activeNav = 'settings';
require __DIR__ . '/includes/layout-header.php';
?>
<div class="admin-header"><h1>Настройки сайта</h1></div>
<?php if ($saved): ?><div class="flash flash-ok">Сохранено.</div><?php endif; ?>

<form method="post" class="card">
    <?= csrf_field() ?>
    <?php foreach (SETTINGS_FIELDS as $key => $label): ?>
        <div class="field">
            <label for="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></label>
            <input type="text" id="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" name="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($values[$key], ENT_QUOTES, 'UTF-8') ?>">
        </div>
    <?php endforeach; ?>
    <button type="submit" class="btn btn-gold">Сохранить настройки</button>
</form>

<div class="card">
    <h3>Резервная копия</h3>
    <p class="text-muted" style="font-size:.88rem;margin-bottom:1rem">База данных блога (статьи, медиатека, редиректы) хранится в одном файле SQLite. Скачайте копию перед крупными изменениями.</p>
    <a href="/admin/backup.php" class="btn">Скачать резервную копию (.sqlite)</a>
</div>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
