<?php
/**
 * One-off CLI migration: parses the 6 hand-written static blog post files
 * (pages/blog-*.html, excluding blog-v2.html which is the listing page) into
 * the articles table, so they become fully editable through /admin/.
 *
 * Idempotent — re-running skips any slug that's already in the DB, so it's
 * safe to run multiple times while debugging.
 *
 * Usage: php scripts/migrate-legacy-posts.php
 */

require_once __DIR__ . '/../includes/queries.php';

const LEGACY_FILES = [
    'blog-tkani-dlya-tentov.html',
    'blog-sertifikaciya-specodezhdy.html',
    'blog-proizvodstvenny-cikl.html',
    'blog-takticheskie-materialy.html',
    'blog-antibakterialnye-tkani.html',
    'blog-ognestoykie-tkani.html',
];

function dom_inner_html(DOMElement $el): string
{
    $html = '';
    $doc = $el->ownerDocument;
    foreach ($el->childNodes as $child) {
        $html .= $doc->saveHTML($child);
    }
    return trim($html);
}

function dom_query(DOMXPath $xpath, string $query, ?DOMNode $context = null): DOMNodeList
{
    return $context ? $xpath->query($query, $context) : $xpath->query($query);
}

/** Copies a local image file into the media library (used only by this migration). */
function copy_local_image_to_media(string $sourcePath, string $altText): ?array
{
    if (!is_file($sourcePath)) {
        echo "  ! Source image not found: $sourcePath\n";
        return null;
    }
    $imageInfo = @getimagesize($sourcePath);
    if ($imageInfo === false) {
        echo "  ! Not a valid image: $sourcePath\n";
        return null;
    }
    [$width, $height, $type] = $imageInfo;

    $source = match ($type) {
        IMAGETYPE_JPEG => imagecreatefromjpeg($sourcePath),
        IMAGETYPE_PNG => imagecreatefrompng($sourcePath),
        IMAGETYPE_WEBP => imagecreatefromwebp($sourcePath),
        default => false,
    };
    if ($source === false) {
        echo "  ! Unsupported image type: $sourcePath\n";
        return null;
    }

    $maxDim = 1600;
    $scale = min(1, $maxDim / max($width, $height));
    $targetWidth = max(1, (int) round($width * $scale));
    $targetHeight = max(1, (int) round($height * $scale));

    if ($scale < 1) {
        $resized = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
    } else {
        $resized = $source;
    }

    $targetDir = dirname(__DIR__) . '/uploads/blog/legacy';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    $filename = bin2hex(random_bytes(8)) . '.webp';
    $fullPath = $targetDir . '/' . $filename;
    imagewebp($resized, $fullPath, 82);

    $db = blog_db();
    $stmt = $db->prepare(
        'INSERT INTO media (filename, original_filename, path, mime_type, file_size, width, height, alt)
         VALUES (:filename, :original, :path, :mime, :size, :width, :height, :alt)'
    );
    $stmt->execute([
        'filename' => $filename,
        'original' => basename($sourcePath),
        'path' => 'legacy/' . $filename,
        'mime' => 'image/webp',
        'size' => filesize($fullPath),
        'width' => $targetWidth,
        'height' => $targetHeight,
        'alt' => $altText,
    ]);
    $id = (int) $db->lastInsertId();
    $stmt = $db->prepare('SELECT * FROM media WHERE id = :id');
    $stmt->execute(['id' => $id]);
    return $stmt->fetch();
}

