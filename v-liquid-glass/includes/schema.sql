-- Blog CMS schema (SQLite)
-- Applied automatically by includes/db.php on first run. Keep in sync with that bootstrap logic.

CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS media (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    filename TEXT NOT NULL,
    original_filename TEXT NOT NULL,
    path TEXT NOT NULL,
    mime_type TEXT NOT NULL,
    file_size INTEGER NOT NULL,
    width INTEGER,
    height INTEGER,
    alt TEXT NOT NULL DEFAULT '',
    title TEXT,
    caption TEXT,
    description TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS articles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    excerpt TEXT,
    content_json TEXT NOT NULL DEFAULT '{"time":0,"blocks":[],"version":"2.29.1"}',
    content_html TEXT NOT NULL DEFAULT '',
    category_id INTEGER REFERENCES categories(id) ON DELETE SET NULL,
    author TEXT,
    cover_media_id INTEGER REFERENCES media(id) ON DELETE SET NULL,
    reading_time_minutes INTEGER,
    status TEXT NOT NULL DEFAULT 'draft' CHECK(status IN ('draft','published','hidden','scheduled')),
    publish_at DATETIME,
    published_at DATETIME,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    seo_title TEXT,
    meta_description TEXT,
    h1 TEXT,
    canonical_url TEXT,
    robots_index INTEGER NOT NULL DEFAULT 1,
    robots_follow INTEGER NOT NULL DEFAULT 1,
    focus_keyword TEXT,
    secondary_keywords TEXT,
    og_title TEXT,
    og_description TEXT,
    og_image_id INTEGER REFERENCES media(id) ON DELETE SET NULL,
    twitter_title TEXT,
    twitter_description TEXT,
    twitter_image_id INTEGER REFERENCES media(id) ON DELETE SET NULL,
    schema_type TEXT NOT NULL DEFAULT 'Article' CHECK(schema_type IN ('Article','BlogPosting')),
    include_in_sitemap INTEGER NOT NULL DEFAULT 1,
    sitemap_priority REAL NOT NULL DEFAULT 0.6,
    sitemap_changefreq TEXT NOT NULL DEFAULT 'monthly'
);
CREATE INDEX IF NOT EXISTS idx_articles_status_publish ON articles(status, publish_at);
CREATE INDEX IF NOT EXISTS idx_articles_category ON articles(category_id);

CREATE TABLE IF NOT EXISTS tags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    slug TEXT NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS article_tags (
    article_id INTEGER NOT NULL REFERENCES articles(id) ON DELETE CASCADE,
    tag_id INTEGER NOT NULL REFERENCES tags(id) ON DELETE CASCADE,
    PRIMARY KEY (article_id, tag_id)
);

CREATE TABLE IF NOT EXISTS redirects (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    old_url TEXT NOT NULL UNIQUE,
    new_url TEXT NOT NULL,
    status_code INTEGER NOT NULL DEFAULT 301,
    active INTEGER NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS settings (
    key TEXT PRIMARY KEY,
    value TEXT
);

CREATE TABLE IF NOT EXISTS login_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ip TEXT NOT NULL,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_login_attempts_ip_time ON login_attempts(ip, attempted_at);
