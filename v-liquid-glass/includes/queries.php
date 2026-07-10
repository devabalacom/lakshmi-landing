<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

/**
 * Lazily flips due "scheduled" articles to "published". Called at the top of
 * every read path below instead of depending on cron being available on the
 * shared host.
 */
function flip_scheduled_articles(PDO $db): void
{
    $db->exec(
        "UPDATE articles
         SET status = 'published',
             published_at = COALESCE(published_at, publish_at),
             updated_at = CURRENT_TIMESTAMP
         WHERE status = 'scheduled' AND publish_at IS NOT NULL AND publish_at <= datetime('now')"
    );
}

function article_public_url(array $article): string
{
    return '/pages/blog-' . $article['slug'] . '.html';
}

// --- Public reads ---

function get_published_articles(PDO $db, int $limit = 20, int $offset = 0): array
{
    flip_scheduled_articles($db);
    $stmt = $db->prepare(
        'SELECT a.*, c.name AS category_name, c.slug AS category_slug, m.path AS cover_path, m.alt AS cover_alt
         FROM articles a
         LEFT JOIN categories c ON c.id = a.category_id
         LEFT JOIN media m ON m.id = a.cover_media_id
         WHERE a.status = \'published\'
         ORDER BY COALESCE(a.published_at, a.publish_at, a.created_at) DESC
         LIMIT :limit OFFSET :offset'
    );
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function count_published_articles(PDO $db): int
{
    flip_scheduled_articles($db);
    return (int) $db->query("SELECT COUNT(*) AS c FROM articles WHERE status = 'published'")->fetch()['c'];
}

/** Used by the public single-article template. Draft/scheduled are never publicly reachable. */
function get_public_article_by_slug(PDO $db, string $slug): ?array
{
    flip_scheduled_articles($db);
    $stmt = $db->prepare(
        'SELECT a.*, c.name AS category_name, c.slug AS category_slug, m.path AS cover_path, m.alt AS cover_alt
         FROM articles a
         LEFT JOIN categories c ON c.id = a.category_id
         LEFT JOIN media m ON m.id = a.cover_media_id
         WHERE a.slug = :slug AND a.status IN (\'published\', \'hidden\')'
    );
    $stmt->execute(['slug' => $slug]);
    return $stmt->fetch() ?: null;
}

/** Used by admin preview — any status, including draft/scheduled. */
function get_article_by_slug_any_status(PDO $db, string $slug): ?array
{
    $stmt = $db->prepare('SELECT * FROM articles WHERE slug = :slug');
    $stmt->execute(['slug' => $slug]);
    return $stmt->fetch() ?: null;
}

function get_article_by_id(PDO $db, int $id): ?array
{
    $stmt = $db->prepare('SELECT * FROM articles WHERE id = :id');
    $stmt->execute(['id' => $id]);
    return $stmt->fetch() ?: null;
}

// --- Admin listing: search/filter/sort/paginate ---

const ADMIN_ARTICLE_SORT_COLUMNS = [
    'publish_date' => 'COALESCE(a.published_at, a.publish_at, a.created_at)',
    'updated_at' => 'a.updated_at',
    'title' => 'a.title',
];

function list_articles_admin(PDO $db, array $filters): array
{
    flip_scheduled_articles($db);

    $where = [];
    $params = [];

    if (!empty($filters['q'])) {
        $where[] = 'a.title LIKE :q';
        $params['q'] = '%' . $filters['q'] . '%';
    }
    if (!empty($filters['status'])) {
        $where[] = 'a.status = :status';
        $params['status'] = $filters['status'];
    }
    if (!empty($filters['category_id'])) {
        $where[] = 'a.category_id = :category_id';
        $params['category_id'] = (int) $filters['category_id'];
    }

    $sortKey = $filters['sort'] ?? 'publish_date';
    $sortCol = ADMIN_ARTICLE_SORT_COLUMNS[$sortKey] ?? ADMIN_ARTICLE_SORT_COLUMNS['publish_date'];
    $sortDir = (($filters['dir'] ?? 'desc') === 'asc') ? 'ASC' : 'DESC';

    $limit = max(1, min(100, (int) ($filters['per_page'] ?? 20)));
    $page = max(1, (int) ($filters['page'] ?? 1));
    $offset = ($page - 1) * $limit;

    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $countStmt = $db->prepare("SELECT COUNT(*) AS c FROM articles a $whereSql");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetch()['c'];

    $sql = "SELECT a.*, c.name AS category_name, m.path AS cover_path
            FROM articles a
            LEFT JOIN categories c ON c.id = a.category_id
            LEFT JOIN media m ON m.id = a.cover_media_id
            $whereSql
            ORDER BY $sortCol $sortDir
            LIMIT :limit OFFSET :offset";
    $stmt = $db->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue(':' . $k, $v);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return [
        'items' => $stmt->fetchAll(),
        'total' => $total,
        'page' => $page,
        'per_page' => $limit,
        'pages' => max(1, (int) ceil($total / $limit)),
    ];
}

// --- Categories ---

function get_categories(PDO $db): array
{
    return $db->query('SELECT * FROM categories ORDER BY name ASC')->fetchAll();
}

function find_or_create_category(PDO $db, string $name): ?int
{
    $name = trim($name);
    if ($name === '') {
        return null;
    }
    $slug = slugify($name);
    $stmt = $db->prepare('SELECT id FROM categories WHERE slug = :slug');
    $stmt->execute(['slug' => $slug]);
    $row = $stmt->fetch();
    if ($row) {
        return (int) $row['id'];
    }
    $stmt = $db->prepare('INSERT INTO categories (name, slug) VALUES (:name, :slug)');
    $stmt->execute(['name' => $name, 'slug' => $slug]);
    return (int) $db->lastInsertId();
}

// --- Tags ---

function set_article_tags(PDO $db, int $articleId, array $tagNames): void
{
    $db->prepare('DELETE FROM article_tags WHERE article_id = :id')->execute(['id' => $articleId]);
    foreach ($tagNames as $name) {
        $name = trim((string) $name);
        if ($name === '') {
            continue;
        }
        $slug = slugify($name);
        $stmt = $db->prepare('SELECT id FROM tags WHERE slug = :slug');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();
        if ($row) {
            $tagId = (int) $row['id'];
        } else {
            $ins = $db->prepare('INSERT INTO tags (name, slug) VALUES (:name, :slug)');
            $ins->execute(['name' => $name, 'slug' => $slug]);
            $tagId = (int) $db->lastInsertId();
        }
        $db->prepare('INSERT OR IGNORE INTO article_tags (article_id, tag_id) VALUES (:aid, :tid)')
            ->execute(['aid' => $articleId, 'tid' => $tagId]);
    }
}

function get_article_tags(PDO $db, int $articleId): array
{
    $stmt = $db->prepare(
        'SELECT t.name FROM tags t
         JOIN article_tags at ON at.tag_id = t.id
         WHERE at.article_id = :id
         ORDER BY t.name'
    );
    $stmt->execute(['id' => $articleId]);
    return array_column($stmt->fetchAll(), 'name');
}

// --- Redirects ---

function find_active_redirect(PDO $db, string $oldUrl): ?array
{
    $stmt = $db->prepare('SELECT * FROM redirects WHERE old_url = :u AND active = 1');
    $stmt->execute(['u' => $oldUrl]);
    return $stmt->fetch() ?: null;
}

/**
 * Records old->new and repoints any existing redirect that pointed at the
 * article's previous URL, so renaming twice doesn't create A->B->C chains.
 */
function record_slug_change_redirect(PDO $db, string $oldUrl, string $newUrl): void
{
    if ($oldUrl === $newUrl) {
        return;
    }
    $db->prepare(
        'UPDATE redirects SET new_url = :new WHERE new_url = :old AND active = 1'
    )->execute(['new' => $newUrl, 'old' => $oldUrl]);

    $stmt = $db->prepare('SELECT id FROM redirects WHERE old_url = :old');
    $stmt->execute(['old' => $oldUrl]);
    if ($stmt->fetch()) {
        $db->prepare('UPDATE redirects SET new_url = :new, active = 1 WHERE old_url = :old')
            ->execute(['new' => $newUrl, 'old' => $oldUrl]);
    } else {
        $db->prepare('INSERT INTO redirects (old_url, new_url, status_code, active) VALUES (:old, :new, 301, 1)')
            ->execute(['old' => $oldUrl, 'new' => $newUrl]);
    }
}

// --- Settings ---

function get_setting(PDO $db, string $key, string $default = ''): string
{
    $stmt = $db->prepare('SELECT value FROM settings WHERE key = :k');
    $stmt->execute(['k' => $key]);
    $row = $stmt->fetch();
    return $row ? (string) $row['value'] : $default;
}

function set_setting(PDO $db, string $key, string $value): void
{
    $db->prepare('INSERT INTO settings (key, value) VALUES (:k, :v) ON CONFLICT(key) DO UPDATE SET value = :v')
        ->execute(['k' => $key, 'v' => $value]);
}

// --- Article writes ---

const ARTICLE_WRITABLE_FIELDS = [
    'title', 'excerpt', 'category_id', 'author', 'cover_media_id',
    'reading_time_minutes', 'status', 'publish_at',
    'seo_title', 'meta_description', 'h1', 'canonical_url',
    'robots_index', 'robots_follow', 'focus_keyword', 'secondary_keywords',
    'og_title', 'og_description', 'og_image_id',
    'twitter_title', 'twitter_description', 'twitter_image_id',
    'schema_type', 'include_in_sitemap', 'sitemap_priority', 'sitemap_changefreq',
];

/**
 * $input holds the writable fields above plus 'slug', 'content_json', and
 * optionally 'tags' (array of names). Slug/content are handled explicitly
 * because they need derived values (content_html, reading time, uniqueness).
 */
function create_article(PDO $db, array $input): int
{
    $rawSlug = trim((string) ($input['slug'] ?? ''));
    $slugBase = slugify($rawSlug !== '' ? $rawSlug : (string) ($input['title'] ?? ''));
    $slug = unique_slug($db, $slugBase);

    $contentJson = $input['content_json'] ?? '{"time":0,"blocks":[],"version":"2.29.1"}';
    $contentHtml = render_editorjs_to_html($contentJson);
    $readingMinutes = $input['reading_time_minutes'] ?? estimate_reading_minutes($contentHtml);

    $status = $input['status'] ?? 'draft';
    if ($status === 'scheduled' && empty($input['publish_at'])) {
        $status = 'draft'; // never leave an article stuck as "scheduled" with nothing to trigger the flip
    }
    $publishedAt = ($status === 'published') ? date('Y-m-d H:i:s') : null;

    $values = array_merge(
        ['slug' => $slug, 'content_json' => $contentJson, 'content_html' => $contentHtml, 'published_at' => $publishedAt],
        array_intersect_key($input, array_flip(ARTICLE_WRITABLE_FIELDS))
    );
    $values['reading_time_minutes'] = $readingMinutes;
    $values['status'] = $status;

    $placeholders = array_map(fn($c) => ':' . $c, array_keys($values));
    $sql = 'INSERT INTO articles (' . implode(',', array_keys($values)) . ') VALUES (' . implode(',', $placeholders) . ')';
    $stmt = $db->prepare($sql);
    $stmt->execute($values);
    return (int) $db->lastInsertId();
}

/** Returns the effective public URL the article had BEFORE this update, for redirect bookkeeping. */
function update_article(PDO $db, int $id, array $input): void
{
    $existing = get_article_by_id($db, $id);
    if (!$existing) {
        throw new RuntimeException('Article not found');
    }

    $oldUrl = article_public_url($existing);

    $slug = $existing['slug'];
    if (!empty($input['slug'])) {
        $candidate = slugify($input['slug']);
        if ($candidate !== $existing['slug']) {
            $slug = unique_slug($db, $candidate, $id);
        }
    }

    $contentJson = $input['content_json'] ?? $existing['content_json'];
    $contentHtml = render_editorjs_to_html($contentJson);
    $readingMinutes = $input['reading_time_minutes'] ?? estimate_reading_minutes($contentHtml);

    $status = $input['status'] ?? $existing['status'];
    $publishAtValue = $input['publish_at'] ?? $existing['publish_at'];
    if ($status === 'scheduled' && empty($publishAtValue)) {
        $status = 'draft'; // never leave an article stuck as "scheduled" with nothing to trigger the flip
    }
    $publishedAt = $existing['published_at'];
    if ($status === 'published' && $publishedAt === null) {
        $publishedAt = date('Y-m-d H:i:s');
    }

    $values = array_intersect_key($input, array_flip(ARTICLE_WRITABLE_FIELDS));
    $values['slug'] = $slug;
    $values['content_json'] = $contentJson;
    $values['content_html'] = $contentHtml;
    $values['reading_time_minutes'] = $readingMinutes;
    $values['status'] = $status;
    $values['published_at'] = $publishedAt;
    $values['updated_at'] = date('Y-m-d H:i:s');

    $setSql = implode(', ', array_map(fn($c) => "$c = :$c", array_keys($values)));
    $values['id'] = $id;
    $db->prepare("UPDATE articles SET $setSql WHERE id = :id")->execute($values);

    if ($slug !== $existing['slug']) {
        $newUrl = '/pages/blog-' . $slug . '.html';
        record_slug_change_redirect($db, $oldUrl, $newUrl);
        rewrite_internal_links_in_articles($db, $oldUrl, $newUrl, $id);
    }

    if (!empty($input['tags']) && is_array($input['tags'])) {
        set_article_tags($db, $id, $input['tags']);
    }
}

/** Bounded find-replace of the old URL inside OTHER articles' own content — not the static marketing pages. */
function rewrite_internal_links_in_articles(PDO $db, string $oldUrl, string $newUrl, int $excludeId): void
{
    $stmt = $db->prepare('SELECT id, content_json FROM articles WHERE id != :id AND content_json LIKE :needle');
    $stmt->execute(['id' => $excludeId, 'needle' => '%' . $oldUrl . '%']);
    foreach ($stmt->fetchAll() as $row) {
        $updatedJson = str_replace($oldUrl, $newUrl, $row['content_json']);
        $updatedHtml = render_editorjs_to_html($updatedJson);
        $db->prepare('UPDATE articles SET content_json = :j, content_html = :h WHERE id = :id')
            ->execute(['j' => $updatedJson, 'h' => $updatedHtml, 'id' => $row['id']]);
    }
}

function set_article_status(PDO $db, int $id, string $status): void
{
    if (!in_array($status, ['draft', 'published', 'hidden', 'scheduled'], true)) {
        throw new InvalidArgumentException('Invalid status');
    }
    $article = get_article_by_id($db, $id);
    if (!$article) {
        throw new RuntimeException('Article not found');
    }
    $publishedAt = $article['published_at'];
    if ($status === 'published' && $publishedAt === null) {
        $publishedAt = date('Y-m-d H:i:s');
    }
    $db->prepare('UPDATE articles SET status = :status, published_at = :pub, updated_at = CURRENT_TIMESTAMP WHERE id = :id')
        ->execute(['status' => $status, 'pub' => $publishedAt, 'id' => $id]);
}

function delete_article(PDO $db, int $id): void
{
    $db->prepare('DELETE FROM articles WHERE id = :id')->execute(['id' => $id]);
}

function duplicate_article(PDO $db, int $id): int
{
    $article = get_article_by_id($db, $id);
    if (!$article) {
        throw new RuntimeException('Article not found');
    }
    $baseSlug = slugify($article['slug'] . '-copy');
    $slug = unique_slug($db, $baseSlug);

    $values = $article;
    unset($values['id']);
    $values['slug'] = $slug;
    $values['title'] = $article['title'] . ' (копия)';
    $values['status'] = 'draft';
    $values['published_at'] = null;
    $values['created_at'] = date('Y-m-d H:i:s');
    $values['updated_at'] = date('Y-m-d H:i:s');

    $cols = array_keys($values);
    $placeholders = array_map(fn($c) => ':' . $c, $cols);
    $stmt = $db->prepare('INSERT INTO articles (' . implode(',', $cols) . ') VALUES (' . implode(',', $placeholders) . ')');
    $stmt->execute($values);
    $newId = (int) $db->lastInsertId();

    $tags = get_article_tags($db, $id);
    if ($tags) {
        set_article_tags($db, $newId, $tags);
    }

    return $newId;
}

// --- Media ---

function media_is_referenced(PDO $db, int $mediaId): bool
{
    $stmt = $db->prepare(
        'SELECT COUNT(*) AS c FROM articles
         WHERE cover_media_id = :id OR og_image_id = :id OR twitter_image_id = :id'
    );
    $stmt->execute(['id' => $mediaId]);
    if ((int) $stmt->fetch()['c'] > 0) {
        return true;
    }
    $media = find_media_by_id($mediaId);
    if (!$media) {
        return false;
    }
    $stmt = $db->prepare("SELECT COUNT(*) AS c FROM articles WHERE content_html LIKE :needle");
    $stmt->execute(['needle' => '%' . $media['path'] . '%']);
    return (int) $stmt->fetch()['c'] > 0;
}

function list_media(PDO $db, int $limit = 60, int $offset = 0): array
{
    $stmt = $db->prepare('SELECT * FROM media ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}
