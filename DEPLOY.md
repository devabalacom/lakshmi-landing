# Публикация на GitHub

## Вариант 1: Через веб-интерфейс GitHub

### Шаг 1: Создайте репозиторий
1. Откройте https://github.com/new
2. Repository name: `lakshmi-landing`
3. Description: `Landing page for Lakshmi industrial textile production`
4. Выберите **Public**
5. **НЕ** ставьте галочки "Initialize with README"
6. Нажмите **Create repository**

### Шаг 2: Подключите к локальному репо
Скопируйте URL созданного репо (будет выглядеть как: `https://github.com/devabalacom/lakshmi-landing.git`)

Выполните на VPS:
```bash
cd /home/aroma_openclaw/.openclaw/workspace-joni/lakshmi-landing
git remote add origin https://github.com/YOUR_USERNAME/lakshmi-landing.git
git branch -M main
git push -u origin main
```

## Вариант 2: Через GitHub CLI (нужны дополнительные права)

Обновите PAT токен с правами:
- ✅ `repo` (Full control)
- ✅ `workflow`
- ✅ `read:org`
- ✅ **`public_repo`** (Create public repositories)

Затем:
```bash
cd /home/aroma_openclaw/.openclaw/workspace-joni/lakshmi-landing
gh repo create lakshmi-landing --public --source=. --description="Landing for Lakshmi" --push
```

## После публикации

### GitHub Pages (бесплатный хостинг)

1. Откройте настройки репозитория: **Settings** → **Pages**
2. Source: **Deploy from a branch**
3. Branch: **main** → папка: **/ (root)**
4. Нажмите **Save**

Сайт будет доступен через 1-2 минуты по адресу:
```
https://YOUR_USERNAME.github.io/lakshmi-landing/
```

### Netlify (альтернатива)

1. Откройте https://app.netlify.com/
2. **New site from Git**
3. Выберите GitHub → найдите репо `lakshmi-landing`
4. Build settings: оставьте пустыми (статический HTML)
5. **Deploy site**

Сайт получит адрес типа `random-name-12345.netlify.app` (можно изменить)

## Обновление сайта

После изменений:
```bash
cd /home/aroma_openclaw/.openclaw/workspace-joni/lakshmi-landing
git add .
git commit -m "Update content"
git push
```

GitHub Pages или Netlify автоматически обновят сайт.

## Кастомный домен

### Для GitHub Pages:
1. Settings → Pages → Custom domain
2. Введите ваш домен (например: `lakshmi.com`)
3. Добавьте CNAME запись у регистратора домена:
   ```
   CNAME: lakshmi.com → devabalacom.github.io
   ```

### Для Netlify:
1. Site settings → Domain management
2. Add custom domain
3. Следуйте инструкциям (добавить A/CNAME записи)

---

## Текущий статус

✅ Git репо инициализирован
✅ Первый коммит создан
⏳ Ожидает публикации на GitHub

Выберите **Вариант 1** (создать репо через веб) или обновите права токена для **Варианта 2**.
