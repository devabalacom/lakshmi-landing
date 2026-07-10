<?php
/**
 * Session bootstrap shared by every admin/API entry point, including login.php
 * itself (which must NOT go through auth.php's redirect-if-not-logged-in check).
 */

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => $isHttps,
    ]);
    session_start();
}

define('ADMIN_SESSION_IDLE_TIMEOUT', 60 * 60 * 4); // 4h idle timeout
define('ADMIN_SESSION_ABSOLUTE_TIMEOUT', 60 * 60 * 12); // 12h absolute timeout

function admin_session_expired(): bool
{
    if (empty($_SESSION['admin_authenticated'])) {
        return true;
    }
    $now = time();
    if (($now - ($_SESSION['admin_last_activity'] ?? 0)) > ADMIN_SESSION_IDLE_TIMEOUT) {
        return true;
    }
    if (($now - ($_SESSION['admin_login_time'] ?? 0)) > ADMIN_SESSION_ABSOLUTE_TIMEOUT) {
        return true;
    }
    return false;
}

function admin_touch_session(): void
{
    $_SESSION['admin_last_activity'] = time();
}

function admin_is_logged_in(): bool
{
    if (empty($_SESSION['admin_authenticated'])) {
        return false; // never authenticated in this session — nothing to clean up
    }
    if (admin_session_expired()) {
        admin_logout(); // WAS authenticated but timed out — tear down properly
        return false;
    }
    admin_touch_session();
    return true;
}

function admin_login_success(): void
{
    session_regenerate_id(true);
    $_SESSION['admin_authenticated'] = true;
    $_SESSION['admin_login_time'] = time();
    $_SESSION['admin_last_activity'] = time();
}

function admin_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_verify(?string $token): bool
{
    return is_string($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/** Called by any state-changing admin/API endpoint (form POST or AJAX). */
function csrf_require(): void
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!csrf_verify($token)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit;
    }
}
