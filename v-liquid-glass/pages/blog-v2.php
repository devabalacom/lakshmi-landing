<?php
require_once dirname(__DIR__) . '/includes/queries.php';

$db = blog_db();
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;
$articles = get_published_articles($db, $perPage, ($page - 1) * $perPage);
$total = count_published_articles($db);
$totalPages = max(1, (int) ceil($total / $perPage));
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
    <title>Блог о техническом текстиле | Пром-текстиль</title>
    <meta name="description" content="Статьи Пром-текстиль о техническом текстиле: чехлы, тенты, материалы, спецодежда, чистые помещения, огнестойкие ткани и подготовка ТЗ.">
    <meta name="theme-color" content="#D4A017">
    <link rel="canonical" href="https://пром-текстиль.рф/pages/blog-v2.html">
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="author" content="Пром-текстиль">
    <meta name="geo.region" content="RU-SPE">
    <meta name="geo.placename" content="Saint Petersburg, Russia">
    <meta name="geo.position" content="59.9869;30.3389">
    <meta name="ICBM" content="59.9869, 30.3389">

    <meta property="og:type" content="website">
    <meta property="og:locale" content="ru_RU">
    <meta property="og:site_name" content="Пром-текстиль">
    <meta property="og:url" content="https://пром-текстиль.рф/pages/blog-v2.html">
    <meta property="og:title" content="Блог о техническом текстиле | Пром-текстиль">
    <meta property="og:description" content="Статьи Пром-текстиль о техническом текстиле: чехлы, тенты, материалы, спецодежда, чистые помещения, огнестойкие ткани и подготовка ТЗ.">
    <meta property="og:image" content="https://пром-текстиль.рф/og-image.svg">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Блог о техническом текстиле | Пром-текстиль">
    <meta name="twitter:description" content="Статьи Пром-текстиль о техническом текстиле: чехлы, тенты, материалы, спецодежда, чистые помещения, огнестойкие ткани и подготовка ТЗ.">
    <meta name="twitter:image" content="https://пром-текстиль.рф/og-image.svg">

    <meta name="yandex-verification" content="cc20bc028820e8f1">
    <meta name="google-site-verification" content="2SUr4ki9IVYciPxgnPJejZgrx7lgREzFV9vVkDza-5Y">
    <script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "@id": "https://пром-текстиль.рф/#organization",
  "name": "Пром-текстиль",
  "alternateName": ["Пром-текстиль"],
  "url": "https://пром-текстиль.рф/",
  "logo": "https://пром-текстиль.рф/og-image.svg",
  "image": "https://пром-текстиль.рф/og-image.svg",
  "description": "Производство технического текстиля для промышленности и бизнеса. Чехлы, тенты, спецодежда, тактическое снаряжение, медицинский и интерьерный текстиль. Серии от 1 единицы.",
  "foundingDate": "2005",
  "telephone": "+7-981-817-26-49",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "Литовская 12, к. Д",
    "addressLocality": "Санкт-Петербург",
    "addressRegion": "Санкт-Петербург",
    "postalCode": "194100",
    "addressCountry": "RU"
  },
  "areaServed": ["RU","BY","KZ","KG"],
  "sameAs": []
}
    </script>
    <script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {"@type": "ListItem","position": 1,"name": "Главная","item": "https://пром-текстиль.рф/"},
    {"@type": "ListItem","position": 2,"name": "Блог","item": "https://пром-текстиль.рф/pages/blog-v2.html"}
  ]
}
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400&display=swap" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400&display=swap"></noscript>
    <style>
        :root {
            --bg:    #F8FAFC;
            --bg2:   #EEF2F6;
            --bg3:   #E2E8F0;
            --card:  #FFFFFF;
            --border:  #CBD5E1;
            --ink:       #1E293B;
            --ink2:      #475569;
            --ink3:      #64748B;
            --text-soft: #94A3B8;
            --g0:    #D4A017;
            --g1:    #FACC15;
            --g2:    #8B6508;
            --ga:    rgba(212,160,23,0.09);
            --gl:    rgba(212,160,23,0.16);
            --glh:   rgba(212,160,23,0.38);
            --accent-hover:  #B8860B;
            --accent-active: #8B6508;
            --accent-bg:     #FFFBEB;
            --accent-border: #FDE68A;
            --nr:    #0F172A;
            --nr2:   #1D1914;
            --nt:    #F8FAFC;
            --nt2:   rgba(248,250,252,0.65);
            --dark-muted: #CBD5E1;
            --gbg:   rgba(255,255,255,0.68);
            --gbg2:  rgba(255,255,255,0.86);
            --gbd:   rgba(212,160,23,0.18);
            --gsh:   0 8px 32px rgba(26,22,18,0.07), 0 1px 3px rgba(26,22,18,0.04);
            --fd: 'Montserrat', sans-serif;
            --fs: 'Montserrat', sans-serif;
            --fb: 'Montserrat', sans-serif;
            --fm: 'Montserrat', monospace;
            --rs:  6px;
            --r:   16px;
            --rl:  24px;
            --rp:  999px;
            --ease: cubic-bezier(0.22, 1, 0.36, 1);
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; background: var(--bg); }
        body {
            background: var(--bg); color: var(--ink); font-family: var(--fb); font-weight: 400;
            line-height: 1.65; -webkit-font-smoothing: antialiased; overflow-x: hidden;
        }
        a { color: inherit; text-decoration: none; }
        ul { list-style: none; }
        button { cursor: pointer; border: 0; font: inherit; background: none; }
        :focus-visible { outline: 2px solid var(--g0); outline-offset: 3px; border-radius: 4px; }
        .progress {
            position: fixed; top: 0; left: 0; height: 2px; width: 0;
            background: linear-gradient(90deg, var(--g2), var(--g0), var(--g1));
            z-index: 300; transition: width .08s linear; pointer-events: none;
        }
        .shell { max-width: 1400px; margin: 0 auto; padding: 0 1.5rem; }
        @media(min-width:768px) { .shell { padding: 0 3rem; } }
        .nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100; padding: 1.2rem 0;
            background: white; border-bottom: 1px solid var(--bg3); transition: padding .4s var(--ease);
        }
        .nav.solid { padding: .85rem 0; }
        .nav-inner { display: flex; align-items: center; justify-content: space-between; }
        .brand { display: flex; align-items: center; gap: .75rem; }
        .brand-name { font-weight: 800; font-size: .95rem; letter-spacing: .01em; color: var(--ink); }
        .nav-links { display: none; align-items: center; gap: 2.2rem; }
        @media(min-width:900px) { .nav-links { display: flex; } }
        .nav-links a {
            font-size: .72rem; font-weight: 600; color: var(--ink3); letter-spacing: .08em; text-transform: uppercase;
            transition: color .25s; position: relative; padding: .2rem 0;
        }
        .nav-links a::after {
            content: ''; position: absolute; left: 0; right: 100%; bottom: -2px; height: 1px; background: var(--g0);
            transition: right .35s var(--ease);
        }
        .nav-links a:hover { color: var(--g2); }
        .nav-links a:hover::after { right: 0; }
        .nav-links a[aria-current="page"] { color: var(--g2); }
        .nav-links a[aria-current="page"]::after { right: 0; }
        .nav-cta {
            display: none; padding: .8rem 1.5rem; border-radius: var(--rp); border: 1.5px solid var(--glh);
            font-size: .75rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--g2);
            transition: background .3s, color .3s, border-color .3s, transform .25s;
        }
        @media(min-width:920px) { .nav-cta { display: inline-flex; } }
        .nav-cta:hover { background: var(--g0); border-color: var(--g0); color: white; transform: translateY(-1px); }
        .nav-burger { display: flex; flex-direction: column; gap: 5px; width: 44px; height: 44px; justify-content: center; padding: 8px; }
        .nav-burger span { display: block; width: 100%; height: 1.5px; background: var(--ink); border-radius: 2px; transition: transform .3s, opacity .3s; }
        .nav-burger.open span:nth-child(1) { transform: translateY(6.5px) rotate(45deg); }
        .nav-burger.open span:nth-child(2) { opacity: 0; }
        .nav-burger.open span:nth-child(3) { transform: translateY(-6.5px) rotate(-45deg); }
        @media(min-width:900px) { .nav-burger { display: none; } }
        .nav-mobile {
            display: none; position: fixed; inset: 0; top: 58px; background: rgba(253,250,245,0.97);
            backdrop-filter: blur(40px); -webkit-backdrop-filter: blur(40px);
            padding: 2.5rem 2rem; flex-direction: column; gap: .2rem; z-index: 99;
        }
        .nav-mobile.open { display: flex; }
        .nav-mobile a {
            padding: 1rem 0; font-size: 1.3rem; font-weight: 800; color: var(--ink2);
            border-bottom: 1px solid var(--bg3); transition: color .25s, padding-left .25s;
        }
        .nav-mobile a:hover { color: var(--g0); padding-left: .5rem; }
        .nav-dropdown { position: relative; }
        .dropdown-menu {
            display: none; position: absolute; top: calc(100% + 1.2rem); left: -1rem; min-width: 240px;
            background: var(--gbg2); backdrop-filter: blur(24px) saturate(1.3); -webkit-backdrop-filter: blur(24px) saturate(1.3);
            border: 1px solid var(--gbd); border-radius: var(--r); box-shadow: var(--gsh); padding: .5rem 0; z-index: 200;
            opacity: 0; transform: translateY(-8px); transition: opacity .25s var(--ease), transform .25s var(--ease), display .25s;
            pointer-events: none;
        }
        .dropdown-menu::before { content: ''; position: absolute; top: -1.2rem; left: 0; right: 0; height: 1.2rem; }
        .nav-dropdown:hover .dropdown-menu, .nav-dropdown:focus-within .dropdown-menu { display: block; opacity: 1; transform: none; pointer-events: auto; }
        .dropdown-menu a {
            display: block; padding: .6rem 1.2rem; font-size: .78rem; font-weight: 500; color: var(--ink2);
            transition: color .2s, background .2s; letter-spacing: .02em; text-transform: none;
        }
        .dropdown-menu a:hover { color: var(--g2); background: var(--ga); }
        .dropdown-menu a::after { display: none !important; }
        .nav-dd-arrow { display: inline-block; margin-left: .3rem; font-size: .6rem; transition: transform .25s var(--ease); vertical-align: middle; }
        .nav-dropdown:hover .nav-dd-arrow { transform: rotate(180deg); }
        .mnl-group { display: flex; flex-direction: column; }
        .mnl-toggle {
            padding: 1rem 0; font-size: 1.3rem; font-weight: 800; color: var(--ink2); border-bottom: 1px solid var(--bg3);
            transition: color .25s; cursor: pointer; display: flex; justify-content: space-between; align-items: center;
        }
        .mnl-toggle:hover { color: var(--g0); }
        .mnl-sub { display: none; flex-direction: column; padding: .4rem 0 .8rem 1rem; }
        .mnl-sub.open { display: flex; }
        .mnl-sub a { padding: .55rem 0; font-size: .95rem; font-weight: 600; color: var(--ink3); border-bottom: none !important; transition: color .2s; }
        .mnl-sub a:hover { color: var(--g0); }
        .hero-section { background: white; padding: 90px 0 1rem; }
        .page-head { padding: 3.5rem 0 1rem; max-width: 680px; }
        .page-head .s-h2 { margin-bottom: .9rem; }
        .section { padding: 3rem 0 6rem; position: relative; }
        @media(min-width:768px) { .section { padding: 3rem 0 8rem; } }
        .s-head { margin-bottom: 3.5rem; }
        .s-h2 { font-family: var(--fd); font-size: clamp(2rem, 4vw, 3.6rem); font-weight: 400; line-height: 1.05; letter-spacing: -.01em; margin-bottom: .7rem; }
        .s-sub { font-size: 1rem; color: var(--ink2); max-width: 560px; line-height: 1.7; }
        .reveal { opacity: 0; transform: translateY(24px); transition: opacity .72s var(--ease), transform .72s var(--ease); }
        .reveal.vis { opacity: 1; transform: none; }
        .blog-grid { display: grid; grid-template-columns: 1fr; gap: 1.5rem; }
        @media(min-width:640px) { .blog-grid { grid-template-columns: repeat(2, 1fr); } }
        @media(min-width:1024px) { .blog-grid { grid-template-columns: repeat(3, 1fr); } }
        .blog-card {
            position: relative; display: flex; flex-direction: column; height: 100%; background: white;
            border: 1px solid var(--gbd); border-radius: var(--r); overflow: hidden;
            transition: transform .4s var(--ease), box-shadow .4s var(--ease), border-color .4s var(--ease);
        }
        .blog-card:hover { transform: translateY(-6px); box-shadow: 0 20px 44px rgba(212,160,23,0.14), 0 4px 12px rgba(26,22,18,0.06); border-color: var(--glh); }
        .blog-card-media { position: relative; overflow: hidden; aspect-ratio: 16/10; background: var(--bg2); }
        .blog-card-media img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .6s var(--ease); }
        .blog-card:hover .blog-card-media img { transform: scale(1.06); }
        .blog-card-body { padding: 1.6rem 1.7rem 1.5rem; display: flex; flex-direction: column; flex: 1; }
        .blog-card-cat {
            align-self: flex-start; font-family: var(--fm); font-size: .58rem; font-weight: 700; letter-spacing: .14em;
            text-transform: uppercase; color: var(--ink); background: transparent; padding: .28rem .75rem; border-radius: var(--rp); margin-bottom: .9rem;
        }
        .blog-card-title { font-size: 1.05rem; font-weight: 700; line-height: 1.35; margin-bottom: .6rem; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
        .blog-card-title a { color: var(--ink); transition: color .25s; }
        .blog-card:hover .blog-card-title a { color: var(--g2); }
        .blog-card-desc { font-size: .86rem; color: var(--ink2); line-height: 1.65; margin-bottom: 1.3rem; flex: 1; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
        .blog-card-meta { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding-top: 1rem; margin-top: auto; border-top: 1px solid var(--bg3); }
        .blog-card-meta time { font-size: .75rem; color: var(--ink3); }
        .blog-card-read { font-size: .78rem; font-weight: 700; color: var(--g0); display: inline-flex; align-items: center; gap: .35rem; transition: gap .3s var(--ease); }
        .blog-card:hover .blog-card-read { gap: .55rem; }
        .stretched-link::after { content: ''; position: absolute; inset: 0; z-index: 2; }
        .blog-pagination { display: flex; gap: .5rem; margin-top: 2.5rem; flex-wrap: wrap; }
        .blog-pagination a, .blog-pagination span { padding: .5rem .95rem; border-radius: var(--rp); font-size: .82rem; font-weight: 700; border: 1px solid var(--gbd); }
        .blog-pagination .current { background: var(--g0); color: white; border-color: var(--g0); }
        footer { background: var(--bg2); position: relative; padding: 3rem 0 2rem; }
        footer::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1px;
            background: linear-gradient(90deg, transparent 0%, rgba(197,151,62,0.35) 25%, rgba(197,151,62,0.6) 50%, rgba(197,151,62,0.35) 75%, transparent 100%);
        }
        .footer-grid { display: grid; grid-template-columns: 1fr; gap: 2.5rem; margin-bottom: 2.5rem; }
        @media(min-width:768px) { .footer-grid { grid-template-columns: 1.5fr 1fr 1fr; } }
        .footer-grid h4 { font-size: .8rem; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: var(--ink2); margin-bottom: 1rem; }
        .footer-grid ul { display: flex; flex-direction: column; gap: .5rem; }
        .footer-grid ul a { font-size: .88rem; display: inline-flex; align-items: center; min-height: 32px; color: var(--ink2); transition: color .3s; }
        .footer-grid ul a:hover { color: var(--g2); }
        /* Footer links need a real touch target on phones */
        @media(max-width:767px) {
            .footer-grid ul a, footer ul li a { min-height: 40px; }
        }
        .footer-base { display: flex; flex-wrap: wrap; gap: 1rem; font-size: .8rem; color: var(--ink2); border-top: 1px solid var(--bg3); padding-top: 1.5rem; }
        @media(max-width:767px) { .section { padding: 2.5rem 0 4rem; } .page-head { padding: 2.5rem 0 .5rem; } }
        @media(prefers-reduced-motion: reduce) { .reveal { transition: none !important; } .blog-card, .blog-card-media img { transition: none !important; } }
    </style>
</head>
<body>
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
            <div class="page-head reveal">
                <h1 class="s-h2">Блог о техническом текстиле</h1>
                <p class="s-sub">В блоге Пром-текстиль будут материалы о техническом текстиле для бизнеса: как подготовить ТЗ, выбрать ткань, согласовать фурнитуру, проверить требования к документам и не ошибиться при заказе партии.</p>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="shell">
            <div class="blog-grid reveal">
                <?php foreach ($articles as $a): ?>
                    <?php
                    $url = article_public_url($a);
                    $img = $a['cover_path'] ? '/uploads/blog/' . $a['cover_path'] : '/img/cat-01-covers.jpg';
                    $alt = $a['cover_alt'] ?: $a['title'];
                    $dateIso = $a['published_at'] ?? $a['publish_at'] ?? $a['created_at'];
                    ?>
                    <article class="blog-card">
                        <div class="blog-card-media">
                            <img src="<?= htmlspecialchars($img, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                        </div>
                        <div class="blog-card-body">
                            <span class="blog-card-cat"><?= htmlspecialchars($a['category_name'] ?? 'Блог', ENT_QUOTES, 'UTF-8') ?></span>
                            <h3 class="blog-card-title"><a href="<?= htmlspecialchars(basename($url), ENT_QUOTES, 'UTF-8') ?>" class="stretched-link"><?= htmlspecialchars($a['title'], ENT_QUOTES, 'UTF-8') ?></a></h3>
                            <p class="blog-card-desc"><?= htmlspecialchars($a['excerpt'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                            <div class="blog-card-meta">
                                <time datetime="<?= htmlspecialchars(substr($dateIso, 0, 10), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(format_ru_date($dateIso), ENT_QUOTES, 'UTF-8') ?></time>
                                <span class="blog-card-read">Читать статью <span aria-hidden="true">→</span></span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
                <?php if (!$articles): ?>
                    <p style="color:var(--ink2)">Пока нет опубликованных статей.</p>
                <?php endif; ?>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="blog-pagination">
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <?php if ($p === $page): ?>
                            <span class="current"><?= $p ?></span>
                        <?php else: ?>
                            <a href="?page=<?= $p ?>"><?= $p ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>
<footer>
    <div class="shell">
        <div class="footer-grid">
            <div>
                <div style="font-weight:900;font-size:1.2rem;margin-bottom:.5rem;color:var(--ink)">Пром-текстиль</div>
                <p style="font-size:.88rem;color:var(--ink2);max-width:34ch;line-height:1.65">Производство технического текстиля. Санкт-Петербург, 1400 м², 50 специалистов, 20 лет на рынке.</p>
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
var io=new IntersectionObserver(function(entries){entries.forEach(function(e){if(e.isIntersecting){e.target.classList.add('vis');io.unobserve(e.target);}});},{threshold:.07,rootMargin:'0px 0px -24px 0px'});
document.querySelectorAll('.reveal').forEach(function(el){io.observe(el)});
})();
</script>
</body>
</html>