function parse_legacy_post(string $filePath): array
{
    $html = file_get_contents($filePath);
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();
    $xpath = new DOMXPath($doc);

    $title = trim(dom_query($xpath, '//h1[@class="article-h1"]')->item(0)->textContent);
    $metaDescEl = $xpath->query('//meta[@name="description"]')->item(0);
    $metaDescription = $metaDescEl ? $metaDescEl->getAttribute('content') : '';
    $category = trim($xpath->query('//span[@class="article-cat"]')->item(0)->textContent);

    $timeEl = $xpath->query('//div[@class="article-meta"]//time')->item(0);
    $dateIso = $timeEl ? $timeEl->getAttribute('datetime') : date('Y-m-d');

    $readingText = trim($xpath->query('//div[@class="article-meta"]/span')->item(0)->textContent ?? '');
    preg_match('/(\d+)/', $readingText, $m);
    $readingMinutes = isset($m[1]) ? (int) $m[1] : 5;

    $heroImgEl = $xpath->query('//div[@class="article-hero-img"]//img')->item(0);
    $heroSrc = $heroImgEl ? $heroImgEl->getAttribute('src') : null; // relative "../img/xxx.jpg"
    $heroAlt = $heroImgEl ? $heroImgEl->getAttribute('alt') : $title;

    $proseEl = $xpath->query('//article[@class="article-prose"]')->item(0);
    $blocks = [];
    $cta = null;

    foreach ($proseEl->childNodes as $node) {
        if (!($node instanceof DOMElement)) {
            continue;
        }
        $tag = strtolower($node->nodeName);
        $class = $node->getAttribute('class');

        if ($tag === 'p') {
            $blocks[] = ['type' => 'paragraph', 'data' => ['text' => dom_inner_html($node)]];
        } elseif ($tag === 'h2') {
            $blocks[] = ['type' => 'header', 'data' => ['text' => dom_inner_html($node), 'level' => 2]];
        } elseif ($tag === 'ul') {
            $items = [];
            foreach ($node->getElementsByTagName('li') as $li) {
                $items[] = dom_inner_html($li);
            }
            $blocks[] = ['type' => 'list', 'data' => ['style' => 'unordered', 'items' => $items]];
        } elseif ($tag === 'blockquote' && str_contains($class, 'pull-quote')) {
            $blocks[] = ['type' => 'quote', 'data' => ['text' => dom_inner_html($node), 'caption' => '']];
        } elseif ($tag === 'div' && str_contains($class, 'article-cta')) {
            $h3 = $node->getElementsByTagName('h3')->item(0);
            $p = $node->getElementsByTagName('p')->item(0);
            $a = $node->getElementsByTagName('a')->item(0);
            $cta = [
                'type' => 'cta',
                'data' => [
                    'heading' => $h3 ? trim($h3->textContent) : '',
                    'text' => $p ? trim($p->textContent) : '',
                    'buttonText' => $a ? trim($a->textContent) : 'Позвонить →',
                    'buttonHref' => $a ? $a->getAttribute('href') : 'tel:+79818172649',
                ],
            ];
        }
        // 'a.back-link' and anything else is intentionally skipped — the template
        // always appends its own back-link, so it must not become a content block.
    }
    if ($cta) {
        $blocks[] = $cta;
    }

    return [
        'title' => $title,
        'meta_description' => $metaDescription,
        'category' => $category,
        'date_iso' => $dateIso,
        'reading_minutes' => $readingMinutes,
        'hero_src' => $heroSrc,
        'hero_alt' => $heroAlt,
        'blocks' => $blocks,
    ];
}

$db = blog_db();
$pagesDir = dirname(__DIR__) . '/pages';
$imgDir = dirname(__DIR__) . '/img';

foreach (LEGACY_FILES as $filename) {
    $slug = preg_replace('/^blog-/', '', preg_replace('/\.html$/', '', $filename));
    echo "Processing $filename (slug: $slug)...\n";

    $existing = get_article_by_slug_any_status($db, $slug);
    if ($existing) {
        echo "  - Already migrated (article #{$existing['id']}), skipping.\n";
        continue;
    }

    $filePath = $pagesDir . '/' . $filename;
    if (!is_file($filePath)) {
        echo "  ! File not found, skipping: $filePath\n";
        continue;
    }

    $parsed = parse_legacy_post($filePath);

    $categoryId = find_or_create_category($db, $parsed['category']);

    $coverMediaId = null;
    if ($parsed['hero_src']) {
        $heroAbsPath = realpath($pagesDir . '/' . $parsed['hero_src']); // "../img/xxx.jpg" relative to pages/
        if ($heroAbsPath) {
            $media = copy_local_image_to_media($heroAbsPath, $parsed['hero_alt']);
            $coverMediaId = $media ? (int) $media['id'] : null;
        }
    }

    $contentJson = json_encode(
        ['time' => strtotime($parsed['date_iso']) * 1000, 'blocks' => $parsed['blocks'], 'version' => '2.29.1'],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    $contentHtml = render_editorjs_to_html($contentJson);

    $publishedAt = $parsed['date_iso'] . ' 12:00:00';

    $stmt = $db->prepare(
        'INSERT INTO articles (
            title, slug, excerpt, content_json, content_html, category_id, author,
            cover_media_id, reading_time_minutes, status, published_at, created_at, updated_at,
            meta_description, robots_index, robots_follow, schema_type,
            include_in_sitemap, sitemap_priority, sitemap_changefreq
        ) VALUES (
            :title, :slug, :excerpt, :content_json, :content_html, :category_id, :author,
            :cover_media_id, :reading_time_minutes, :status, :published_at, :created_at, :updated_at,
            :meta_description, 1, 1, :schema_type,
            1, 0.6, :sitemap_changefreq
        )'
    );
    $stmt->execute([
        'title' => $parsed['title'],
        'slug' => $slug,
        'excerpt' => $parsed['meta_description'],
        'content_json' => $contentJson,
        'content_html' => $contentHtml,
        'category_id' => $categoryId,
        'author' => 'Пром-текстиль',
        'cover_media_id' => $coverMediaId,
        'reading_time_minutes' => $parsed['reading_minutes'],
        'status' => 'published',
        'published_at' => $publishedAt,
        'created_at' => $publishedAt,
        'updated_at' => $publishedAt,
        'meta_description' => $parsed['meta_description'],
        'schema_type' => 'Article',
        'sitemap_changefreq' => 'monthly',
    ]);

    $newId = (int) $db->lastInsertId();
    echo "  + Migrated as article #$newId (slug: $slug, category: {$parsed['category']}, blocks: " . count($parsed['blocks']) . ")\n";
}

echo "Done.\n";
