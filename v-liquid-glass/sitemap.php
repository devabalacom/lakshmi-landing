<?php
require_once __DIR__ . '/includes/queries.php';

header('Content-Type: application/xml; charset=UTF-8');

$db = blog_db();
flip_scheduled_articles($db);

$siteBase = 'https://пром-текстиль.рф';

// Static pages — unchanged from the previous hand-maintained sitemap.xml.
$staticUrls = [
    ['loc' => $siteBase . '/', 'lastmod' => '2026-07-02', 'changefreq' => 'weekly', 'priority' => '1.0'],
    ['loc' => $siteBase . '/pages/about-v2.html', 'lastmod' => '2026-07-02', 'changefreq' => 'monthly', 'priority' => '0.8'],
    ['loc' => $siteBase . '/pages/contacts-v2.html', 'lastmod' => '2026-07-02', 'changefreq' => 'monthly', 'priority' => '0.8'],
    ['loc' => $siteBase . '/privacy.html', 'lastmod' => '2026-07-02', 'changefreq' => 'yearly', 'priority' => '0.3'],
    ['loc' => $siteBase . '/sitemap.html', 'lastmod' => '2026-07-09', 'changefreq' => 'monthly', 'priority' => '0.4'],
    ['loc' => $siteBase . '/pages/blog-v2.html', 'lastmod' => date('Y-m-d'), 'changefreq' => 'weekly', 'priority' => '0.7'],
    ['loc' => $siteBase . '/pages/cases-v2.html', 'lastmod' => '2026-08-03', 'changefreq' => 'monthly', 'priority' => '0.8'],
    ['loc' => $siteBase . '/cases/rzd-tents.html', 'lastmod' => '2026-08-03', 'changefreq' => 'monthly', 'priority' => '0.7'],
    ['loc' => $siteBase . '/cases/neftegaz-specodezhda.html', 'lastmod' => '2026-08-03', 'changefreq' => 'monthly', 'priority' => '0.7'],
    ['loc' => $siteBase . '/cases/wb-tactical.html', 'lastmod' => '2026-08-03', 'changefreq' => 'monthly', 'priority' => '0.7'],
    ['loc' => $siteBase . '/cases/premium-auto.html', 'lastmod' => '2026-08-03', 'changefreq' => 'monthly', 'priority' => '0.7'],
    ['loc' => $siteBase . '/services/chekhly-tenty-v2.html', 'lastmod' => '2026-07-02', 'changefreq' => 'monthly', 'priority' => '0.9'],
    ['loc' => $siteBase . '/services/specodezhda-v2.html', 'lastmod' => '2026-07-02', 'changefreq' => 'monthly', 'priority' => '0.9'],
    ['loc' => $siteBase . '/services/tactical-v2.html', 'lastmod' => '2026-07-02', 'changefreq' => 'monthly', 'priority' => '0.9'],
    ['loc' => $siteBase . '/services/medical-v2.html', 'lastmod' => '2026-07-02', 'changefreq' => 'monthly', 'priority' => '0.9'],
    ['loc' => $siteBase . '/services/interior-v2.html', 'lastmod' => '2026-07-02', 'changefreq' => 'monthly', 'priority' => '0.9'],
    ['loc' => $siteBase . '/services/transport-v2.html', 'lastmod' => '2026-07-02', 'changefreq' => 'monthly', 'priority' => '0.9'],
    ['loc' => $siteBase . '/services/agro-v2.html', 'lastmod' => '2026-07-02', 'changefreq' => 'monthly', 'priority' => '0.9'],
    ['loc' => $siteBase . '/services/fire-v2.html', 'lastmod' => '2026-07-02', 'changefreq' => 'monthly', 'priority' => '0.9'],
    ['loc' => $siteBase . '/services/cleanroom-v2.html', 'lastmod' => '2026-07-02', 'changefreq' => 'monthly', 'priority' => '0.9'],
];

$stmt = $db->query(
    "SELECT slug, updated_at, sitemap_priority, sitemap_changefreq
     FROM articles WHERE status = 'published' AND include_in_sitemap = 1"
);
$articleUrls = [];
foreach ($stmt->fetchAll() as $row) {
    $articleUrls[] = [
        'loc' => $siteBase . '/pages/blog-' . $row['slug'] . '.html',
        'lastmod' => date('Y-m-d', strtotime($row['updated_at'])),
        'changefreq' => $row['sitemap_changefreq'],
        'priority' => number_format((float) $row['sitemap_priority'], 1),
    ];
}

$allUrls = array_merge($staticUrls, $articleUrls);

$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" ';
$xml .= 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ';
$xml .= 'xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n";
foreach ($allUrls as $u) {
    $xml .= "    <url>\n";
    $xml .= '        <loc>' . htmlspecialchars($u['loc'], ENT_QUOTES | ENT_XML1, 'UTF-8') . "</loc>\n";
    $xml .= '        <lastmod>' . htmlspecialchars($u['lastmod'], ENT_QUOTES | ENT_XML1, 'UTF-8') . "</lastmod>\n";
    $xml .= '        <changefreq>' . htmlspecialchars($u['changefreq'], ENT_QUOTES | ENT_XML1, 'UTF-8') . "</changefreq>\n";
    $xml .= '        <priority>' . htmlspecialchars($u['priority'], ENT_QUOTES | ENT_XML1, 'UTF-8') . "</priority>\n";
    $xml .= "    </url>\n";
}
$xml .= '</urlset>';

echo $xml;
