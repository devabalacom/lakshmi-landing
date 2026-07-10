<?php
require_once __DIR__ . '/db.php';

/** Transliterates Cyrillic and slugifies arbitrary text into a URL-safe slug. */
function slugify(string $text): string
{
    $map = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e', 'ж' => 'zh',
        'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
        'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts',
        'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
    ];
    $text = mb_strtolower($text, 'UTF-8');
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9]+/u', '-', $text) ?? '';
    $text = trim($text, '-');
    $text = preg_replace('/-+/', '-', $text) ?? '';
    return $text === '' ? 'article-' . bin2hex(random_bytes(4)) : $text;
}

/** Enforced at the point of save — never trust the rewrite regex alone. */
function is_valid_slug(string $slug): bool
{
    return (bool) preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug);
}

function unique_slug(PDO $db, string $base, ?int $excludeId = null): string
{
    $slug = $base;
    $i = 2;
    while (true) {
        $sql = 'SELECT COUNT(*) AS c FROM articles WHERE slug = :slug';
        $params = ['slug' => $slug];
        if ($excludeId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $excludeId;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        if ((int) $stmt->fetch()['c'] === 0) {
            return $slug;
        }
        $slug = $base . '-' . $i;
        $i++;
    }
}

function estimate_reading_minutes(string $html): int
{
    $text = strip_tags($html);
    $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY);
    $count = is_array($words) ? count($words) : 0;
    return max(1, (int) ceil($count / 200));
}

/** Allows http(s)/mailto/tel/relative/anchor URLs only — blocks javascript:/data: etc. */
function is_safe_url(string $url): bool
{
    $url = trim($url);
    if ($url === '') {
        return false;
    }
    if (str_starts_with($url, '#') || str_starts_with($url, '/')) {
        return true;
    }
    return (bool) preg_match('#^(https?|mailto|tel):#i', $url);
}

/**
 * Allowlist-based inline HTML sanitizer for text coming out of Editor.js's
 * inline toolbar (bold/italic/link/marker/code). Disallowed tags are unwrapped
 * (text kept, markup dropped) rather than the whole fragment being rejected.
 */
function sanitize_inline_html(string $html): string
{
    $allowedTags = ['b', 'strong', 'i', 'em', 'a', 'mark', 'code', 'br'];
    if (trim($html) === '') {
        return '';
    }

    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML(
        '<?xml encoding="UTF-8"><root>' . $html . '</root>',
        LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED
    );
    libxml_clear_errors();

    $root = $doc->getElementsByTagName('root')->item(0);
    if (!$root) {
        return htmlspecialchars(strip_tags($html), ENT_QUOTES, 'UTF-8');
    }
    return sanitize_node_children($root, $allowedTags);
}

function sanitize_node_children(DOMNode $node, array $allowedTags): string
{
    $out = '';
    foreach ($node->childNodes as $child) {
        $out .= sanitize_node($child, $allowedTags);
    }
    return $out;
}

function sanitize_node(DOMNode $node, array $allowedTags): string
{
    if ($node->nodeType === XML_TEXT_NODE) {
        return htmlspecialchars($node->textContent, ENT_QUOTES, 'UTF-8');
    }
    if ($node->nodeType !== XML_ELEMENT_NODE) {
        return '';
    }

    $tag = strtolower($node->nodeName);
    $inner = sanitize_node_children($node, $allowedTags);

    if (!in_array($tag, $allowedTags, true)) {
        return $inner; // unwrap disallowed tag, keep its text
    }
    if ($tag === 'br') {
        return '<br>';
    }
    if ($tag === 'a') {
        $href = $node instanceof DOMElement ? $node->getAttribute('href') : '';
        if (!is_safe_url($href)) {
            return $inner; // strip the link but keep the text
        }
        $safeHref = htmlspecialchars($href, ENT_QUOTES, 'UTF-8');
        return "<a href=\"{$safeHref}\" rel=\"noopener\">{$inner}</a>";
    }
    return "<{$tag}>{$inner}</{$tag}>";
}

