<?php
require_once dirname(__DIR__) . '/includes/queries.php';
require_once __DIR__ . '/../admin/includes/session.php';

$slug = $_GET['slug'] ?? '';
$db = blog_db();

$article = get_public_article_by_slug($db, $slug);
$isPreview = false;

if (!$article) {
    // Only an authenticated admin can preview a draft/scheduled article by its would-be URL.
    $candidate = get_article_by_slug_any_status($db, $slug);
    if ($candidate && admin_is_logged_in()) {
        $article = $candidate;
        $isPreview = true;
    }
}

if (!$article) {
    $requestPath = '/pages/blog-' . $slug . '.html';
    $redirect = find_active_redirect($db, $requestPath);
    if ($redirect) {
        http_response_code($redirect['status_code']);
        header('Location: ' . $redirect['new_url']);
        exit;
    }
    http_response_code(404);
    ?>
    <!doctype html><html lang="ru"><head><meta charset="UTF-8"><meta name="robots" content="noindex"><title>Статья не найдена · Пром-текстиль</title></head>
    <body style="font-family:sans-serif;text-align:center;padding:4rem 1rem">
        <h1>Статья не найдена</h1>
        <p><a href="/pages/blog-v2.html">← Вернуться в блог</a></p>
    </body></html>
    <?php
    exit;
}

$siteBase = 'https://пром-текстиль.рф';
$publicUrl = $siteBase . article_public_url($article);
$tags = get_article_tags($db, (int) $article['id']);

$effectiveTitle = seo_effective_title($article);
$effectiveDescription = seo_effective_description($article);
if ($effectiveDescription === '') {
    $effectiveDescription = get_setting($db, 'default_meta_description', '');
}
$effectiveH1 = seo_effective_h1($article);
$effectiveCanonical = seo_effective_canonical($article, $publicUrl);

$isHidden = $article['status'] === 'hidden';
$noindex = $isHidden || !$article['robots_index'];
$nofollow = !$article['robots_follow'];
$robotsContent = ($noindex ? 'noindex' : 'index') . ', ' . ($nofollow ? 'nofollow' : 'follow');

$defaultOgImage = get_setting($db, 'default_og_image', '/og-image.svg');
$defaultOgImageAbs = str_starts_with($defaultOgImage, 'http') ? $defaultOgImage : $siteBase . $defaultOgImage;
$coverUrl = $article['cover_path'] ? $siteBase . '/uploads/blog/' . $article['cover_path'] : $defaultOgImageAbs;
$coverAlt = $article['cover_alt'] ?: $effectiveTitle;

$ogTitle = trim((string) ($article['og_title'] ?? '')) ?: $effectiveTitle;
$ogDescription = trim((string) ($article['og_description'] ?? '')) ?: $effectiveDescription;
$ogImage = $article['og_image_id'] ? (function () use ($article, $siteBase) {
    $m = find_media_by_id((int) $article['og_image_id']);
    return $m ? $siteBase . '/uploads/blog/' . $m['path'] : null;
})() : $coverUrl;

$twitterTitle = trim((string) ($article['twitter_title'] ?? '')) ?: $effectiveTitle;
$twitterDescription = trim((string) ($article['twitter_description'] ?? '')) ?: $effectiveDescription;
$twitterImage = $article['twitter_image_id'] ? (function () use ($article, $siteBase) {
    $m = find_media_by_id((int) $article['twitter_image_id']);
    return $m ? $siteBase . '/uploads/blog/' . $m['path'] : null;
})() : $coverUrl;

$publishedIso = $article['published_at'] ?? $article['publish_at'] ?? $article['created_at'];
$modifiedIso = $article['updated_at'];
$categoryName = $article['category_name'] ?? 'Блог';
$readingMinutes = $article['reading_time_minutes'] ?: 1;

$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => $article['schema_type'] ?: 'Article',
    'headline' => $effectiveTitle,
    'image' => $coverUrl,
    'datePublished' => date('c', strtotime($publishedIso)),
    'dateModified' => date('c', strtotime($modifiedIso)),
    'author' => ['@type' => 'Organization', 'name' => $article['author'] ?: 'Пром-текстиль'],
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'Пром-текстиль',
        'logo' => ['@type' => 'ImageObject', 'url' => $siteBase . '/og-image.svg'],
    ],
    'mainEntityOfPage' => $publicUrl,
];
if ($tags) {
    $jsonLd['keywords'] = implode(', ', $tags);
}

