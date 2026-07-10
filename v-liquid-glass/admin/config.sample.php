<?php
/**
 * Copy this file to config.php and fill in real values.
 * config.php is gitignored and excluded from the FTP deploy — it must be
 * created once by hand on the production server (see README-BLOG-CMS.md).
 */

// Login used on the /admin/ sign-in form.
define('ADMIN_LOGIN', 'admin');

// Generate with: php -r "echo password_hash('your-password-here', PASSWORD_DEFAULT), PHP_EOL;"
define('ADMIN_PASSWORD_HASH', '');

// Random secret used to key the session/CSRF tokens.
// Generate with: php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
define('SESSION_SECRET', '');
