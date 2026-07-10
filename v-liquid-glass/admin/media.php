<?php
require_once __DIR__ . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/queries.php';
require_once dirname(__DIR__) . '/includes/upload.php';

$db = blog_db();
$picker = $_GET['picker'] ?? null; // 'cover' | 'og' | 'twitter' — popup picker mode for article-edit.php
$error = null;
$uploaded = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $action = $_POST['action'] ?? '';

    if ($action === 'upload') {
        try {
            $uploaded = handle_image_upload($_FILES['file']);
        } catch (UploadException $e) {
            $error = $e->getMessage();
        }
    } elseif ($action === 'update_meta') {
        $id = (int) ($_POST['id'] ?? 0);
        $db->prepare('UPDATE media SET alt = :alt, title = :title, caption = :caption, description = :description WHERE id = :id')
            ->execute([
                'alt' => trim($_POST['alt'] ?? ''),
                'title' => trim($_POST['title'] ?? ''),
                'caption' => trim($_POST['caption'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'id' => $id,
            ]);
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if (media_is_referenced($db, $id)) {
            $error = 'Изображение используется в одной или нескольких статьях и не может быть удалено. Сначала замените его там.';
        } else {
            $media = find_media_by_id($id);
            if ($media) {
                $fullPath = dirname(__DIR__) . '/uploads/blog/' . $media['path'];
                if (is_file($fullPath)) {
                    @unlink($fullPath);
                }
                $db->prepare('DELETE FROM media WHERE id = :id')->execute(['id' => $id]);
            }
        }
    }

    if ($picker && $uploaded) {
        // fall through to render the picker page, which will auto-select $uploaded via JS
    } elseif (!$picker || !$uploaded) {
        $redirectQs = $picker ? ('?picker=' . urlencode($picker)) : '';
        if ($error === null) {
            header('Location: /admin/media.php' . $redirectQs);
            exit;
        }
    }
}

$items = list_media($db, 200, 0);

$pageTitle = 'Медиатека';
$activeNav = 'media';

if ($picker) {
    ?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Выбор изображения</title>
<link rel="stylesheet" href="/admin/assets/admin.css">
</head>
<body style="padding:1.2rem">
<h3 style="margin-top:0">Выбор изображения</h3>
<?php if ($error): ?><div class="flash flash-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data" style="margin-bottom:1rem">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="upload">
    <input type="file" name="file" accept="image/jpeg,image/png,image/webp" required>
    <button type="submit" class="btn btn-sm">Загрузить и выбрать</button>
</form>
<div class="media-grid">
    <?php foreach ($items as $m): ?>
        <div class="media-item" onclick="pick(<?= $m['id'] ?>, '<?= htmlspecialchars(addslashes($m['path']), ENT_QUOTES, 'UTF-8') ?>')">
            <img src="/uploads/blog/<?= htmlspecialchars($m['path'], ENT_QUOTES, 'UTF-8') ?>" alt="">
            <div class="media-meta"><?= htmlspecialchars($m['original_filename'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    <?php endforeach; ?>
</div>
<script>
function pick(id, path) {
    if (window.opener && window.opener.setPickedMedia) {
        window.opener.setPickedMedia('<?= htmlspecialchars($picker, ENT_QUOTES, 'UTF-8') ?>', id, path);
    }
    window.close();
}
<?php if ($uploaded): ?>
pick(<?= $uploaded['id'] ?>, '<?= htmlspecialchars(addslashes($uploaded['path']), ENT_QUOTES, 'UTF-8') ?>');
<?php endif; ?>
</script>
</body>
</html>
    <?php
    exit;
}

require __DIR__ . '/includes/layout-header.php';
?>
<div class="admin-header"><h1>Медиатека</h1></div>
<?php if ($error): ?><div class="flash flash-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

<div class="card">
    <form method="post" enctype="multipart/form-data" id="upload-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="upload">
        <div class="dropzone" id="dropzone">
            Перетащите изображение сюда или нажмите, чтобы выбрать файл (JPG/PNG/WebP, до 10 МБ)
            <input type="file" name="file" id="file-input" accept="image/jpeg,image/png,image/webp" style="display:none" required>
        </div>
    </form>
</div>

<div class="media-grid">
    <?php foreach ($items as $m): ?>
        <div class="media-item">
            <img src="/uploads/blog/<?= htmlspecialchars($m['path'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($m['alt'], ENT_QUOTES, 'UTF-8') ?>">
            <div class="media-meta">
                <div style="font-weight:700;color:var(--ink);margin-bottom:.3rem;word-break:break-word"><?= htmlspecialchars($m['original_filename'], ENT_QUOTES, 'UTF-8') ?></div>
                <form method="post" style="margin-bottom:.3rem">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_meta">
                    <input type="hidden" name="id" value="<?= $m['id'] ?>">
                    <input type="text" name="alt" value="<?= htmlspecialchars($m['alt'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Alt-текст" style="margin-bottom:.3rem">
                    <input type="text" name="title" value="<?= htmlspecialchars($m['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Название" style="margin-bottom:.3rem">
                    <input type="text" name="caption" value="<?= htmlspecialchars($m['caption'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Подпись" style="margin-bottom:.3rem">
                    <button type="submit" class="btn btn-sm" style="width:100%">Сохранить</button>
                </form>
                <form method="post" onsubmit="return confirm('Удалить изображение?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $m['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger" style="width:100%">Удалить</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (!$items): ?><p style="color:var(--ink3)">Пока нет загруженных изображений.</p><?php endif; ?>
</div>

<script>
var dz = document.getElementById('dropzone');
var fileInput = document.getElementById('file-input');
var form = document.getElementById('upload-form');
dz.addEventListener('click', function () { fileInput.click(); });
fileInput.addEventListener('change', function () { if (fileInput.files.length) form.submit(); });
['dragover', 'dragenter'].forEach(function (evt) {
    dz.addEventListener(evt, function (e) { e.preventDefault(); dz.classList.add('dragover'); });
});
['dragleave', 'drop'].forEach(function (evt) {
    dz.addEventListener(evt, function (e) { e.preventDefault(); dz.classList.remove('dragover'); });
});
dz.addEventListener('drop', function (e) {
    if (e.dataTransfer.files.length) {
        fileInput.files = e.dataTransfer.files;
        form.submit();
    }
});
</script>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
