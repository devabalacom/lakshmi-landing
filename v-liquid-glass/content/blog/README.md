# Git-backed blog articles

New weekly articles should be published as one JSON file in this directory plus
any required cover asset under `v-liquid-glass/img/blog-covers/`.

The public CMS imports these files into SQLite on request via
`includes/seed-articles.php`. This keeps article content reviewable in git while
avoiding manual PHP edits for every new post.

Required fields:

- `title`
- `slug`
- `excerpt`
- `published_at`
- `blocks` in Editor.js block format

Recommended fields:

- `category`
- `cover_path`
- `cover_alt`
- `focus_keyword`
- `secondary_keywords`
- `meta_description`
- `tags`

After adding an article, push to `main`; GitHub Actions deploys
`v-liquid-glass/**` to production.
