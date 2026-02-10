<?php
/**
 * AuraStore - Authentication Middleware
 */
session_start();
require_once __DIR__ . '/../config/database.php';

/**
 * Check if user is logged in
 */
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

/**
 * Login user
 */
function loginUser($email, $password)
{
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        return true;
    }
    return false;
}

/**
 * Register a new seller
 */
function registerUser($name, $email, $password, $phone = '')
{
    $db = getDB();

    // Check email uniqueness
    $check = $db->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        return ['error' => 'Cet email est déjà utilisé.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (full_name, email, password, phone, role) VALUES (?, ?, ?, ?, 'seller')");
    $stmt->execute([$name, $email, $hash, $phone]);

    $userId = $db->lastInsertId();

    // Give 50 free credits
    $credit = $db->prepare("INSERT INTO credits (user_id, amount, type, description) VALUES (?, 50, 'purchase', 'Crédits de bienvenue')");
    $credit->execute([$userId]);

    // Auto-login
    $_SESSION['user_id'] = $userId;
    $_SESSION['role'] = 'seller';
    $_SESSION['full_name'] = $name;

    return ['success' => true, 'user_id' => $userId];
}
