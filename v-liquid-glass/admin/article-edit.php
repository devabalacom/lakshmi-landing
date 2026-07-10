<?php
require_once __DIR__ . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/queries.php';

$db = blog_db();
$idParam = $_GET['id'] ?? 'new';
$isNew = ($idParam === 'new');
$article = null;
$error = null;
$saved = false;

if (!$isNew) {
    $article = get_article_by_id($db, (int) $idParam);
    if (!$article) {
        header('Location: /admin/articles.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $formAction = $_POST['form_action'] ?? 'save_changes';

    $tagsRaw = trim((string) ($_POST['tags'] ?? ''));
    $tags = $tagsRaw === '' ? [] : array_map('trim', explode(',', $tagsRaw));

    $categoryId = null;
    if (!empty($_POST['category_name'])) {
        $categoryId = find_or_create_category($db, $_POST['category_name']);
    }

    $status = $formAction === 'save_draft' ? 'draft' : ($_POST['status'] ?? 'draft');
    $publishAt = null;
    if ($status === 'scheduled' && !empty($_POST['publish_at'])) {
        $publishAt = str_replace('T', ' ', $_POST['publish_at']) . ':00';
    }

    $input = [
        'title' => trim($_POST['title'] ?? ''),
        'slug' => trim($_POST['slug'] ?? ''),
        'excerpt' => trim($_POST['excerpt'] ?? ''),
        'content_json' => $_POST['content_json'] ?? '{"time":0,"blocks":[],"version":"2.29.1"}',
        'category_id' => $categoryId,
        'author' => trim($_POST['author'] ?? '') ?: 'Пром-текстиль',
        'cover_media_id' => !empty($_POST['cover_media_id']) ? (int) $_POST['cover_media_id'] : null,
        'reading_time_minutes' => !empty($_POST['reading_time_minutes']) ? (int) $_POST['reading_time_minutes'] : null,
        'status' => $status,
        'publish_at' => $publishAt,
        'seo_title' => trim($_POST['seo_title'] ?? ''),
        'meta_description' => trim($_POST['meta_description'] ?? ''),
        'h1' => trim($_POST['h1'] ?? ''),
        'canonical_url' => trim($_POST['canonical_url'] ?? ''),
        'robots_index' => isset($_POST['robots_index']) ? 1 : 0,
        'robots_follow' => isset($_POST['robots_follow']) ? 1 : 0,
        'focus_keyword' => trim($_POST['focus_keyword'] ?? ''),
        'secondary_keywords' => trim($_POST['secondary_keywords'] ?? ''),
        'og_title' => trim($_POST['og_title'] ?? ''),
        'og_description' => trim($_POST['og_description'] ?? ''),
        'og_image_id' => !empty($_POST['og_image_id']) ? (int) $_POST['og_image_id'] : null,
        'twitter_title' => trim($_POST['twitter_title'] ?? ''),
        'twitter_description' => trim($_POST['twitter_description'] ?? ''),
        'twitter_image_id' => !empty($_POST['twitter_image_id']) ? (int) $_POST['twitter_image_id'] : null,
        'schema_type' => in_array($_POST['schema_type'] ?? '', ['Article', 'BlogPosting'], true) ? $_POST['schema_type'] : 'Article',
        'include_in_sitemap' => isset($_POST['include_in_sitemap']) ? 1 : 0,
        'sitemap_priority' => (float) ($_POST['sitemap_priority'] ?? 0.6),
        'sitemap_changefreq' => $_POST['sitemap_changefreq'] ?? 'monthly',
        'tags' => $tags,
    ];

    if ($input['title'] === '') {
        $error = 'Название статьи обязательно.';
    } else {
        try {
            if ($isNew) {
                $newId = create_article($db, $input);
                if (!empty($tags)) {
                    set_article_tags($db, $newId, $tags);
                }
                header('Location: /admin/article-edit.php?id=' . $newId . '&saved=1');
                exit;
            } else {
                update_article($db, (int) $idParam, $input);
                header('Location: /admin/article-edit.php?id=' . $idParam . '&saved=1');
                exit;
            }
        } catch (Throwable $e) {
            $error = 'Не удалось сохранить: ' . $e->getMessage();
        }
    }
}

$saved = isset($_GET['saved']);
$tab = $_GET['tab'] ?? 'basic';
$categories = get_categories($db);
$articleTags = $article ? get_article_tags($db, (int) $article['id']) : [];
$contentJson = $article['content_json'] ?? '{"time":0,"blocks":[],"version":"2.29.1"}';
$publicUrl = $article ? article_public_url($article) : '';

$coverMedia = ($article && $article['cover_media_id']) ? find_media_by_id((int) $article['cover_media_id']) : null;
$ogMedia = ($article && $article['og_image_id']) ? find_media_by_id((int) $article['og_image_id']) : null;
$twitterMedia = ($article && $article['twitter_image_id']) ? find_media_by_id((int) $article['twitter_image_id']) : null;

$pageTitle = $isNew ? 'Новая статья' : 'Редактирование статьи';
$activeNav = 'edit';
require __DIR__ . '/includes/layout-header.php';
?>
<div class="admin-header">
    <h1><?= $isNew ? 'Новая статья' : htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8') ?></h1>
    <?php if (!$isNew): ?>
        <div style="display:flex;gap:.5rem">
            <button type="button" class="btn" id="preview-btn" <?= $isNew ? 'disabled' : '' ?>>Предпросмотр</button>
            <a href="<?= htmlspecialchars($publicUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn btn-ghost">Открыть на сайте</a>
        </div>
    <?php endif; ?>
</div>

<?php if ($saved): ?><div class="flash flash-ok">Сохранено.</div><?php endif; ?>
<?php if ($error): ?><div class="flash flash-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

<div class="tabs" id="article-tabs">
    <a href="#" data-tab="basic" class="<?= $tab === 'basic' ? 'active' : '' ?>">Основное</a>
    <a href="#" data-tab="content" class="<?= $tab === 'content' ? 'active' : '' ?>">Контент</a>
    <a href="#" data-tab="seo" class="<?= $tab === 'seo' ? 'active' : '' ?>">SEO</a>
</div>

<form method="post" id="article-form">
    <?= csrf_field() ?>
    <input type="hidden" name="content_json" id="content_json" value="<?= htmlspecialchars($contentJson, ENT_QUOTES, 'UTF-8') ?>">

    <div class="tab-panel" data-tab="basic" style="<?= $tab === 'basic' ? '' : 'display:none' ?>">
        <div class="two-col">
            <div>
                <div class="card">
                    <div class="field">
                        <label for="title">Название статьи</label>
                        <input type="text" id="title" name="title" value="<?= htmlspecialchars($article['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="field">
                        <label for="slug">URL-slug</label>
                        <input type="text" id="slug" name="slug" value="<?= htmlspecialchars($article['slug'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="оставьте пустым — сформируется из названия">
                        <?php if (!$isNew): ?><div class="hint">Смена slug у опубликованной статьи создаст 301-редирект со старого адреса.</div><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="excerpt">Краткое описание для карточки</label>
                        <textarea id="excerpt" name="excerpt"><?= htmlspecialchars($article['excerpt'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div class="field">
                        <label for="category_name">Категория</label>
                        <input type="text" id="category_name" name="category_name" list="category-list" value="<?= htmlspecialchars($article['category_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="напр. Материалы">
                        <datalist id="category-list">
                            <?php foreach ($categories as $c): ?><option value="<?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?>"><?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="field">
                        <label for="tags">Теги (через запятую)</label>
                        <input type="text" id="tags" name="tags" value="<?= htmlspecialchars(implode(', ', $articleTags), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="field">
                        <label for="author">Автор</label>
                        <input type="text" id="author" name="author" value="<?= htmlspecialchars($article['author'] ?? 'Пром-текстиль', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="field">
                        <label for="reading_time_minutes">Время чтения (мин.) — пусто = авторасчёт</label>
                        <input type="number" id="reading_time_minutes" name="reading_time_minutes" min="1" value="<?= htmlspecialchars((string) ($article['reading_time_minutes'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
            </div>
            <div>
                <div class="card">
                    <div class="field">
                        <label>Статус</label>
                        <select name="status" id="status-select">
                            <?php foreach (['draft' => 'Черновик', 'published' => 'Опубликовано', 'hidden' => 'Скрыто', 'scheduled' => 'Запланировано'] as $val => $label): ?>
                                <option value="<?= $val ?>" <?= ($article['status'] ?? 'draft') === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field" id="publish-at-field" style="<?= ($article['status'] ?? '') === 'scheduled' ? '' : 'display:none' ?>">
                        <label for="publish_at">Дата и время публикации</label>
                        <input type="datetime-local" id="publish_at" name="publish_at" value="<?= htmlspecialchars($article['publish_at'] ? str_replace(' ', 'T', substr($article['publish_at'], 0, 16)) : '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="field">
                        <label>Обложка статьи</label>
                        <div id="cover-preview">
                            <?php if ($coverMedia): ?><img src="/uploads/blog/<?= htmlspecialchars($coverMedia['path'], ENT_QUOTES, 'UTF-8') ?>" style="width:100%;border-radius:8px;margin-bottom:.5rem"><?php endif; ?>
                        </div>
                        <input type="hidden" name="cover_media_id" id="cover_media_id" value="<?= htmlspecialchars((string) ($article['cover_media_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <button type="button" class="btn btn-sm" onclick="openMediaPicker('cover')">Выбрать обложку</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-panel" data-tab="content" style="<?= $tab === 'content' ? '' : 'display:none' ?>">
        <div class="card">
            <div id="editorjs"></div>
        </div>
        <script id="editorjs-initial-data" type="application/json"><?= str_replace('</', '<\\/', $contentJson) ?></script>
    </div>

    <div class="tab-panel" data-tab="seo" style="<?= $tab === 'seo' ? '' : 'display:none' ?>">
        <div class="card">
            <div class="field"><label for="seo_title">SEO Title (пусто → название статьи)</label><input type="text" id="seo_title" name="seo_title" value="<?= htmlspecialchars($article['seo_title'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="field"><label for="meta_description">Meta Description (пусто → краткое описание)</label><textarea id="meta_description" name="meta_description"><?= htmlspecialchars($article['meta_description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea></div>
            <div class="field"><label for="h1">H1 (пусто → название статьи)</label><input type="text" id="h1" name="h1" value="<?= htmlspecialchars($article['h1'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="field"><label for="canonical_url">Canonical URL (пусто → текущий URL)</label><input type="url" id="canonical_url" name="canonical_url" value="<?= htmlspecialchars($article['canonical_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="field checkbox-row"><input type="checkbox" id="robots_index" name="robots_index" <?= ($article['robots_index'] ?? 1) ? 'checked' : '' ?>><label for="robots_index" style="margin:0">Индексировать (robots: index)</label></div>
            <div class="field checkbox-row"><input type="checkbox" id="robots_follow" name="robots_follow" <?= ($article['robots_follow'] ?? 1) ? 'checked' : '' ?>><label for="robots_follow" style="margin:0">Переходить по ссылкам (robots: follow)</label></div>
            <div class="field"><label for="focus_keyword">Основной ключевой запрос</label><input type="text" id="focus_keyword" name="focus_keyword" value="<?= htmlspecialchars($article['focus_keyword'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="field"><label for="secondary_keywords">Дополнительные ключевые запросы</label><textarea id="secondary_keywords" name="secondary_keywords"><?= htmlspecialchars($article['secondary_keywords'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea><div class="hint">Только для внутреннего использования — никогда не выводится как публичный meta keywords.</div></div>
            <div class="field"><label for="schema_type">Тип Schema.org</label><select id="schema_type" name="schema_type"><option value="Article" <?= ($article['schema_type'] ?? 'Article') === 'Article' ? 'selected' : '' ?>>Article</option><option value="BlogPosting" <?= ($article['schema_type'] ?? '') === 'BlogPosting' ? 'selected' : '' ?>>BlogPosting</option></select></div>
        </div>
        <div class="card">
            <h3 style="margin-top:0">Open Graph</h3>
            <div class="field"><label for="og_title">OG Title</label><input type="text" id="og_title" name="og_title" value="<?= htmlspecialchars($article['og_title'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="field"><label for="og_description">OG Description</label><textarea id="og_description" name="og_description"><?= htmlspecialchars($article['og_description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea></div>
            <div class="field">
                <label>OG Image</label>
                <div id="og-preview"><?php if ($ogMedia): ?><img src="/uploads/blog/<?= htmlspecialchars($ogMedia['path'], ENT_QUOTES, 'UTF-8') ?>" style="max-width:200px;border-radius:8px;margin-bottom:.5rem"><?php endif; ?></div>
                <input type="hidden" name="og_image_id" id="og_image_id" value="<?= htmlspecialchars((string) ($article['og_image_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                <button type="button" class="btn btn-sm" onclick="openMediaPicker('og')">Выбрать изображение</button>
            </div>
        </div>
        <div class="card">
            <h3 style="margin-top:0">Twitter Card</h3>
            <div class="field"><label for="twitter_title">Twitter Title</label><input type="text" id="twitter_title" name="twitter_title" value="<?= htmlspecialchars($article['twitter_title'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="field"><label for="twitter_description">Twitter Description</label><textarea id="twitter_description" name="twitter_description"><?= htmlspecialchars($article['twitter_description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea></div>
            <div class="field">
                <label>Twitter Image</label>
                <div id="twitter-preview"><?php if ($twitterMedia): ?><img src="/uploads/blog/<?= htmlspecialchars($twitterMedia['path'], ENT_QUOTES, 'UTF-8') ?>" style="max-width:200px;border-radius:8px;margin-bottom:.5rem"><?php endif; ?></div>
                <input type="hidden" name="twitter_image_id" id="twitter_image_id" value="<?= htmlspecialchars((string) ($article['twitter_image_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                <button type="button" class="btn btn-sm" onclick="openMediaPicker('twitter')">Выбрать изображение</button>
            </div>
        </div>
        <div class="card">
            <h3 style="margin-top:0">Sitemap</h3>
            <div class="field checkbox-row"><input type="checkbox" id="include_in_sitemap" name="include_in_sitemap" <?= ($article['include_in_sitemap'] ?? 1) ? 'checked' : '' ?>><label for="include_in_sitemap" style="margin:0">Включить в sitemap.xml</label></div>
            <div class="field"><label for="sitemap_priority">Приоритет (0.0–1.0)</label><input type="text" id="sitemap_priority" name="sitemap_priority" value="<?= htmlspecialchars((string) ($article['sitemap_priority'] ?? 0.6), ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="field"><label for="sitemap_changefreq">Частота обновления</label>
                <select id="sitemap_changefreq" name="sitemap_changefreq">
                    <?php foreach (['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'] as $freq): ?>
                        <option value="<?= $freq ?>" <?= ($article['sitemap_changefreq'] ?? 'monthly') === $freq ? 'selected' : '' ?>><?= $freq ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:.6rem;margin-top:1rem">
        <button type="submit" name="form_action" value="save_changes" class="btn btn-gold">Сохранить изменения</button>
        <button type="submit" name="form_action" value="save_draft" class="btn">Сохранить как черновик</button>
    </div>
</form>

<script>
window.ADMIN_CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;
window.ARTICLE_ID = <?= $isNew ? 'null' : (int) $idParam ?>;

document.querySelectorAll('#article-tabs a').forEach(function (a) {
    a.addEventListener('click', function (e) {
        e.preventDefault();
        var target = a.getAttribute('data-tab');
        document.querySelectorAll('#article-tabs a').forEach(function (el) { el.classList.toggle('active', el === a); });
        document.querySelectorAll('.tab-panel').forEach(function (panel) {
            panel.style.display = (panel.getAttribute('data-tab') === target) ? '' : 'none';
        });
    });
});

function syncPublishAtRequired() {
    var isScheduled = document.getElementById('status-select').value === 'scheduled';
    document.getElementById('publish-at-field').style.display = isScheduled ? '' : 'none';
    document.getElementById('publish_at').required = isScheduled;
}
document.getElementById('status-select').addEventListener('change', syncPublishAtRequired);
syncPublishAtRequired();

function openMediaPicker(target) {
    var w = window.open('/admin/media.php?picker=' + target, 'mediaPicker', 'width=920,height=700');
    window.__mediaPickerTarget = target;
}

window.setPickedMedia = function (target, id, path) {
    var hiddenId = target === 'cover' ? 'cover_media_id' : (target + '_image_id');
    var previewId = target === 'cover' ? 'cover-preview' : (target + '-preview');
    var hidden = document.getElementById(hiddenId);
    var preview = document.getElementById(previewId);
    if (hidden) hidden.value = id;
    if (preview) preview.innerHTML = '<img src="/uploads/blog/' + path + '" style="max-width:200px;border-radius:8px;margin-bottom:.5rem">';
};

<?php if (!$isNew): ?>
document.getElementById('preview-btn').addEventListener('click', function () {
    window.getEditorContentJson().then(function (json) {
        var fd = new FormData();
        fd.append('csrf_token', window.ADMIN_CSRF_TOKEN);
        fd.append('id', window.ARTICLE_ID);
        fd.append('content_json', json);
        fetch('/admin/autosave.php', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function () { window.open('<?= htmlspecialchars($publicUrl, ENT_QUOTES, 'UTF-8') ?>?preview=1', '_blank'); });
    });
});

// Draft autosave every 30s while editing an existing article.
setInterval(function () {
    window.getEditorContentJson().then(function (json) {
        var fd = new FormData();
        fd.append('csrf_token', window.ADMIN_CSRF_TOKEN);
        fd.append('id', window.ARTICLE_ID);
        fd.append('content_json', json);
        fetch('/admin/autosave.php', { method: 'POST', body: fd, credentials: 'same-origin' });
    });
}, 30000);
<?php endif; ?>
</script>

<script src="/admin/assets/editorjs/editorjs.umd.min.js"></script>
<script src="/admin/assets/editorjs/header.umd.min.js"></script>
<script src="/admin/assets/editorjs/list.umd.min.js"></script>
<script src="/admin/assets/editorjs/quote.umd.min.js"></script>
<script src="/admin/assets/editorjs/table.umd.min.js"></script>
<script src="/admin/assets/editorjs/delimiter.umd.min.js"></script>
<script src="/admin/assets/editorjs/marker.umd.min.js"></script>
<script src="/admin/assets/editorjs/link.umd.min.js"></script>
<script src="/admin/assets/editorjs/image.umd.min.js"></script>
<script src="/admin/assets/editorjs/cta-tool.js"></script>
<script src="/admin/assets/editor-init.js"></script>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
