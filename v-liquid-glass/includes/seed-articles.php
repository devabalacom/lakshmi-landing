<?php
require_once __DIR__ . '/functions.php';

function seed_default_articles(PDO $db): void
{
    // Legacy seed articles stay here for backward compatibility. New weekly
    // articles should be added as JSON files in v-liquid-glass/content/blog/.
    seed_tz_cover_article($db);
    seed_cover_material_choice_article($db);
    seed_en_45545_textile_procurement_article($db);
    seed_git_blog_articles($db);
    seed_article_covers($db);
}

function seed_git_blog_articles(PDO $db): void
{
    $dir = dirname(__DIR__) . '/content/blog';
    foreach (glob($dir . '/*.json') ?: [] as $file) {
        $article = json_decode(file_get_contents($file) ?: '', true);
        if (!is_array($article)) {
            continue;
        }
        upsert_seed_blog_article($db, $article);
    }
}

function upsert_seed_blog_article(PDO $db, array $article): void
{
    $slug = (string) ($article['slug'] ?? '');
    $title = (string) ($article['title'] ?? '');
    $blocks = $article['blocks'] ?? [];
    if (!is_valid_slug($slug) || $title === '' || !is_array($blocks)) {
        return;
    }

    $categoryId = find_or_create_seed_category($db, (string) ($article['category'] ?? 'Блог'));
    $contentJson = json_encode([
        'time' => strtotime((string) ($article['published_at'] ?? 'now')) * 1000,
        'blocks' => $blocks,
        'version' => '2.29.1',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $contentHtml = render_editorjs_to_html($contentJson);
    $publishedAt = (string) ($article['published_at'] ?? date('Y-m-d H:i:s'));
    $coverPath = trim((string) ($article['cover_path'] ?? ''));
    $coverMediaId = null;
    if ($coverPath !== '') {
        $coverMediaId = find_or_create_seed_media($db, $coverPath, (string) ($article['cover_alt'] ?? $title));
    }

    $secondaryKeywords = $article['secondary_keywords'] ?? [];
    if (!is_array($secondaryKeywords)) {
        $secondaryKeywords = [(string) $secondaryKeywords];
    }

    $values = [
        'title' => $title,
        'slug' => $slug,
        'excerpt' => (string) ($article['excerpt'] ?? ''),
        'content_json' => $contentJson,
        'content_html' => $contentHtml,
        'category_id' => $categoryId,
        'author' => (string) ($article['author'] ?? 'Пром-текстиль'),
        'reading_time_minutes' => estimate_reading_minutes($contentHtml),
        'status' => (string) ($article['status'] ?? 'published'),
        'published_at' => $publishedAt,
        'created_at' => $publishedAt,
        'updated_at' => date('Y-m-d H:i:s'),
        'seo_title' => (string) ($article['seo_title'] ?? $title),
        'meta_description' => (string) ($article['meta_description'] ?? ($article['excerpt'] ?? '')),
        'h1' => (string) ($article['h1'] ?? $title),
        'canonical_url' => (string) ($article['canonical_url'] ?? ('https://пром-текстиль.рф/pages/blog-' . $slug . '.html')),
        'focus_keyword' => (string) ($article['focus_keyword'] ?? ''),
        'secondary_keywords' => implode(', ', array_filter($secondaryKeywords)),
        'og_title' => (string) ($article['og_title'] ?? $title),
        'og_description' => (string) ($article['og_description'] ?? ($article['excerpt'] ?? '')),
        'twitter_title' => (string) ($article['twitter_title'] ?? $title),
        'twitter_description' => (string) ($article['twitter_description'] ?? ($article['excerpt'] ?? '')),
        'schema_type' => (string) ($article['schema_type'] ?? 'BlogPosting'),
        'sitemap_changefreq' => (string) ($article['sitemap_changefreq'] ?? 'monthly'),
        'cover_media_id' => $coverMediaId,
    ];

    $stmt = $db->prepare('SELECT id FROM articles WHERE slug = :slug');
    $stmt->execute(['slug' => $slug]);
    $existing = $stmt->fetch();
    if ($existing) {
        $updateValues = [
            'id' => (int) $existing['id'],
            'title' => $values['title'],
            'excerpt' => $values['excerpt'],
            'content_json' => $values['content_json'],
            'content_html' => $values['content_html'],
            'category_id' => $values['category_id'],
            'author' => $values['author'],
            'cover_media_id' => $values['cover_media_id'],
            'reading_time_minutes' => $values['reading_time_minutes'],
            'status' => $values['status'],
            'published_at' => $values['published_at'],
            'updated_at' => $values['updated_at'],
            'seo_title' => $values['seo_title'],
            'meta_description' => $values['meta_description'],
            'h1' => $values['h1'],
            'canonical_url' => $values['canonical_url'],
            'focus_keyword' => $values['focus_keyword'],
            'secondary_keywords' => $values['secondary_keywords'],
            'og_title' => $values['og_title'],
            'og_description' => $values['og_description'],
            'twitter_title' => $values['twitter_title'],
            'twitter_description' => $values['twitter_description'],
            'schema_type' => $values['schema_type'],
            'sitemap_changefreq' => $values['sitemap_changefreq'],
        ];
        $db->prepare(
            'UPDATE articles SET
                title = :title, excerpt = :excerpt, content_json = :content_json,
                content_html = :content_html, category_id = :category_id, author = :author,
                cover_media_id = :cover_media_id, reading_time_minutes = :reading_time_minutes,
                status = :status, published_at = :published_at, updated_at = :updated_at,
                seo_title = :seo_title, meta_description = :meta_description, h1 = :h1,
                canonical_url = :canonical_url, focus_keyword = :focus_keyword,
                secondary_keywords = :secondary_keywords, og_title = :og_title,
                og_description = :og_description, og_image_id = :cover_media_id,
                twitter_title = :twitter_title, twitter_description = :twitter_description,
                twitter_image_id = :cover_media_id, schema_type = :schema_type,
                sitemap_changefreq = :sitemap_changefreq
             WHERE id = :id'
        )->execute($updateValues);
        $articleId = (int) $existing['id'];
    } else {
        $insertValues = [
            'title' => $values['title'],
            'slug' => $values['slug'],
            'excerpt' => $values['excerpt'],
            'content_json' => $values['content_json'],
            'content_html' => $values['content_html'],
            'category_id' => $values['category_id'],
            'author' => $values['author'],
            'cover_media_id' => $values['cover_media_id'],
            'reading_time_minutes' => $values['reading_time_minutes'],
            'status' => $values['status'],
            'published_at' => $values['published_at'],
            'created_at' => $values['created_at'],
            'updated_at' => $values['updated_at'],
            'seo_title' => $values['seo_title'],
            'meta_description' => $values['meta_description'],
            'h1' => $values['h1'],
            'canonical_url' => $values['canonical_url'],
            'focus_keyword' => $values['focus_keyword'],
            'secondary_keywords' => $values['secondary_keywords'],
            'og_title' => $values['og_title'],
            'og_description' => $values['og_description'],
            'twitter_title' => $values['twitter_title'],
            'twitter_description' => $values['twitter_description'],
            'schema_type' => $values['schema_type'],
            'sitemap_changefreq' => $values['sitemap_changefreq'],
        ];
        $db->prepare(
            'INSERT INTO articles (
                title, slug, excerpt, content_json, content_html, category_id, author,
                cover_media_id, reading_time_minutes, status, published_at, created_at, updated_at,
                seo_title, meta_description, h1, canonical_url, robots_index, robots_follow,
                focus_keyword, secondary_keywords, og_title, og_description, og_image_id,
                twitter_title, twitter_description, twitter_image_id, schema_type,
                include_in_sitemap, sitemap_priority, sitemap_changefreq
            ) VALUES (
                :title, :slug, :excerpt, :content_json, :content_html, :category_id, :author,
                :cover_media_id, :reading_time_minutes, :status, :published_at, :created_at, :updated_at,
                :seo_title, :meta_description, :h1, :canonical_url, 1, 1,
                :focus_keyword, :secondary_keywords, :og_title, :og_description, :cover_media_id,
                :twitter_title, :twitter_description, :cover_media_id, :schema_type,
                1, 0.6, :sitemap_changefreq
            )'
        )->execute($insertValues);
        $articleId = (int) $db->lastInsertId();
    }

    $tags = $article['tags'] ?? [];
    if (!is_array($tags)) {
        $tags = [(string) $tags];
    }
    set_seed_article_tags($db, $articleId, $tags);
}

function seed_tz_cover_article(PDO $db): void
{
    $slug = 'kak-snyat-tz-na-chehol-dlya-oborudovaniya';
    $stmt = $db->prepare('SELECT id FROM articles WHERE slug = :slug');
    $stmt->execute(['slug' => $slug]);
    if ($stmt->fetch()) {
        return;
    }

    $categoryId = find_or_create_seed_category($db, 'Чехлы и тенты');
    $contentJson = json_encode([
        'time' => strtotime('2026-07-16 12:00:00') * 1000,
        'blocks' => [
            block_paragraph('Чехлы для оборудования на заказ редко шьют “по примерной ширине”. Для простого пыльника этого иногда хватает, но промышленный чехол обычно должен закрывать конкретный корпус, обходить выступающие детали, не мешать обслуживанию и выдерживать свою среду: склад, цех, улицу, перевозку, влажную зону или участок с пылью.'),
            block_paragraph('Хорошее ТЗ не обязано быть сложным. Достаточно собрать размеры, фото, условия эксплуатации и пару важных ограничений. Тогда производитель быстрее подберет материал, предложит крепления и даст расчет без длинной переписки.'),

            block_header('Что нужно понять до замера'),
            block_paragraph('Сначала стоит ответить на один вопрос: что именно должен делать защитный чехол на оборудование. Закрывать от пыли на складе, защищать от влаги на улице, снижать риск царапин при перевозке, закрывать прибор от брызг, сохранять чистоту изделия между сменами или работать как транспортировочный чехол с ручками и усилениями.'),
            block_paragraph('От назначения зависит почти все: ткань, плотность, швы, фурнитура, способ фиксации и даже форма выкройки. Чехол для станка в цехе и чехол для паллеты на улице могут быть похожи только названием.'),

            block_header('Какие фото приложить'),
            block_paragraph('Фото часто экономят больше времени, чем длинное описание. Лучше сделать несколько кадров при нормальном освещении:'),
            block_list([
                'общий вид оборудования спереди, сбоку и сзади;',
                'крупно выступающие элементы: ручки, патрубки, колеса, панели, кабели;',
                'места, где чехол должен открываться или сниматься;',
                'точки, за которые можно крепиться: рама, ножки, основание, кронштейны;',
                'шильдик или паспортную табличку, если по модели можно найти габариты.',
            ]),
            block_paragraph('Если оборудование стоит вплотную к стене или другой технике, это тоже нужно показать. Иногда чехол нельзя снять вверх, и тогда конструкция делается на молнии, липучке или с разъемной частью.'),

            block_header('Размеры: что измерять'),
            block_paragraph('Минимальный набор: длина, ширина, высота. Но для чехла по форме оборудования этого мало. Нужно отметить все зоны, которые меняют геометрию изделия:'),
            block_list([
                'выступы и ручки;',
                'панели управления;',
                'колеса и опоры;',
                'патрубки, разъемы, кабельные вводы;',
                'крышки, люки, откидные элементы;',
                'зоны, которые должны оставаться открытыми.',
            ]),
            block_paragraph('Размеры лучше давать в миллиметрах. Если есть чертеж, схема или 3D-модель, приложите их к заявке. Если чертежа нет, подойдет простая схема от руки: прямоугольник, основные габариты, стрелки к выступам и подписи.'),
            block_paragraph('Для мягких чехлов обычно нужен технологический запас. Его не стоит добавлять самостоятельно “на глаз”, если нет опыта. Лучше указать чистые размеры оборудования и написать, насколько плотно должен сидеть чехол: свободно, с небольшим облеганием или максимально по форме.'),

            block_header('Условия эксплуатации'),
            block_paragraph('Одна и та же выкройка может требовать разных материалов. В ТЗ нужно написать, где и как будет использоваться изделие:'),
            block_list([
                'внутри помещения или на улице;',
                'постоянное хранение или периодическое накрывание;',
                'есть ли влага, снег, конденсат, масло, пыль, искры;',
                'важна ли защита от ультрафиолета;',
                'нужно ли часто снимать и надевать чехол;',
                'будет ли изделие перевозиться вместе с оборудованием.',
            ]),
            block_paragraph('Для улицы чаще смотрят в сторону ПВХ, Oxford с пропиткой, Tarpaulin или других влагостойких тканей. Для цеха важнее износостойкость, удобство чистки и поведение рядом с рабочей зоной. Для специальных задач могут понадобиться антистатические, огнестойкие или другие технические материалы, но такие требования лучше подтверждать документами на ткань, а не формулировать общими словами.'),

            block_header('Материал: что указать в заявке'),
            block_paragraph('Если материал уже задан внутренним стандартом, напишите его прямо: ПВХ, Oxford, Cordura, брезент, огнестойкая ткань, антистатическая ткань. Если стандарта нет, опишите задачу человеческим языком: “будет стоять на улице круглый год”, “нужно часто мыть”, “чехол будут снимать каждый день”, “есть риск зацепов о металлические кромки”.'),
            block_paragraph('Производителю этого достаточно, чтобы предложить варианты. В нормальном расчете должны быть понятны не только цена, но и логика выбора: почему один материал подходит для влажной зоны, а другой лучше для транспортировки или частого использования.'),

            block_header('Крепления и доступ к оборудованию'),
            block_paragraph('Крепления часто вспоминают слишком поздно. А потом выясняется, что чехол закрывает нужную ручку, молния стоит не с той стороны или изделие неудобно снимать одному человеку.'),
            block_paragraph('В ТЗ стоит заранее указать:'),
            block_list([
                'как чехол надевается: сверху, сбоку, через разъемную часть;',
                'нужно ли открывать панель без полного снятия;',
                'нужны ли молнии, липучки, люверсы, ремни, фастексы, шнур, утяжка;',
                'будет ли доступ для обслуживания, осмотра, зарядки, вентиляции;',
                'кто будет пользоваться изделием: один человек, бригада, складской персонал.',
            ]),
            block_paragraph('Для крупных промышленных чехлов удобство обслуживания иногда важнее идеального облегания. Если изделие снимают каждый день, лучше сразу заложить ручки, усиления и понятную схему фиксации.'),

            block_header('Что подготовить перед звонком'),
            block_paragraph('Оптимальный пакет для обсуждения выглядит так:'),
            block_list([
                'назначение чехла: хранение, защита, перевозка, временное укрытие;',
                'фото оборудования с нескольких сторон;',
                'длина, ширина, высота и размеры выступающих частей;',
                'условия эксплуатации: улица, цех, склад, влажность, пыль, УФ;',
                'требования к материалу, если они есть;',
                'пожелания по креплениям и доступу;',
                'количество изделий и нужна ли повторяемость партии;',
                'требования к цвету, маркировке, упаковке.',
            ]),
            block_paragraph('Такой набор закрывает большую часть вопросов по чехлам на заказ на оборудование. Если изделие сложное, после первичного расчета можно отдельно согласовать лекало, образец или выезд на замер.'),

            block_header('Частые ошибки в ТЗ'),
            block_paragraph('Самая частая ошибка — дать только три габарита и не показать форму. Для коробки этого достаточно, для оборудования почти никогда. Выступы, ручки и кабели меняют посадку.'),
            block_paragraph('Вторая ошибка — не описать среду. “Нужен прочный чехол” звучит понятно, но для производства этого мало. Прочность на истирание, влагостойкость, огнестойкость и устойчивость к загрязнениям решаются разными материалами.'),
            block_paragraph('Третья ошибка — забыть про сценарий использования. Чехол, который надевают раз в сезон, и чехол, который снимают каждую смену, должны быть устроены по-разному.'),

            block_header('FAQ'),
            block_header('Можно ли заказать чехол без чертежа?', 3),
            block_paragraph('Да. Для первого расчета обычно достаточно фото, размеров и описания условий эксплуатации. Чертеж ускоряет работу, но не всегда обязателен.'),
            block_header('Что лучше: чехол по размерам или по форме оборудования?', 3),
            block_paragraph('Если оборудование простое, подойдет чехол по размерам. Если есть выступы, панели, кабели, колесная база или нестандартный корпус, лучше делать чехол по форме оборудования.'),
            block_header('Можно ли сделать один образец перед партией?', 3),
            block_paragraph('Да, для серийных или повторяемых изделий это разумный шаг. Образец помогает проверить посадку, крепления и удобство использования до запуска партии.'),
            block_header('Какие материалы подходят для промышленных чехлов?', 3),
            block_paragraph('Часто используют ПВХ, Oxford, Cordura, Tarpaulin, брезент и технические ткани со специальными свойствами. Выбор зависит от среды, нагрузки и требований к обслуживанию.'),
            block_header('Можно ли добавить логотип или маркировку?', 3),
            block_paragraph('Да, если это нужно для склада, эксплуатации или корпоративного стандарта. Маркировку лучше согласовать до запуска партии, чтобы сразу заложить место и способ нанесения.'),

            block_header('Короткий чек-лист перед звонком'),
            block_paragraph('Перед расчетом проверьте, что есть фото, основные размеры, описание среды, количество изделий и пожелания по креплениям. Если есть спорные места, лучше отметить их прямо на фото.'),
            block_paragraph('Для расчета чехла для оборудования подготовьте размеры, фото и краткое описание задачи, затем позвоните в Пром-текстиль или откройте <a href="/pages/contacts-v2.html">контакты</a>. Чем точнее исходные данные, тем быстрее получится подобрать материал, конструкцию и стоимость без лишних итераций.'),
            [
                'type' => 'cta',
                'data' => [
                    'heading' => 'Нужно рассчитать чехол для оборудования?',
                    'text' => 'Позвоните в Пром-текстиль и подготовьте размеры, фото объекта и условия эксплуатации. По этим данным проще обсудить материал, крепления и партию.',
                    'buttonText' => 'Позвонить',
                    'buttonHref' => 'tel:+79818172649',
                ],
            ],
        ],
        'version' => '2.29.1',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $contentHtml = render_editorjs_to_html($contentJson);
    $publishedAt = '2026-07-16 12:00:00';
    $stmt = $db->prepare(
        'INSERT INTO articles (
            title, slug, excerpt, content_json, content_html, category_id, author,
            reading_time_minutes, status, published_at, created_at, updated_at,
            seo_title, meta_description, h1, canonical_url, robots_index, robots_follow,
            focus_keyword, secondary_keywords, og_title, og_description,
            twitter_title, twitter_description, schema_type,
            include_in_sitemap, sitemap_priority, sitemap_changefreq
        ) VALUES (
            :title, :slug, :excerpt, :content_json, :content_html, :category_id, :author,
            :reading_time_minutes, :status, :published_at, :created_at, :updated_at,
            :seo_title, :meta_description, :h1, :canonical_url, 1, 1,
            :focus_keyword, :secondary_keywords, :og_title, :og_description,
            :twitter_title, :twitter_description, :schema_type,
            1, 0.6, :sitemap_changefreq
        )'
    );
    $stmt->execute([
        'title' => 'Как снять ТЗ на чехол для оборудования: размеры, крепления, материал',
        'slug' => $slug,
        'excerpt' => 'Чек-лист для заказа чехла для оборудования: какие размеры, фото, условия эксплуатации, материалы и крепления нужны для точного расчета.',
        'content_json' => $contentJson,
        'content_html' => $contentHtml,
        'category_id' => $categoryId,
        'author' => 'Пром-текстиль',
        'reading_time_minutes' => estimate_reading_minutes($contentHtml),
        'status' => 'published',
        'published_at' => $publishedAt,
        'created_at' => $publishedAt,
        'updated_at' => $publishedAt,
        'seo_title' => 'Как снять ТЗ на чехол для оборудования: размеры, крепления, материал',
        'meta_description' => 'Чек-лист для заказа чехла для оборудования: какие размеры, фото, условия эксплуатации, материалы и крепления нужны для точного расчета.',
        'h1' => 'Как снять ТЗ на чехол для оборудования: размеры, крепления, материал',
        'canonical_url' => 'https://пром-текстиль.рф/pages/blog-kak-snyat-tz-na-chehol-dlya-oborudovaniya.html',
        'focus_keyword' => 'чехлы для оборудования на заказ',
        'secondary_keywords' => 'чехлы на заказ на оборудование, защитный чехол на оборудование, чехлы для оборудования, чехол по размерам, чехол по форме оборудования, промышленные чехлы',
        'og_title' => 'Как снять ТЗ на чехол для оборудования',
        'og_description' => 'Что подготовить для расчета промышленного чехла: фото, размеры, условия эксплуатации, материалы и крепления.',
        'twitter_title' => 'Как снять ТЗ на чехол для оборудования',
        'twitter_description' => 'Чек-лист для B2B-заказчика: размеры, фото, материалы, крепления и условия эксплуатации.',
        'schema_type' => 'BlogPosting',
        'sitemap_changefreq' => 'monthly',
    ]);

    set_seed_article_tags($db, (int) $db->lastInsertId(), [
        'чехлы для оборудования',
        'технический текстиль',
        'B2B',
    ]);
}

function seed_cover_material_choice_article(PDO $db): void
{
    $slug = 'kak-vybrat-material-dlya-chehla-pvh-oxford-cordura-brezent';
    $stmt = $db->prepare('SELECT id FROM articles WHERE slug = :slug');
    $stmt->execute(['slug' => $slug]);
    if ($stmt->fetch()) {
        return;
    }

    $categoryId = find_or_create_seed_category($db, 'Материалы');
    $contentJson = json_encode([
        'time' => strtotime('2026-07-28 09:00:00') * 1000,
        'blocks' => [
            block_paragraph('Материал для чехла лучше выбирать не по названию ткани, а по задаче. Один заказчик закрывает станок от пыли в сухом цехе. Другой хранит оборудование на улице под снегом. Третий возит приборы в машине и каждый день снимает чехол руками в перчатках. Для этих трех случаев нужен разный материал, даже если размеры чехла одинаковые.'),
            block_paragraph('Ниже простой разбор четырех частых вариантов: ПВХ, Oxford, Cordura и брезент. Он поможет подготовиться к звонку, заранее отсеять неподходящие ткани и быстрее получить расчет.'),

            block_header('Сначала опишите условия, а не ткань'),
            block_paragraph('Перед выбором материала ответьте на пять вопросов. Ответы можно сразу отправить производителю вместе с размерами и фото.'),
            block_list([
                'Где будет работать чехол: помещение, улица, склад, транспорт, влажная зона.',
                'От чего нужна защита: пыль, вода, грязь, УФ, искры, масло, истирание, случайные удары.',
                'Как часто чехол будут снимать: раз в месяц, раз в неделю, каждый день, несколько раз за смену.',
                'Нужна ли жесткая посадка или подойдет мягкий чехол с технологическим запасом.',
                'Есть ли требования к цвету, маркировке, огнестойкости, антистатике или документам на ткань.',
            ]),
            block_paragraph('Если этих данных нет, разговор быстро уходит в общие слова: “нужен прочный”, “чтобы не промокал”, “чтобы служил долго”. Для расчета лучше писать конкретно: “улица круглый год, осадки, солнце, снимают редко” или “сухой цех, пыль, снимают каждый день, важна легкость”.'),

            block_header('ПВХ: когда нужна влагостойкость и простая мойка'),
            block_paragraph('ПВХ-ткань часто выбирают для уличных укрытий, тентов, плотных защитных чехлов и изделий, которые нужно мыть. Материал хорошо переносит влагу, грязь и ветер, если правильно подобраны плотность, швы и крепления.'),
            block_paragraph('ПВХ стоит рассматривать, если оборудование хранится на улице, чехол должен закрывать от дождя и снега, изделие редко снимают или нужна плотная конструкция с люверсами, ремнями, утяжкой. Еще один плюс - поверхность проще протирать после грязи, чем ткань с выраженной фактурой.'),
            block_paragraph('Ограничение тоже есть. ПВХ может быть тяжелее Oxford и не всегда удобен для чехлов, которые оператор надевает несколько раз в день. На маленьких изделиях жесткость материала иногда мешает аккуратной посадке вокруг сложных выступов. Для морозной улицы лучше заранее обсудить температуру эксплуатации: не каждая ПВХ-ткань одинаково ведет себя на холоде.'),

            block_header('Oxford: когда нужен легкий защитный чехол'),
            block_paragraph('Oxford с пропиткой подходит для многих чехлов внутри помещения, складских укрытий, временной защиты от пыли и умеренной влаги. Его часто выбирают, когда изделие должно быть легче, мягче и удобнее в ежедневном использовании.'),
            block_paragraph('Oxford уместен для приборов, мебели, оборудования в чистом или сухом помещении, транспортировочных чехлов без жесткой абразивной нагрузки, защитных накидок, которые часто снимают и складывают. Для таких задач важны не только плотность ткани, но и качество пропитки, нитки, швы, молнии и усиления в местах хвата.'),
            block_paragraph('Если чехол будет постоянно лежать под открытым небом, тереться о металл или закрывать острые кромки, Oxford нужно проверять осторожно. Иногда он подходит, но только с усилениями. Иногда лучше сразу перейти к ПВХ, Cordura или комбинированной конструкции.'),

            block_header('Cordura: когда важна износостойкость'),
            block_paragraph('Cordura выбирают для изделий, которые много трутся, цепляются, переносятся или работают в жестком режиме. Это частый вариант для транспортировочных чехлов, сумок, подсумков, защитных элементов, чехлов для инструмента и оборудования, которое часто перемещают.'),
            block_paragraph('Материал полезен там, где слабое место - не дождь сверху, а износ: углы корпуса, ручки, места контакта с полом, стропы, крепления, зоны частого хвата. В таких местах Cordura может использоваться как основной материал или как усиление поверх другой ткани.'),
            block_paragraph('Cordura не стоит выбирать только из-за слова “прочная”. Для уличного стационарного чехла иногда разумнее ПВХ. Для простого пыльника в помещении Cordura может быть избыточной по цене. Хороший вариант - указать, где именно будет трение, и попросить предложить основной материал плюс усиления.'),

            block_header('Брезент: когда нужны плотность, воздух и грубая защита'),
            block_paragraph('Брезент используют для плотных укрытий, рабочих чехлов, защитных изделий для цеха и складских задач. Он хорошо знаком производству и часто подходит там, где нужна механическая защита, стойкость к грубому обращению и более “дышащее” поведение, чем у сплошного ПВХ.'),
            block_paragraph('Брезент стоит обсуждать для сухих или условно сухих зон, защиты от пыли, искр и загрязнений, укрытий в производстве, изделий с простой геометрией. Если нужна огнестойкость, это надо писать отдельно и запрашивать материал с нужной обработкой и документами. Обычное слово “брезент” само по себе не подтверждает специальное свойство.'),
            block_paragraph('Для постоянной защиты от воды брезент не всегда лучший выбор. Он может намокать, тяжелеть и требовать сушки. Если изделие будет на улице под дождем, нужно отдельно сравнить брезент с ПВХ или другой влагостойкой тканью.'),

            block_header('Как выбрать быстрее: таблица решений'),
            block_list([
                'Улица, дождь, снег, редкое снятие: чаще ПВХ.',
                'Сухой цех или склад, пыль, частое снятие: чаще Oxford.',
                'Перевозка, острые углы, постоянное трение: Cordura или усиления из Cordura.',
                'Грубая защита в цехе, нужна плотная ткань: брезент или специальная техническая ткань.',
                'Сложная геометрия и ежедневное использование: мягкий материал плюс усиления в нагруженных местах.',
                'Огнестойкость, антистатика, ESD, санитарные требования: выбирать не “похожую” ткань, а материал с нужными документами.',
            ]),

            block_header('Где чаще ошибаются'),
            block_paragraph('Первая ошибка - выбирать самый плотный материал “с запасом”. Тяжелый чехол может быть неудобен, его перестают надевать, и защита перестает работать. Если чехол снимают каждый день, вес и хват важны не меньше плотности.'),
            block_paragraph('Вторая ошибка - не учитывать кромки оборудования. Даже хорошая ткань быстро портится, если постоянно трется об острый угол. В таких местах нужны усиления, подкладка, окантовка или другая посадка.'),
            block_paragraph('Третья ошибка - забывать про крепления. Материал может быть выбран правильно, но ветер, вибрация или частое снятие порвут изделие в районе люверсов, ремней или молнии. Для крупных чехлов крепления надо обсуждать вместе с тканью.'),
            block_paragraph('Четвертая ошибка - просить “водонепроницаемый чехол”, но оставлять открытые швы, молнии и низ изделия. Защита от воды зависит не только от ткани. Важны конструкция, направление стока, обработка швов и способ фиксации.'),

            block_header('Что подготовить для расчета'),
            block_paragraph('Перед звонком в Пром-текстиль подготовьте короткое описание задачи. Его можно написать в один абзац: где стоит оборудование, от чего защищаем, как часто снимаем чехол, какие размеры и выступы есть, сколько изделий нужно.'),
            block_list([
                'Фото оборудования с 3-4 сторон.',
                'Габариты в миллиметрах: длина, ширина, высота.',
                'Фото или размеры выступающих деталей: ручки, патрубки, кабели, колеса, панели.',
                'Условия эксплуатации: улица или помещение, влажность, пыль, солнце, грязь, температура.',
                'Сценарий использования: хранение, перевозка, ежедневная работа, сезонное укрытие.',
                'Пожелания по креплениям: молния, липучка, ремни, люверсы, утяжка, ручки.',
                'Количество изделий и нужна ли повторяемость партии.',
            ]),

            block_header('Когда лучше делать комбинированный чехол'),
            block_paragraph('Один материал не всегда должен решать всю задачу. Часто рабочее решение выглядит так: основная ткань легче, а углы, ручки, низ и места трения усилены. Для улицы можно использовать влагостойкий основной материал, а в местах нагрузки добавить ремни, стропу, накладки и усиленную окантовку.'),
            block_paragraph('Комбинированный чехол особенно полезен для оборудования с выступами, колесами, кабелями, частым доступом к панели управления или перевозкой. Он может быть практичнее, чем просто взять самую дорогую ткань на всю площадь.'),

            block_header('Короткий вывод'),
            block_paragraph('Для улицы и воды чаще смотрят на ПВХ. Для легкого пыльника и частого снятия - на Oxford. Для трения и переноски - на Cordura или усиления из нее. Для плотной рабочей защиты в цехе - на брезент или специальную техническую ткань.'),
            block_paragraph('Чтобы быстрее получить точный расчет, не начинайте с фразы “посоветуйте прочную ткань”. Подготовьте фото, размеры, условия эксплуатации и сценарий использования. После этого можно выбрать материал без угадывания и сразу обсудить конструкцию чехла.'),
            [
                'type' => 'cta',
                'data' => [
                    'heading' => 'Нужно подобрать материал для чехла?',
                    'text' => 'Позвоните в Пром-текстиль и подготовьте фото, размеры и условия эксплуатации. По этим данным проще выбрать ткань, крепления и усиления без лишних переделок.',
                    'buttonText' => 'Позвонить',
                    'buttonHref' => 'tel:+79818172649',
                ],
            ],
        ],
        'version' => '2.29.1',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $contentHtml = render_editorjs_to_html($contentJson);
    $publishedAt = '2026-07-28 09:00:00';
    $stmt = $db->prepare(
        'INSERT INTO articles (
            title, slug, excerpt, content_json, content_html, category_id, author,
            reading_time_minutes, status, published_at, created_at, updated_at,
            seo_title, meta_description, h1, canonical_url, robots_index, robots_follow,
            focus_keyword, secondary_keywords, og_title, og_description,
            twitter_title, twitter_description, schema_type,
            include_in_sitemap, sitemap_priority, sitemap_changefreq
        ) VALUES (
            :title, :slug, :excerpt, :content_json, :content_html, :category_id, :author,
            :reading_time_minutes, :status, :published_at, :created_at, :updated_at,
            :seo_title, :meta_description, :h1, :canonical_url, 1, 1,
            :focus_keyword, :secondary_keywords, :og_title, :og_description,
            :twitter_title, :twitter_description, :schema_type,
            1, 0.6, :sitemap_changefreq
        )'
    );
    $stmt->execute([
        'title' => 'ПВХ, Oxford, Cordura или брезент: какой материал выбрать для чехла',
        'slug' => $slug,
        'excerpt' => 'Практичный разбор материалов для защитного чехла: когда выбрать ПВХ, Oxford, Cordura или брезент, какие условия описать и что подготовить для расчета.',
        'content_json' => $contentJson,
        'content_html' => $contentHtml,
        'category_id' => $categoryId,
        'author' => 'Пром-текстиль',
        'reading_time_minutes' => estimate_reading_minutes($contentHtml),
        'status' => 'published',
        'published_at' => $publishedAt,
        'created_at' => $publishedAt,
        'updated_at' => $publishedAt,
        'seo_title' => 'ПВХ, Oxford, Cordura или брезент: какой материал выбрать для чехла',
        'meta_description' => 'Как выбрать ткань для чехла: ПВХ, Oxford, Cordura или брезент. Условия эксплуатации, ошибки выбора, усиления и чек-лист для расчета.',
        'h1' => 'ПВХ, Oxford, Cordura или брезент: какой материал выбрать для чехла',
        'canonical_url' => 'https://пром-текстиль.рф/pages/blog-kak-vybrat-material-dlya-chehla-pvh-oxford-cordura-brezent.html',
        'focus_keyword' => 'ткань для чехлов',
        'secondary_keywords' => 'ПВХ ткань для чехла, Oxford для чехла, Cordura для чехла, брезент для чехла, материал для защитного чехла, чехол из ПВХ, чехол из Oxford',
        'og_title' => 'Как выбрать материал для чехла',
        'og_description' => 'ПВХ, Oxford, Cordura или брезент: что подходит для улицы, цеха, перевозки и частого снятия.',
        'twitter_title' => 'Как выбрать материал для чехла',
        'twitter_description' => 'Практичный чек-лист по выбору ткани для защитного чехла: условия, ошибки, усиления и подготовка к расчету.',
        'schema_type' => 'BlogPosting',
        'sitemap_changefreq' => 'monthly',
    ]);

    set_seed_article_tags($db, (int) $db->lastInsertId(), [
        'материалы',
        'чехлы',
        'технический текстиль',
    ]);
}

function seed_en_45545_textile_procurement_article(PDO $db): void
{
    $slug = 'en-45545-dlya-tekstilya-chto-proverit-pered-zakupkoy';
    $stmt = $db->prepare('SELECT id FROM articles WHERE slug = :slug');
    $stmt->execute(['slug' => $slug]);
    if ($stmt->fetch()) {
        return;
    }

    $categoryId = find_or_create_seed_category($db, 'Огнестойкие материалы');
    $contentJson = json_encode([
        'time' => strtotime('2026-08-20 09:00:00') * 1000,
        'blocks' => [
            block_paragraph('EN 45545 часто вспоминают уже на этапе закупки ткани, когда сроки горят, а в ТЗ написано коротко: “материал должен соответствовать EN 45545”. Для железнодорожных и транспортных проектов такой формулировки обычно мало. Нужно понять, к какому изделию относится текстиль, где он будет стоять, какой уровень опасности требуется и какие документы подтвердят пригодность материала.'),
            block_paragraph('Эта статья не заменяет работу инженера, сертификационного специалиста или требований конкретного проекта. Задача проще: помочь заказчику подготовить запрос к производителю текстильного изделия и не потерять важные вопросы до расчета партии.'),

            block_header('Что такое EN 45545 простыми словами'),
            block_paragraph('EN 45545 - европейская серия требований по пожарной безопасности для железнодорожного транспорта. В закупках технического текстиля чаще всего обсуждают поведение материалов при огне: воспламеняемость, дымообразование, токсичность продуктов горения и требования к конкретному месту применения.'),
            block_paragraph('Важно: “EN 45545” само по себе не равно одному универсальному сертификату на любую ткань. Материал проверяют в привязке к назначению, условиям применения, группе требований и уровню опасности. Поэтому один и тот же текстиль может подходить для одной задачи и не подходить для другой.'),

            block_header('Что нужно запросить у проектной стороны'),
            block_paragraph('Перед подбором ткани лучше не начинать с каталога материалов. Сначала нужно получить исходные требования по проекту. Минимальный набор выглядит так:'),
            block_list([
                'тип изделия: чехол, штора, экран, обивка, защитный элемент, технологический текстиль;',
                'где изделие будет находиться: салон, техническая зона, багажная зона, наружная часть, ремонтный участок;',
                'требуемый уровень опасности или ссылка на раздел проектной документации;',
                'нужная группа требований, если она уже определена в проекте;',
                'нужно ли подтверждать не только ткань, но и готовое изделие, швы, фурнитуру, крепления или пропитку;',
                'какие документы принимает заказчик: протокол испытаний, сертификат, декларация, паспорт материала, письмо от поставщика.',
            ]),
            block_paragraph('Если этих данных нет, производитель может предложить огнестойкую ткань, но это еще не значит, что она закроет именно ваш тендер или внутренний стандарт. Лучше сразу попросить у проектной стороны формулировку требования полностью, а не пересказывать ее своими словами.'),

            block_header('Какие документы проверять по материалу'),
            block_paragraph('Для закупки важны не рекламные слова “огнестойкая ткань”, а проверяемые документы. Обычно стоит запросить протоколы испытаний или сертификаты с понятной привязкой к материалу: название, артикул, плотность, состав, покрытие, партия или действующая спецификация.'),
            block_paragraph('Проверьте, чтобы в документах совпадали ключевые параметры. Если в протоколе указана одна плотность, а в коммерческом предложении другая, это повод уточнить применимость. Если ткань идет с покрытием, пропиткой или ламинацией, нужно понимать, что именно испытывалось: базовая ткань или полный материал в рабочем виде.'),
            block_paragraph('Для готовых изделий отдельно обсудите нитки, ленты, липучки, молнии, люверсы, окантовку и усиления. Иногда основная ткань проходит по требованию, а вспомогательные элементы становятся слабым местом конструкции.'),

            block_header('Что написать в ТЗ для производителя'),
            block_paragraph('Хорошее ТЗ для текстильного изделия под EN 45545 должно быть конкретнее, чем “нужна негорючая ткань”. В запросе лучше указать:'),
            block_list([
                'назначение изделия и место установки;',
                'размеры, форму, способ крепления и сценарий использования;',
                'требуемые пожарные документы и формулировку из проекта;',
                'условия эксплуатации: влажность, загрязнения, мойка, истирание, температура, частота снятия;',
                'нужны ли цвет, маркировка, логотип, упаковка и повторяемость партии;',
                'объем первой партии и планируемые повторные поставки;',
                'кто принимает документы и в каком виде их нужно передать.',
            ]),
            block_paragraph('Такой запрос помогает сразу отсеять материалы без подтверждения, понять срок закупки ткани и рассчитать изделие с учетом фурнитуры, обработки края, усилений и упаковки.'),

            block_header('Частые ошибки при закупке'),
            block_paragraph('Первая ошибка - выбирать ткань только по слову “огнестойкая”. Для транспортных проектов важны конкретные испытания и применимость документов. Без этого материал может не пройти входной контроль даже при хороших физических свойствах.'),
            block_paragraph('Вторая ошибка - забывать про конструкцию изделия. Чехол, штора или экран состоят не только из полотна. Шов, молния, липучка, ремень и окантовка должны быть согласованы с требованиями проекта и реальной эксплуатацией.'),
            block_paragraph('Третья ошибка - заказывать материал до согласования документов. Если поставка срочная, кажется логичным купить ткань сразу. Но если заказчик потом не примет протокол или потребует другую группу требований, экономия времени исчезнет.'),

            block_header('Когда нужен образец'),
            block_paragraph('Образец полезен, если изделие серийное, дорогое, нестандартное по форме или будет проходить приемку у крупного заказчика. На образце можно проверить посадку, крепления, швы, маркировку, удобство снятия и комплект документов до запуска партии.'),
            block_paragraph('Для повторяемых поставок стоит зафиксировать артикул ткани, цвет, плотность, фурнитуру, выкройку и допуски. Тогда следующая партия будет меньше зависеть от ручных уточнений и переписки.'),

            block_header('Чек-лист перед звонком'),
            block_list([
                'Есть полная формулировка требования EN 45545 из проекта или тендера.',
                'Понятно, где изделие будет применяться и как его будут обслуживать.',
                'Есть размеры, фото, схема или чертеж изделия.',
                'Понятно, какие документы должен принять заказчик.',
                'Отдельно отмечены фурнитура, швы, крепления и усиления.',
                'Известен объем партии и желаемый срок поставки.',
            ]),
            block_paragraph('Если хотя бы два пункта пока неизвестны, расчет все равно можно начать, но лучше сразу написать, что данные уточняются. Производитель сможет предложить варианты и список вопросов, которые нужно закрыть до финального согласования.'),

            [
                'type' => 'cta',
                'data' => [
                    'heading' => 'Нужно изделие из технического текстиля под требования проекта?',
                    'text' => 'Позвоните в Пром-текстиль и подготовьте выдержку из ТЗ, размеры, фото и требования к документам. Так проще подобрать материал, конструкцию и фурнитуру без лишних переделок.',
                    'buttonText' => 'Позвонить',
                    'buttonHref' => 'tel:+79818172649',
                ],
            ],
        ],
        'version' => '2.29.1',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $contentHtml = render_editorjs_to_html($contentJson);
    $publishedAt = '2026-08-20 09:00:00';
    $stmt = $db->prepare(
        'INSERT INTO articles (
            title, slug, excerpt, content_json, content_html, category_id, author,
            reading_time_minutes, status, published_at, created_at, updated_at,
            seo_title, meta_description, h1, canonical_url, robots_index, robots_follow,
            focus_keyword, secondary_keywords, og_title, og_description,
            twitter_title, twitter_description, schema_type,
            include_in_sitemap, sitemap_priority, sitemap_changefreq
        ) VALUES (
            :title, :slug, :excerpt, :content_json, :content_html, :category_id, :author,
            :reading_time_minutes, :status, :published_at, :created_at, :updated_at,
            :seo_title, :meta_description, :h1, :canonical_url, 1, 1,
            :focus_keyword, :secondary_keywords, :og_title, :og_description,
            :twitter_title, :twitter_description, :schema_type,
            1, 0.6, :sitemap_changefreq
        )'
    );
    $stmt->execute([
        'title' => 'EN 45545 для текстиля: что проверить перед закупкой',
        'slug' => $slug,
        'excerpt' => 'Практичный чек-лист для закупки технического текстиля под EN 45545: требования проекта, документы, протоколы, фурнитура, образец и приемка.',
        'content_json' => $contentJson,
        'content_html' => $contentHtml,
        'category_id' => $categoryId,
        'author' => 'Пром-текстиль',
        'reading_time_minutes' => estimate_reading_minutes($contentHtml),
        'status' => 'published',
        'published_at' => $publishedAt,
        'created_at' => $publishedAt,
        'updated_at' => $publishedAt,
        'seo_title' => 'EN 45545 для текстиля: что проверить перед закупкой',
        'meta_description' => 'Что запросить перед закупкой текстиля под EN 45545: требования проекта, документы на материал, протоколы испытаний, фурнитура и образец.',
        'h1' => 'EN 45545 для текстиля: что проверить перед закупкой',
        'canonical_url' => 'https://пром-текстиль.рф/pages/blog-en-45545-dlya-tekstilya-chto-proverit-pered-zakupkoy.html',
        'focus_keyword' => 'EN 45545',
        'secondary_keywords' => 'огнестойкие ткани, технический текстиль для транспорта, текстиль для железнодорожного транспорта, пожарная безопасность материалов, EN 45545 текстиль',
        'og_title' => 'EN 45545 для текстиля перед закупкой',
        'og_description' => 'Какие требования и документы проверить перед заказом текстильного изделия под транспортный проект.',
        'twitter_title' => 'EN 45545 для текстиля перед закупкой',
        'twitter_description' => 'Чек-лист для закупки технического текстиля: проектные требования, протоколы, фурнитура, образец и приемка.',
        'schema_type' => 'BlogPosting',
        'sitemap_changefreq' => 'monthly',
    ]);

    set_seed_article_tags($db, (int) $db->lastInsertId(), [
        'EN 45545',
        'огнестойкие материалы',
        'технический текстиль',
        'транспорт',
    ]);
}

function seed_article_covers(PDO $db): void
{
    ensure_seed_article_cover(
        $db,
        'kak-snyat-tz-na-chehol-dlya-oborudovaniya',
        '/img/blog-covers/cover-tz-chehol-oborudovanie.svg',
        'Замер защитного чехла для оборудования'
    );
    ensure_seed_article_cover(
        $db,
        'kak-vybrat-material-dlya-chehla-pvh-oxford-cordura-brezent',
        '/img/blog-covers/cover-materialy-chehla.svg',
        'Образцы материалов для защитного чехла'
    );
    ensure_seed_article_cover(
        $db,
        'en-45545-dlya-tekstilya-chto-proverit-pered-zakupkoy',
        '/img/blog-covers/cover-en-45545-tekstilya.svg',
        'Текстиль под требования EN 45545'
    );
}

function ensure_seed_article_cover(PDO $db, string $slug, string $path, string $alt): void
{
    $mediaId = find_or_create_seed_media($db, $path, $alt);
    $stmt = $db->prepare(
        'UPDATE articles
         SET cover_media_id = :media_id,
             og_image_id = :media_id,
             twitter_image_id = :media_id,
             updated_at = CURRENT_TIMESTAMP
         WHERE slug = :slug'
    );
    $stmt->execute(['media_id' => $mediaId, 'slug' => $slug]);
}

function find_or_create_seed_media(PDO $db, string $path, string $alt): int
{
    $stmt = $db->prepare('SELECT id FROM media WHERE path = :path');
    $stmt->execute(['path' => $path]);
    $row = $stmt->fetch();
    if ($row) {
        return (int) $row['id'];
    }

    $absolutePath = str_starts_with($path, '/')
        ? dirname(__DIR__) . $path
        : dirname(__DIR__) . '/uploads/blog/' . $path;
    $fileSize = is_file($absolutePath) ? filesize($absolutePath) : 0;
    $filename = basename($path);
    $stmt = $db->prepare(
        'INSERT INTO media (
            filename, original_filename, path, mime_type, file_size,
            width, height, alt, title, caption, description
        ) VALUES (
            :filename, :original_filename, :path, :mime_type, :file_size,
            :width, :height, :alt, :title, :caption, :description
        )'
    );
    $stmt->execute([
        'filename' => $filename,
        'original_filename' => $filename,
        'path' => $path,
        'mime_type' => 'image/svg+xml',
        'file_size' => $fileSize ?: 1,
        'width' => 1600,
        'height' => 1000,
        'alt' => $alt,
        'title' => $alt,
        'caption' => '',
        'description' => '',
    ]);
    return (int) $db->lastInsertId();
}

function block_paragraph(string $text): array
{
    return ['type' => 'paragraph', 'data' => ['text' => $text]];
}

function block_header(string $text, int $level = 2): array
{
    return ['type' => 'header', 'data' => ['text' => $text, 'level' => $level]];
}

function block_list(array $items): array
{
    return ['type' => 'list', 'data' => ['style' => 'unordered', 'items' => $items]];
}

function find_or_create_seed_category(PDO $db, string $name): ?int
{
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

function set_seed_article_tags(PDO $db, int $articleId, array $tagNames): void
{
    foreach ($tagNames as $name) {
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
        $db->prepare('INSERT OR IGNORE INTO article_tags (article_id, tag_id) VALUES (:article_id, :tag_id)')
            ->execute(['article_id' => $articleId, 'tag_id' => $tagId]);
    }
}