$breadcrumbLd = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Главная', 'item' => $siteBase . '/'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Блог', 'item' => $siteBase . '/pages/blog-v2.html'],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $effectiveTitle, 'item' => $publicUrl],
    ],
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <!-- Yandex.Metrika counter -->
    <script type="text/javascript">
        (function(m,e,t,r,i,k,a){
            m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
            m[i].l=1*new Date();
            for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
            k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
        })(window, document,'script','https://mc.yandex.ru/metrika/tag.js?id=110733856', 'ym');

        ym(110733856, 'init', {ssr:true, webvisor:true, clickmap:true, ecommerce:"dataLayer", referrer: document.referrer, url: location.href, accurateTrackBounce:true, trackLinks:true});
    </script>
    <noscript><div><img src="https://mc.yandex.ru/watch/110733856" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
    <!-- /Yandex.Metrika counter -->
    <meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($effectiveTitle, ENT_QUOTES, 'UTF-8') ?> · Блог Пром-текстиль</title>
    <meta name="description" content="<?= htmlspecialchars($effectiveDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="theme-color" content="#D4A017">
    <link rel="canonical" href="<?= htmlspecialchars($effectiveCanonical, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="robots" content="<?= htmlspecialchars($robotsContent, ENT_QUOTES, 'UTF-8') ?>, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="author" content="<?= htmlspecialchars($article['author'] ?: 'Пром-текстиль', ENT_QUOTES, 'UTF-8') ?>">
    <meta name="geo.region" content="RU-SPE">
    <meta name="geo.placename" content="Saint Petersburg, Russia">
    <meta name="geo.position" content="59.9869;30.3389">
    <meta name="ICBM" content="59.9869, 30.3389">

    <meta property="og:type" content="article">
    <meta property="og:locale" content="ru_RU">
    <meta property="og:site_name" content="Пром-текстиль">
    <meta property="og:url" content="<?= htmlspecialchars($publicUrl, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:title" content="<?= htmlspecialchars($ogTitle, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($ogDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image" content="<?= htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="article:published_time" content="<?= htmlspecialchars(date('c', strtotime($publishedIso)), ENT_QUOTES, 'UTF-8') ?>">
    <meta property="article:modified_time" content="<?= htmlspecialchars(date('c', strtotime($modifiedIso)), ENT_QUOTES, 'UTF-8') ?>">
    <meta property="article:author" content="<?= htmlspecialchars($article['author'] ?: 'Пром-текстиль', ENT_QUOTES, 'UTF-8') ?>">
    <meta property="article:section" content="<?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') ?>">
    <?php foreach ($tags as $tag): ?>
    <meta property="article:tag" content="<?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') ?>">
    <?php endforeach; ?>

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($twitterTitle, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($twitterDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($twitterImage, ENT_QUOTES, 'UTF-8') ?>">

    <meta name="yandex-verification" content="cc20bc028820e8f1">
    <meta name="google-site-verification" content="2SUr4ki9IVYciPxgnPJejZgrx7lgREzFV9vVkDza-5Y">
    <script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE) ?></script>
    <script type="application/ld+json"><?= json_encode($breadcrumbLd, JSON_UNESCAPED_UNICODE) ?></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400&display=swap" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400&display=swap"></noscript>
    <style>
        :root {
            --bg:    #F8FAFC; --bg2:   #EEF2F6; --bg3:   #E2E8F0; --card:  #FFFFFF; --border:  #CBD5E1;
            --ink:       #1E293B; --ink2:      #475569; --ink3:      #64748B; --text-soft: #94A3B8;
            --g0:    #D4A017; --g1:    #FACC15; --g2:    #8B6508;
            --ga:    rgba(212,160,23,0.09); --gl:    rgba(212,160,23,0.16); --glh:   rgba(212,160,23,0.38);
            --accent-hover:  #B8860B; --accent-active: #8B6508; --accent-bg:     #FFFBEB; --accent-border: #FDE68A;
            --nr:    #0F172A; --nr2:   #1D1914; --nt:    #F8FAFC; --nt2:   rgba(248,250,252,0.65); --dark-muted: #CBD5E1;
            --gbg:   rgba(255,255,255,0.68); --gbg2:  rgba(255,255,255,0.86); --gbd:   rgba(212,160,23,0.18);
            --gsh:   0 8px 32px rgba(26,22,18,0.07), 0 1px 3px rgba(26,22,18,0.04);
            --fd: 'Montserrat', sans-serif; --fs: 'Montserrat', sans-serif; --fb: 'Montserrat', sans-serif; --fm: 'Montserrat', monospace;
            --rs:  6px; --r:   16px; --rl:  24px; --rp:  999px;
            --ease: cubic-bezier(0.22, 1, 0.36, 1);
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; background: var(--bg); }
        body { background: var(--bg); color: var(--ink); font-family: var(--fb); font-weight: 400; line-height: 1.65; -webkit-font-smoothing: antialiased; overflow-x: hidden; }
        a { color: inherit; text-decoration: none; }
        ul { list-style: none; }
        button { cursor: pointer; border: 0; font: inherit; background: none; }
        :focus-visible { outline: 2px solid var(--g0); outline-offset: 3px; border-radius: 4px; }
        .progress { position: fixed; top: 0; left: 0; height: 2px; width: 0; background: linear-gradient(90deg, var(--g2), var(--g0), var(--g1)); z-index: 300; transition: width .08s linear; pointer-events: none; }
        .shell { max-width: 1400px; margin: 0 auto; padding: 0 1.5rem; }
        @media(min-width:768px) { .shell { padding: 0 3rem; } }
        .nav { position: fixed; top: 0; left: 0; right: 0; z-index: 100; padding: 1.2rem 0; background: white; border-bottom: 1px solid var(--bg3); transition: padding .4s var(--ease); }
        .nav.solid { padding: .85rem 0; }
        .nav-inner { display: flex; align-items: center; justify-content: space-between; }
        .brand { display: flex; align-items: center; gap: .75rem; }
        .brand-name { font-weight: 800; font-size: .95rem; letter-spacing: .01em; color: var(--ink); }
        .nav-links { display: none; align-items: center; gap: 2.2rem; }
        @media(min-width:900px) { .nav-links { display: flex; } }
        .nav-links a { font-size: .72rem; font-weight: 600; color: var(--ink3); letter-spacing: .08em; text-transform: uppercase; transition: color .25s; position: relative; padding: .2rem 0; }
        .nav-links a::after { content: ''; position: absolute; left: 0; right: 100%; bottom: -2px; height: 1px; background: var(--g0); transition: right .35s var(--ease); }
        .nav-links a:hover { color: var(--g2); }
        .nav-links a:hover::after { right: 0; }
        .nav-links a[aria-current="page"] { color: var(--g2); }
        .nav-links a[aria-current="page"]::after { right: 0; }
        .nav-cta { display: none; padding: .55rem 1.4rem; border-radius: var(--rp); border: 1.5px solid var(--glh); font-size: .72rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--g0); transition: background .3s, color .3s, border-color .3s, transform .25s; }
        @media(min-width:920px) { .nav-cta { display: inline-flex; } }
        .nav-cta:hover { background: var(--g0); border-color: var(--g0); color: white; transform: translateY(-1px); }
        .nav-burger { display: flex; flex-direction: column; gap: 5px; width: 36px; height: 36px; justify-content: center; padding: 4px; }
        .nav-burger span { display: block; width: 100%; height: 1.5px; background: var(--ink); border-radius: 2px; transition: transform .3s, opacity .3s; }
        .nav-burger.open span:nth-child(1) { transform: translateY(6.5px) rotate(45deg); }
        .nav-burger.open span:nth-child(2) { opacity: 0; }
        .nav-burger.open span:nth-child(3) { transform: translateY(-6.5px) rotate(-45deg); }
        @media(min-width:900px) { .nav-burger { display: none; } }
        .nav-mobile { display: none; position: fixed; inset: 0; top: 58px; background: rgba(253,250,245,0.97); backdrop-filter: blur(40px); -webkit-backdrop-filter: blur(40px); padding: 2.5rem 2rem; flex-direction: column; gap: .2rem; z-index: 99; }
        .nav-mobile.open { display: flex; }
        .nav-mobile a { padding: 1rem 0; font-size: 1.3rem; font-weight: 800; color: var(--ink2); border-bottom: 1px solid var(--bg3); transition: color .25s, padding-left .25s; }
        .nav-mobile a:hover { color: var(--g0); padding-left: .5rem; }
        .nav-dropdown { position: relative; }
        .dropdown-menu { display: none; position: absolute; top: calc(100% + 1.2rem); left: -1rem; min-width: 240px; background: var(--gbg2); backdrop-filter: blur(24px) saturate(1.3); -webkit-backdrop-filter: blur(24px) saturate(1.3); border: 1px solid var(--gbd); border-radius: var(--r); box-shadow: var(--gsh); padding: .5rem 0; z-index: 200; opacity: 0; transform: translateY(-8px); transition: opacity .25s var(--ease), transform .25s var(--ease), display .25s; pointer-events: none; }
        .dropdown-menu::before { content: ''; position: absolute; top: -1.2rem; left: 0; right: 0; height: 1.2rem; }
        .nav-dropdown:hover .dropdown-menu, .nav-dropdown:focus-within .dropdown-menu { display: block; opacity: 1; transform: none; pointer-events: auto; }
        .dropdown-menu a { display: block; padding: .6rem 1.2rem; font-size: .78rem; font-weight: 500; color: var(--ink2); transition: color .2s, background .2s; letter-spacing: .02em; text-transform: none; }
        .dropdown-menu a:hover { color: var(--g2); background: var(--ga); }
        .dropdown-menu a::after { display: none !important; }
        .nav-dd-arrow { display: inline-block; margin-left: .3rem; font-size: .6rem; transition: transform .25s var(--ease); vertical-align: middle; }
        .nav-dropdown:hover .nav-dd-arrow { transform: rotate(180deg); }
        .mnl-group { display: flex; flex-direction: column; }
        .mnl-toggle { padding: 1rem 0; font-size: 1.3rem; font-weight: 800; color: var(--ink2); border-bottom: 1px solid var(--bg3); transition: color .25s; cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
        .mnl-toggle:hover { color: var(--g0); }
        .mnl-sub { display: none; flex-direction: column; padding: .4rem 0 .8rem 1rem; }
        .mnl-sub.open { display: flex; }
        .mnl-sub a { padding: .55rem 0; font-size: .95rem; font-weight: 600; color: var(--ink3); border-bottom: none !important; transition: color .2s; }
        .mnl-sub a:hover { color: var(--g0); }
        .hero-section { background: white; padding: 90px 0 1rem; }
        .breadcrumbs { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; font-size: .78rem; color: var(--ink3); margin: 2.2rem 0 1.4rem; }
        .breadcrumbs a { color: var(--ink3); transition: color .25s; }
        .breadcrumbs a:hover { color: var(--g2); }
        .breadcrumbs span { color: var(--text-soft); }
        .article-head { max-width: 760px; }
        .article-cat { display: inline-block; font-family: var(--fm); font-size: .6rem; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: white; background: var(--g2); padding: .3rem .8rem; border-radius: var(--rp); margin-bottom: 1.1rem; }
        .article-h1 { font-family: var(--fd); font-size: clamp(1.9rem, 4vw, 3.1rem); font-weight: 400; line-height: 1.12; letter-spacing: -.01em; margin-bottom: 1.1rem; }
        .article-meta { display: flex; flex-wrap: wrap; gap: 1.2rem; font-size: .82rem; color: var(--ink3); padding-bottom: 2rem; border-bottom: 1px solid var(--bg3); }
        .article-meta time { color: var(--ink2); font-weight: 600; }
        .article-hero-img { margin-top: 2.2rem; border-radius: var(--rl); overflow: hidden; aspect-ratio: 21/9; background: var(--bg2); }
        .article-hero-img img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .section { padding: 3rem 0 6rem; position: relative; }
        @media(min-width:768px) { .section { padding: 3.5rem 0 8rem; } }
        .article-layout { display: grid; grid-template-columns: 1fr; gap: 3rem; max-width: 780px; }
        .article-prose h2 { font-family: var(--fd); font-size: clamp(1.3rem, 2.4vw, 1.7rem); font-weight: 500; line-height: 1.25; letter-spacing: -.005em; margin: 2.4rem 0 .9rem; }
        .article-prose h2:first-child { margin-top: 0; }
        .article-prose h3 { font-family: var(--fd); font-size: clamp(1.1rem, 2vw, 1.35rem); font-weight: 600; line-height: 1.3; margin: 2rem 0 .8rem; }
        .article-prose h4 { font-size: 1.02rem; font-weight: 700; margin: 1.6rem 0 .6rem; }
        .article-prose p { font-size: 1rem; color: var(--ink2); line-height: 1.8; margin-bottom: 1.2rem; }
        .article-prose ul, .article-prose ol { margin: 0 0 1.4rem; display: flex; flex-direction: column; gap: .55rem; padding-left: 0; }
        .article-prose ol { counter-reset: item; }
        .article-prose ul li { font-size: .96rem; color: var(--ink2); line-height: 1.65; padding-left: 1.5rem; position: relative; }
        .article-prose ul li::before { content: ''; position: absolute; left: 0; top: .55em; width: 6px; height: 6px; border-radius: 50%; background: var(--g0); }
        .article-prose ol li { font-size: .96rem; color: var(--ink2); line-height: 1.65; padding-left: 1.8rem; position: relative; counter-increment: item; }
        .article-prose ol li::before { content: counter(item) '.'; position: absolute; left: 0; top: 0; font-weight: 700; color: var(--g2); }
        .article-prose li strong { color: var(--ink); }
        .article-prose a { color: var(--g2); text-decoration: underline; }
        .pull-quote { margin: 2rem 0; padding: 1.6rem 1.8rem; background: var(--bg2); border-left: 3px solid var(--g0); border-radius: 0 var(--r) var(--r) 0; font-size: 1.05rem; font-weight: 500; color: var(--ink); line-height: 1.6; font-style: normal; }
        .pull-quote cite { display: block; margin-top: .6rem; font-size: .8rem; font-style: normal; color: var(--ink3); }
        .article-divider { border: none; border-top: 1px solid var(--bg3); margin: 2.6rem 0; }
        .article-table-wrap { overflow-x: auto; margin: 0 0 1.4rem; }
        .article-table { width: 100%; border-collapse: collapse; font-size: .92rem; }
        .article-table th, .article-table td { padding: .6rem .8rem; border: 1px solid var(--bg3); text-align: left; color: var(--ink2); }
        .article-table th { background: var(--bg2); color: var(--ink); font-weight: 700; }
        .article-inline-img { margin: 1.8rem 0; }
        .article-inline-img img { width: 100%; border-radius: var(--r); display: block; }
        .article-inline-img figcaption { font-size: .8rem; color: var(--ink3); margin-top: .5rem; text-align: center; }
        .article-cta { margin-top: 2.6rem; padding: 2.2rem; background: var(--nr); border-radius: var(--r); position: relative; overflow: hidden; }
        .article-cta::before { content: ''; position: absolute; inset: 0; background: radial-gradient(ellipse 65% 80% at 22% 30%, rgba(197,151,62,0.08) 0%, transparent 65%); pointer-events: none; }
        .article-cta h3 { position: relative; z-index: 1; color: var(--nt); font-size: 1.15rem; font-weight: 700; margin-bottom: .5rem; }
        .article-cta p { position: relative; z-index: 1; color: var(--nt2); font-size: .9rem; margin-bottom: 1.4rem; line-height: 1.6; }
        .btn { position: relative; z-index: 1; display: inline-flex; align-items: center; gap: .5rem; padding: .9rem 1.9rem; border-radius: var(--rp); font-weight: 700; font-size: .82rem; letter-spacing: .04em; transition: all .4s var(--ease); }
        .btn-gold { background: #D4A017; color: #111827; box-shadow: 0 8px 28px rgba(212,160,23,0.28); }
        .btn-gold:hover { background: #B8860B; color: #FFFFFF; transform: translateY(-2px); box-shadow: 0 14px 36px rgba(212,160,23,0.38); }
        .back-link { display: inline-flex; align-items: center; gap: .5rem; font-size: .8rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--g2); margin-top: 2.6rem; transition: gap .3s var(--ease); }
        .back-link:hover { gap: .8rem; }
        footer { background: var(--bg2); position: relative; padding: 3rem 0 2rem; }
        footer::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1px; background: linear-gradient(90deg, transparent 0%, rgba(197,151,62,0.35) 25%, rgba(197,151,62,0.6) 50%, rgba(197,151,62,0.35) 75%, transparent 100%); }
        .footer-grid { display: grid; grid-template-columns: 1fr; gap: 2.5rem; margin-bottom: 2.5rem; }
        @media(min-width:768px) { .footer-grid { grid-template-columns: 1.5fr 1fr 1fr; } }
        .footer-grid h4 { font-size: .8rem; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: var(--ink3); margin-bottom: 1rem; }
        .footer-grid ul { display: flex; flex-direction: column; gap: .5rem; }
        .footer-grid ul a { font-size: .88rem; color: var(--ink2); transition: color .3s; }
        .footer-grid ul a:hover { color: var(--g0); }
        .footer-base { display: flex; flex-wrap: wrap; gap: 1rem; font-size: .8rem; color: var(--ink3); border-top: 1px solid var(--bg3); padding-top: 1.5rem; }
        @media(max-width:767px) { .section { padding: 2.5rem 0 4rem; } .article-hero-img { aspect-ratio: 4/3; border-radius: var(--r); } }
    </style>
</head>
<body>
<?php if ($isPreview): ?>
<div style="position:fixed;top:0;left:0;right:0;z-index:400;background:#8B6508;color:#fff;text-align:center;padding:.5rem;font-size:.82rem;font-weight:700">
    Предпросмотр (статус: <?= htmlspecialchars($article['status'], ENT_QUOTES, 'UTF-8') ?>) — эта страница не видна посетителям сайта.
</div>
<?php endif; ?>
<div class="progress" id="progressBar"></div>
<nav class="nav" id="mainNav">
    <div class="shell nav-inner">
        <a href="../index.html" class="brand"><span class="brand-name">Пром-текстиль</span></a>
        <div class="nav-links">
            <div class="nav-dropdown">
                <a href="../index.html#catalog">Изделия <span class="nav-dd-arrow">▾</span></a>
                <div class="dropdown-menu">
                    <a href="../services/chekhly-tenty-v2.html">Чехлы, укрытия, тенты</a>
                    <a href="../services/specodezhda-v2.html">Спецодежда</a>
                    <a href="../services/tactical-v2.html">Тактическое и outdoor</a>
                    <a href="../services/medical-v2.html">Медицинский текстиль</a>
                    <a href="../services/interior-v2.html">Интерьерный B2B</a>
                    <a href="../services/transport-v2.html">Транспортный текстиль</a>
                    <a href="../services/agro-v2.html">Агропромышленный</a>
                    <a href="../services/fire-v2.html">Пожарозащита</a>
                    <a href="../services/cleanroom-v2.html">Чистые помещения</a>
                </div>
            </div>
            <a href="../pages/about-v2.html">О нас</a>
            <a href="../index.html#process">Как работаем</a>
            <a href="../index.html#faq">FAQ</a>
            <a href="../pages/blog-v2.html" aria-current="page">Блог</a>
            <a href="../pages/contacts-v2.html">Контакты</a>
        </div>
        <a href="tel:+79818172649" class="nav-cta">+7 981 817-26-49</a>
        <button class="nav-burger" id="navBurger" type="button" aria-label="Меню" aria-expanded="false"><span></span><span></span><span></span></button>
    </div>
</nav>
<div class="nav-mobile" id="navMobile">
    <div class="mnl-group">
        <div class="mnl-toggle" id="mobMenuToggle">Изделия <span id="mobMenuArrow">▾</span></div>
        <div class="mnl-sub" id="mobMenuSub">
            <a href="../services/chekhly-tenty-v2.html" class="mnl">Чехлы, укрытия, тенты</a>
            <a href="../services/specodezhda-v2.html" class="mnl">Спецодежда</a>
            <a href="../services/tactical-v2.html" class="mnl">Тактическое и outdoor</a>
            <a href="../services/medical-v2.html" class="mnl">Медицинский текстиль</a>
            <a href="../services/interior-v2.html" class="mnl">Интерьерный B2B</a>
            <a href="../services/transport-v2.html" class="mnl">Транспортный текстиль</a>
            <a href="../services/agro-v2.html" class="mnl">Агропромышленный</a>
            <a href="../services/fire-v2.html" class="mnl">Пожарозащита</a>
            <a href="../services/cleanroom-v2.html" class="mnl">Чистые помещения</a>
        </div>
    </div>
    <a href="../pages/about-v2.html" class="mnl">О нас</a>
    <a href="../index.html#process" class="mnl">Как работаем</a>
    <a href="../index.html#faq" class="mnl">FAQ</a>
    <a href="../pages/blog-v2.html" class="mnl" aria-current="page">Блог</a>
    <a href="../pages/contacts-v2.html" class="mnl">Контакты</a>
    <a href="tel:+79818172649">+7 981 817-26-49</a>
</div>

<main>
    <section class="hero-section">
        <div class="shell">
            <nav class="breadcrumbs" aria-label="Хлебные крошки">
                <a href="../pages/blog-v2.html">Блог</a>
                <span>／</span>
                <span><?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') ?></span>
            </nav>
            <div class="article-head reveal">
                <span class="article-cat"><?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') ?></span>
                <h1 class="article-h1"><?= htmlspecialchars($effectiveH1, ENT_QUOTES, 'UTF-8') ?></h1>
                <div class="article-meta">
                    <time datetime="<?= htmlspecialchars(substr($publishedIso, 0, 10), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(format_ru_date($publishedIso), ENT_QUOTES, 'UTF-8') ?></time>
                    <span>Чтение — <?= (int) $readingMinutes ?> минут</span>
                </div>
            </div>
            <div class="article-hero-img">
                <img src="<?= htmlspecialchars($coverUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($coverAlt, ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
            </div>
        </div>
    </section>

    <section class="section">
        <div class="shell">
            <div class="article-layout">
                <article class="article-prose">
                    <?= $article['content_html'] ?>
                    <a href="../pages/blog-v2.html" class="back-link">← Все статьи блога</a>
                </article>
            </div>
        </div>
    </section>
</main>

<footer>
    <div class="shell">
        <div class="footer-grid">
            <div>
                <div style="font-weight:900;font-size:1.2rem;margin-bottom:.5rem;color:var(--ink)">Пром-текстиль</div>
                <p style="font-size:.88rem;color:var(--ink3);max-width:34ch;line-height:1.65">Производство технического текстиля. Санкт-Петербург, 1400 м², 50 специалистов, 20 лет на рынке.</p>
            </div>
            <div>
                <h4>Направления</h4>
                <ul>
                    <li><a href="../services/chekhly-tenty-v2.html">Чехлы и тенты</a></li>
                    <li><a href="../services/specodezhda-v2.html">Спецодежда</a></li>
                    <li><a href="../services/tactical-v2.html">Тактика</a></li>
                    <li><a href="../services/medical-v2.html">Медицинский</a></li>
                    <li><a href="../services/interior-v2.html">Интерьерный B2B</a></li>
                    <li><a href="../services/transport-v2.html">Транспортный</a></li>
                    <li><a href="../services/agro-v2.html">Агропромышленный</a></li>
                    <li><a href="../services/fire-v2.html">Пожарозащита</a></li>
                    <li><a href="../services/cleanroom-v2.html">Чистые помещения</a></li>
                </ul>
            </div>
            <div>
                <h4>Компания</h4>
                <ul>
                    <li><a href="../pages/about-v2.html">О компании</a></li>
                    <li><a href="../pages/blog-v2.html">Блог</a></li>
                    <li><a href="../pages/contacts-v2.html">Контакты</a></li>
                    <li><a href="tel:+79818172649">+7 981 817-26-49</a></li>
                    <li><a href="../privacy.html">Конфиденциальность</a></li>
                    <li><a href="../sitemap.html">Карта сайта</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-base">
            <span>© 2026 Пром-текстиль</span>
            <span>СПб · Литовская 12 к Д</span>
        </div>
    </div>
</footer>
<script>
(function(){
var pb=document.getElementById('progressBar');
window.addEventListener('scroll',function(){var t=document.documentElement.scrollHeight-window.innerHeight;if(t>0)pb.style.width=Math.min(100,(window.scrollY/t)*100)+'%';},{passive:true});
var nav=document.getElementById('mainNav');
window.addEventListener('scroll',function(){nav.classList.toggle('solid',window.scrollY>48)},{passive:true});
var burger=document.getElementById('navBurger'),mob=document.getElementById('navMobile');
burger.addEventListener('click',function(){var open=burger.classList.toggle('open');mob.classList.toggle('open',open);burger.setAttribute('aria-expanded',String(open));document.body.style.overflow=open?'hidden':'';});
document.querySelectorAll('.mnl').forEach(function(a){a.addEventListener('click',function(){burger.classList.remove('open');mob.classList.remove('open');burger.setAttribute('aria-expanded','false');document.body.style.overflow='';});});
var mobToggle=document.getElementById('mobMenuToggle'),mobSub=document.getElementById('mobMenuSub'),mobArrow=document.getElementById('mobMenuArrow');
if(mobToggle)mobToggle.addEventListener('click',function(){var open=mobSub.classList.toggle('open');if(mobArrow)mobArrow.textContent=open?'▴':'▾';});
})();
</script>
</body>
</html>