function find_media_by_id(int $id): ?array
{
    $stmt = blog_db()->prepare('SELECT * FROM media WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Renders one Editor.js block to HTML. H1 is intentionally never an option —
 * `header` blocks are clamped to levels 2-4, so a second H1 is structurally
 * impossible regardless of what the client sends.
 */
function render_editorjs_block(array $block): string
{
    $type = $block['type'] ?? '';
    $data = $block['data'] ?? [];

    switch ($type) {
        case 'paragraph':
            $text = sanitize_inline_html((string) ($data['text'] ?? ''));
            return $text === '' ? '' : "<p>{$text}</p>\n";

        case 'header':
            $level = (int) ($data['level'] ?? 2);
            $level = max(2, min(4, $level));
            $text = sanitize_inline_html((string) ($data['text'] ?? ''));
            return $text === '' ? '' : "<h{$level}>{$text}</h{$level}>\n";

        case 'list':
            $tag = (($data['style'] ?? 'unordered') === 'ordered') ? 'ol' : 'ul';
            $items = is_array($data['items'] ?? null) ? $data['items'] : [];
            $lis = '';
            foreach ($items as $item) {
                $content = is_array($item) ? ($item['content'] ?? '') : $item;
                $lis .= '<li>' . sanitize_inline_html((string) $content) . "</li>\n";
            }
            return $lis === '' ? '' : "<{$tag}>\n{$lis}</{$tag}>\n";

        case 'quote':
            $text = sanitize_inline_html((string) ($data['text'] ?? ''));
            if ($text === '') {
                return '';
            }
            $caption = trim((string) ($data['caption'] ?? ''));
            $captionHtml = $caption !== '' ? '<cite>' . htmlspecialchars($caption, ENT_QUOTES, 'UTF-8') . '</cite>' : '';
            return "<blockquote class=\"pull-quote\">{$text}{$captionHtml}</blockquote>\n";

        case 'delimiter':
            return "<hr class=\"article-divider\">\n";

        case 'table':
            $rows = is_array($data['content'] ?? null) ? $data['content'] : [];
            if (empty($rows)) {
                return '';
            }
            $withHeadings = !empty($data['withHeadings']);
            $out = "<div class=\"article-table-wrap\"><table class=\"article-table\">\n";
            foreach ($rows as $i => $row) {
                $cellTag = ($withHeadings && $i === 0) ? 'th' : 'td';
                $out .= '<tr>';
                foreach ((array) $row as $cell) {
                    $out .= "<{$cellTag}>" . sanitize_inline_html((string) $cell) . "</{$cellTag}>";
                }
                $out .= "</tr>\n";
            }
            $out .= "</table></div>\n";
            return $out;

        case 'image':
            $url = (string) ($data['file']['url'] ?? '');
            if ($url === '') {
                return '';
            }
            $mediaId = $data['file']['id'] ?? null;
            $alt = '';
            if ($mediaId !== null) {
                $media = find_media_by_id((int) $mediaId);
                $alt = $media['alt'] ?? '';
            }
            $caption = trim((string) ($data['caption'] ?? ''));
            if ($alt === '') {
                $alt = $caption;
            }
            $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
            $safeAlt = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');
            $captionHtml = $caption !== '' ? '<figcaption>' . htmlspecialchars($caption, ENT_QUOTES, 'UTF-8') . '</figcaption>' : '';
            return "<figure class=\"article-inline-img\"><img src=\"{$safeUrl}\" alt=\"{$safeAlt}\" loading=\"lazy\">{$captionHtml}</figure>\n";

        case 'cta':
            $heading = htmlspecialchars((string) ($data['heading'] ?? ''), ENT_QUOTES, 'UTF-8');
            $text = htmlspecialchars((string) ($data['text'] ?? ''), ENT_QUOTES, 'UTF-8');
            $buttonText = htmlspecialchars((string) ($data['buttonText'] ?? 'Позвонить'), ENT_QUOTES, 'UTF-8');
            $buttonHref = (string) ($data['buttonHref'] ?? '');
            $buttonHref = is_safe_url($buttonHref) ? $buttonHref : '';
            $safeHref = htmlspecialchars($buttonHref, ENT_QUOTES, 'UTF-8');
            if ($heading === '' && $text === '') {
                return '';
            }
            return "<div class=\"article-cta\"><h3>{$heading}</h3><p>{$text}</p><a href=\"{$safeHref}\" class=\"btn btn-gold\">{$buttonText}</a></div>\n";

        default:
            return '';
    }
}

function render_editorjs_to_html(string $contentJson): string
{
    $data = json_decode($contentJson, true);
    if (!is_array($data) || empty($data['blocks']) || !is_array($data['blocks'])) {
        return '';
    }
    $html = '';
    foreach ($data['blocks'] as $block) {
        if (is_array($block)) {
            $html .= render_editorjs_block($block);
        }
    }
    return $html;
}

// --- SEO fallback chains (spec: empty field falls back, never silently overwritten in the DB) ---

function seo_effective_title(array $article): string
{
    $v = trim((string) ($article['seo_title'] ?? ''));
    return $v !== '' ? $v : (string) $article['title'];
}

function seo_effective_description(array $article): string
{
    $v = trim((string) ($article['meta_description'] ?? ''));
    return $v !== '' ? $v : (string) ($article['excerpt'] ?? '');
}

function seo_effective_h1(array $article): string
{
    $v = trim((string) ($article['h1'] ?? ''));
    return $v !== '' ? $v : (string) $article['title'];
}

function seo_effective_canonical(array $article, string $currentUrl): string
{
    $v = trim((string) ($article['canonical_url'] ?? ''));
    return $v !== '' ? $v : $currentUrl;
}

const RU_MONTHS_GENITIVE = [
    1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля', 5 => 'мая', 6 => 'июня',
    7 => 'июля', 8 => 'августа', 9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря',
];

/** Formats "2026-06-18[ ...]" as "18 июня 2026" to match the site's existing date style. */
function format_ru_date(?string $dateString): string
{
    if (!$dateString) {
        return '';
    }
    $ts = strtotime($dateString);
    if ($ts === false) {
        return '';
    }
    $day = (int) date('j', $ts);
    $month = RU_MONTHS_GENITIVE[(int) date('n', $ts)];
    $year = date('Y', $ts);
    return "{$day} {$month} {$year}";
}
