<?php
/**
 * AuraStore - Helper Functions Library
 */
require_once __DIR__ . '/../config/database.php';

/**
 * Get all themes
 */
function getThemes()
{
    return include __DIR__ . '/../config/themes.php';
}

/**
 * Get a single theme by key
 */
function getTheme($key)
{
    $themes = getThemes();
    return $themes[$key] ?? $themes['streetwear'];
}

/**
 * Get current user with store info
 */
function getCurrentUser()
{
    if (!isset($_SESSION['user_id']))
        return null;
    $db = getDB();
    $stmt = $db->prepare("
        SELECT u.*, s.id as store_id, s.store_name, s.store_slug, s.category, s.is_active,
               s.total_views, s.whatsapp_number,
               (SELECT COALESCE(SUM(amount),0) FROM credits WHERE user_id=u.id AND type='purchase') as credits_total,
               (SELECT COUNT(*) FROM tryon_sessions WHERE store_id=s.id) as credits_used
        FROM users u
        LEFT JOIN stores s ON u.id = s.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/**
 * Generate URL-safe slug
 */
function slugify($text)
{
    $text = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $text), '-'));
    $db = getDB();
    $slug = $text;
    $i = 1;
    while ($db->query("SELECT COUNT(*) FROM stores WHERE store_slug='" . $slug . "'")->fetchColumn() > 0) {
        $slug = $text . '-' . $i++;
    }
    return $slug;
}

/**
 * Upload image to local storage
 */
function uploadImage($file, $folder = 'products')
{
    $dir = __DIR__ . '/../public/uploads/' . $folder . '/';
    if (!is_dir($dir))
        mkdir($dir, 0755, true);

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($ext, $allowed))
        return ['error' => 'Type de fichier non supporté'];
    if ($file['size'] > 5 * 1024 * 1024)
        return ['error' => 'Fichier trop volumineux (max 5MB)'];

    $name = uniqid() . '_' . time() . '.' . $ext;
    $path = $dir . $name;

    if (move_uploaded_file($file['tmp_name'], $path)) {
        return ['url' => 'public/uploads/' . $folder . '/' . $name];
    }
    return ['error' => 'Erreur d\'upload'];
}

/**
 * Get store by slug
 */
function getStoreBySlug($slug)
{
    $db = getDB();
    $stmt = $db->prepare("SELECT s.*, u.full_name as owner_name FROM stores s JOIN users u ON s.user_id = u.id WHERE s.store_slug = ? AND s.is_active = 1");
    $stmt->execute([$slug]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get products for a store
 */
function getStoreProducts($storeId, $limit = 100, $featuredOnly = false)
{
    $db = getDB();
    $sql = "SELECT p.*, (SELECT COUNT(*) FROM tryon_sessions WHERE product_id=p.id) as total_tryons 
            FROM products p WHERE p.store_id = ?";
    if ($featuredOnly)
        $sql .= " AND p.is_featured = 1";
    $sql .= " AND p.is_active = 1 ORDER BY p.created_at DESC LIMIT ?";

    $stmt = $db->prepare($sql);
    $stmt->execute([$storeId, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get seller statistics
 */
function getSellerStats($userId)
{
    $db = getDB();

    $store = $db->prepare("SELECT * FROM stores WHERE user_id = ?");
    $store->execute([$userId]);
    $storeData = $store->fetch(PDO::FETCH_ASSOC);

    if (!$storeData) {
        return [
            'views' => 0,
            'tryons' => 0,
            'products' => 0,
            'orders' => ['total' => 0, 'confirmed' => 0],
            'weekly_tryons' => [],
            'store' => null
        ];
    }

    $sid = $storeData['id'];

    // Product count
    $prodCount = $db->prepare("SELECT COUNT(*) FROM products WHERE store_id = ?");
    $prodCount->execute([$sid]);

    // Try-on count
    $tryonCount = $db->prepare("SELECT COUNT(*) FROM tryon_sessions WHERE store_id = ?");
    $tryonCount->execute([$sid]);

    // Orders
    $orders = $db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status='confirmed' THEN 1 ELSE 0 END) as confirmed FROM orders WHERE store_id = ?");
    $orders->execute([$sid]);
    $orderData = $orders->fetch(PDO::FETCH_ASSOC);

    // Weekly tryons (Postgres Syntax)
    $weekly = $db->prepare("
        SELECT created_at::DATE as day, COUNT(*) as count 
        FROM tryon_sessions 
        WHERE store_id = ? 
        AND created_at >= NOW() - INTERVAL '7 DAY' 
        GROUP BY created_at::DATE 
        ORDER BY day ASC
    ");
    $weekly->execute([$sid]);

    return [
        'views' => (int) ($storeData['total_views'] ?? 0),
        'tryons' => (int) $tryonCount->fetchColumn(),
        'products' => (int) $prodCount->fetchColumn(),
        'orders' => $orderData,
        'weekly_tryons' => $weekly->fetchAll(PDO::FETCH_ASSOC),
        'store' => $storeData
    ];
}

/**
 * Increment store view
 */
function incrementView($storeId)
{
    $db = getDB();
    $stmt = $db->prepare("UPDATE stores SET total_views = total_views + 1 WHERE id = ?");
    $stmt->execute([$storeId]);
}

/**
 * Format price
 */
function formatPrice($price, $currency = 'XAF')
{
    $symbols = ['XAF' => 'FCFA', 'EUR' => '€', 'USD' => '$', 'XOF' => 'FCFA', 'GHS' => 'GH₵', 'NGN' => '₦', 'MAD' => 'DH'];
    $sym = $symbols[$currency] ?? $currency;
    return number_format($price, 0, ',', ' ') . ' ' . $sym;
}

/**
 * Check if user is admin
 */
function requireAdmin()
{
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        header("Location: dashboard.php");
        exit();
    }
}

/**
 * Require login
 */
function requireLogin()
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}
