<?php
/**
 * AuraStore - Security & Utility Helpers
 */

/**
 * Initialize secure session settings
 */
function initSecureSession()
{
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
        session_start();
    }
}

/**
 * Set common security headers
 */
function setSecurityHeaders()
{
    header("X-Frame-Options: SAMEORIGIN");
    header("X-XSS-Protection: 1; mode=block");
    header("X-Content-Type-Options: nosniff");
}

/**
 * Validate CSRF token
 */
function validateCSRF()
{
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || $token !== ($_SESSION['csrf_token'] ?? '')) {
        die("Erreur de sécurité : Jeton CSRF invalide.");
    }
}

/**
 * Generate and return CSRF token
 */
function csrfField()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
}

/**
 * Basic rate limiting (stored in session for simplicity in demo)
 */
function rateLimit($key, $max, $period)
{
    if (!isset($_SESSION['rate_limits']))
        $_SESSION['rate_limits'] = [];

    $now = time();
    $limits = &$_SESSION['rate_limits'];

    if (!isset($limits[$key])) {
        $limits[$key] = ['count' => 1, 'reset' => $now + $period];
    } else {
        if ($now > $limits[$key]['reset']) {
            $limits[$key] = ['count' => 1, 'reset' => $now + $period];
        } else {
            if ($limits[$key]['count'] >= $max) {
                die("Trop de requêtes. Veuillez patienter.");
            }
            $limits[$key]['count']++;
        }
    }
}

/**
 * Sanitize user input
 */
function sanitizeInput($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Validate file upload
 */
function validateFileUpload($file)
{
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($file['type'], $allowed)) {
        return ['error' => 'Format de fichier non autorisé.'];
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        return ['error' => 'Image trop lourde (max 2MB).'];
    }
    return ['success' => true];
}
