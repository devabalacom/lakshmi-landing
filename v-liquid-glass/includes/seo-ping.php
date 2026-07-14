<?php
/**
 * Fire-and-forget search engine notifications, called right after an article
 * transitions to "published". No OAuth/service account needed, so this can
 * run unattended on shared hosting:
 *
 *  - IndexNow (https://www.indexnow.org/) — Bing and Yandex both subscribe to
 *    this feed and typically crawl within minutes. This is the one that
 *    actually gets a new post picked up fast.
 *  - Google's sitemap ping — best-effort nudge to recrawl sitemap.xml sooner.
 *    Google doesn't offer an unauthenticated "index this URL now" endpoint;
 *    real on-demand Google indexing needs their Indexing API (service
 *    account + Search Console ownership), which is out of scope here.
 *
 * Both calls use a short timeout and never throw — a slow/unreachable search
 * engine must never block a save in the admin or a page render on the site.
 */

const SITE_BASE = 'https://пром-текстиль.рф';
const INDEXNOW_KEY = 'a427a013e6dc13abc40357da37c2c3da';

function notify_search_engines(string $absoluteUrl): void
{
    seo_ping_get('https://www.google.com/ping?sitemap=' . urlencode(SITE_BASE . '/sitemap.xml'));

    $host = parse_url(SITE_BASE, PHP_URL_HOST);
    seo_ping_get(
        'https://api.indexnow.org/indexnow'
        . '?url=' . urlencode($absoluteUrl)
        . '&key=' . INDEXNOW_KEY
        . '&keyLocation=' . urlencode(SITE_BASE . '/' . INDEXNOW_KEY . '.txt')
    );
}

function seo_ping_get(string $url): void
{
    if (!function_exists('curl_init')) {
        return;
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT_MS => 1500,
        CURLOPT_CONNECTTIMEOUT_MS => 800,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT => 'prom-tekstil-seo-ping/1.0',
    ]);
    @curl_exec($ch);
    curl_close($ch);
}
