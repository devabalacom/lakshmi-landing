<?php
/**
 * Include after admin/includes/auth.php. Expects $pageTitle (string) and
 * optionally $activeNav (one of: dashboard, articles, edit, media, settings).
 */
$activeNav = $activeNav ?? '';
?><!doctype html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= htmlspecialchars($pageTitle ?? 'Админ-панель', ENT_QUOTES, 'UTF-8') ?> · Админ-панель блога</title>
<link rel="stylesheet" href="/admin/assets/admin.css">
</head>
<body>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <div class="admin-brand">Пром-текстиль<span>Блог CMS</span></div>
        <nav class="admin-nav">
            <a href="/admin/index.php" class="<?= $activeNav === 'dashboard' ? 'active' : '' ?>">Дашборд</a>
            <a href="/admin/articles.php" class="<?= $activeNav === 'articles' ? 'active' : '' ?>">Статьи</a>
            <a href="/admin/article-edit.php?id=new" class="<?= $activeNav === 'edit' ? 'active' : '' ?>">Создать статью</a>
            <a href="/admin/media.php" class="<?= $activeNav === 'media' ? 'active' : '' ?>">Медиатека</a>
            <a href="/admin/settings.php" class="<?= $activeNav === 'settings' ? 'active' : '' ?>">Настройки</a>
        </nav>
        <form action="/admin/logout.php" method="post" class="admin-logout-form">
            <?= csrf_field() ?>
            <button type="submit" class="admin-logout-btn">Выйти</button>
        </form>
    </aside>
    <main class="admin-main">
