<?php
/**
 * Simple per-IP login throttling backed by the login_attempts table.
 * Not a full rate-limiter — just enough to slow down naive brute-forcing
 * of a single-admin panel.
 */

const LOGIN_THROTTLE_WINDOW_SECONDS = 900; // 15 minutes
const LOGIN_THROTTLE_MAX_ATTEMPTS = 5;

function login_client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function login_record_attempt(PDO $db, string $ip): void
{
    $stmt = $db->prepare('INSERT INTO login_attempts (ip, attempted_at) VALUES (:ip, CURRENT_TIMESTAMP)');
    $stmt->execute(['ip' => $ip]);
}

function login_recent_attempt_count(PDO $db, string $ip): int
{
    $stmt = $db->prepare(
        "SELECT COUNT(*) AS c FROM login_attempts
         WHERE ip = :ip AND attempted_at >= datetime('now', :window)"
    );
    $stmt->execute(['ip' => $ip, 'window' => '-' . LOGIN_THROTTLE_WINDOW_SECONDS . ' seconds']);
    return (int) $stmt->fetch()['c'];
}

function login_is_throttled(PDO $db, string $ip): bool
{
    return login_recent_attempt_count($db, $ip) >= LOGIN_THROTTLE_MAX_ATTEMPTS;
}
