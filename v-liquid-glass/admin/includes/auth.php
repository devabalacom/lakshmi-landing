<?php
/**
 * require_once this at the top of every protected /admin/*.php or /api/*.php
 * entry point. Redirects to login (or 401s for JSON endpoints) if not authenticated.
 */

require_once __DIR__ . '/session.php';

$configPath = dirname(__DIR__) . '/config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    die('admin/config.php is missing. Copy admin/config.sample.php to admin/config.php and fill in real values.');
}
require_once $configPath;

function admin_require_login(): void
{
    if (!admin_is_logged_in()) {
        if (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
            http_response_code(401);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['error' => 'Not authenticated']);
            exit;
        }
        $returnTo = urlencode($_SERVER['REQUEST_URI'] ?? '/admin/');
        header('Location: /admin/login.php?return=' . $returnTo);
        exit;
    }
}

admin_require_login();
