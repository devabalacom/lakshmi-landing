<?php
require_once __DIR__ . '/includes/session.php';

$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    die('admin/config.php is missing. Copy admin/config.sample.php to admin/config.php and fill in real values.');
}
require_once $configPath;
require_once dirname(__DIR__) . '/includes/db.php';
require_once __DIR__ . '/includes/login-throttle.php';

if (admin_is_logged_in()) {
    header('Location: /admin/index.php');
    exit;
}

$error = null;
$returnTo = $_GET['return'] ?? $_POST['return'] ?? '/admin/index.php';
if (!str_starts_with($returnTo, '/admin/')) {
    $returnTo = '/admin/index.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = blog_db();
    $ip = login_client_ip();

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'Сессия истекла, попробуйте ещё раз.';
    } elseif (login_is_throttled($db, $ip)) {
        $error = 'Слишком много попыток входа. Подождите несколько минут.';
    } else {
        login_record_attempt($db, $ip);
        $login = trim($_POST['login'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        $loginOk = hash_equals(ADMIN_LOGIN, $login);
        $passwordOk = ADMIN_PASSWORD_HASH !== '' && password_verify($password, ADMIN_PASSWORD_HASH);

        if ($loginOk && $passwordOk) {
            admin_login_success();
            header('Location: ' . $returnTo);
            exit;
        }
        $error = 'Неверный логин или пароль.';
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Вход · Админ-панель блога</title>
<style>
    :root{--g0:#caa24a;--g1:#e8cd8a;--g2:#d4a017;--ink:#141414;--ink2:#4d4438;--ink3:#8a7e62;--bg:#f6efe1;--line:#e2ddd0;--fd:'Georgia',serif;--fm:'Courier New',monospace}
    *{box-sizing:border-box}
    body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:var(--bg);font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:var(--ink)}
    .card{width:100%;max-width:360px;background:#fff;border:1px solid var(--line);border-radius:16px;padding:2.2rem;box-shadow:0 20px 60px rgba(20,20,20,.08)}
    h1{font-size:1.3rem;margin:0 0 .3rem;font-weight:800}
    p.sub{margin:0 0 1.6rem;color:var(--ink3);font-size:.85rem}
    label{display:block;font-size:.78rem;font-weight:600;color:var(--ink2);margin:0 0 .35rem}
    input[type=text],input[type=password]{width:100%;padding:.7rem .85rem;border:1px solid var(--line);border-radius:8px;font-size:.95rem;margin-bottom:1.1rem}
    input[type=text]:focus,input[type=password]:focus{outline:2px solid var(--g2);outline-offset:1px}
    button{width:100%;padding:.75rem;border:0;border-radius:8px;background:linear-gradient(135deg,var(--g2),var(--g0));color:#1a1408;font-weight:700;font-size:.95rem;cursor:pointer}
    button:hover{filter:brightness(1.05)}
    .error{background:#fdecea;color:#a33;border:1px solid #f3c9c4;border-radius:8px;padding:.6rem .8rem;font-size:.85rem;margin-bottom:1.1rem}
</style>
</head>
<body>
<form class="card" method="post" novalidate>
    <h1>Пром-текстиль</h1>
    <p class="sub">Вход в админ-панель блога</p>
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?= csrf_field() ?>
    <input type="hidden" name="return" value="<?= htmlspecialchars($returnTo, ENT_QUOTES, 'UTF-8') ?>">
    <label for="login">Логин</label>
    <input type="text" id="login" name="login" autocomplete="username" required autofocus>
    <label for="password">Пароль</label>
    <input type="password" id="password" name="password" autocomplete="current-password" required>
    <button type="submit">Войти</button>
</form>
</body>
</html>
