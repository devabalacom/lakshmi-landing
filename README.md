# Пром-текстиль — Производство изделий из технического текстиля

Современный лендинг для производственной компании. Дизайн: light premium / brushed gold / liquid glass.

## Стек

- Vanilla HTML / CSS / JS (без фреймворков)
- GSAP + ScrollTrigger для анимаций
- Google Fonts: Calistoga, Cormorant Garamond, Manrope, JetBrains Mono
- GitHub Actions → FTP deploy на пром-текстиль.рф

## Структура

```
v-liquid-glass/
├── index.html              # Главная страница
├── privacy.html            # Политика конфиденциальности
├── services/               # 9 страниц направлений
│   ├── chekhly-tenty-v2.html
│   ├── specodezhda-v2.html
│   ├── tactical-v2.html
│   ├── medical-v2.html
│   ├── interior-v2.html
│   ├── transport-v2.html
│   ├── agro-v2.html
│   ├── fire-v2.html
│   └── cleanroom-v2.html
└── pages/
    ├── about-v2.html
    └── contacts-v2.html
```

## Главная страница — секции

1. **Hero** — заголовок, подзаголовок, попап-форма расчёта, стеклянная панель "от 1 до 1 млн"
2. **Изделия** — 9 карточек направлений (закруглённые, с hover-эффектом)
3. **Задачи** — 6 карточек: заменить аналог / сшить по образцу / СТМ / укрыть / защитить / разделить зону
4. **Цифры** — тёмная полоса: 20 лет / 1400 м² / 50+ / от 1 единицы
5. **О нас** — 4 карточки полного цикла
6. **Почему мы** — 6 тёмных карточек преимуществ
7. **Процесс** — вертикальный таймлайн 5 шагов
8. **FAQ** — аккордеон
9. **Контакты** — контактные ячейки + CTA-блок

## Деплой

Push в `main` с изменениями в `v-liquid-glass/**` автоматически триггерит GitHub Actions → FTP на сервер.

```
.github/workflows/deploy.yml
```

Секреты в настройках репозитория: `FTP_HOST`, `FTP_USERNAME`, `FTP_PASSWORD`.

## Блог

Новые еженедельные статьи добавляются как git-backed JSON:

```
v-liquid-glass/content/blog/<slug>.json
v-liquid-glass/img/blog-covers/<cover>.webp
```

CMS импортирует эти JSON-файлы в SQLite через `v-liquid-glass/includes/seed-articles.php`
при открытии блога. Старые PHP seed-функции оставлены только для совместимости.

Контроль свежести:

- `.github/workflows/blog-watchdog.yml` ежедневно проверяет, что последняя git-backed статья не старше 10 дней;
- тот же workflow проверяет, что URL последней статьи открывается на живом сайте;
- фактическая публикация остается задачей OpenClaw heartbeat: подготовить статью, добавить JSON + OpenAI Image 2 WebP-обложку, запушить в `main`, проверить deploy и live URL.

## Локальный запуск

```bash
cd v-liquid-glass
python3 -m http.server 8000
# открыть http://localhost:8000
```

## Контакты производства

- Телефон: +7 968 599-96-65
- Email: info@laksmi-prom.ru
- Адрес: Санкт-Петербург, Литовская 12 к Д
