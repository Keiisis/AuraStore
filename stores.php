<?php
/**
 * AuraStore - Stores API
 * Create, update, and fetch store info
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Non authentifié']);
    exit();
}

$user = getCurrentUser();
$action = $_GET['action'] ?? '';
$db = getDB();

switch ($action) {
    case 'create':
        $storeName = trim($_POST['store_name'] ?? '');
        $category = $_POST['category'] ?? 'streetwear';
        $whatsapp = trim($_POST['whatsapp'] ?? '');

        if (empty($storeName)) {
            echo json_encode(['error' => 'Le nom de la boutique est obligatoire']);
            exit();
        }

        $slug = slugify($storeName);

        $logoUrl = '';
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
            $upload = uploadImage($_FILES['logo'], 'logos');
            if (isset($upload['url']))
                $logoUrl = $upload['url'];
        }

        $stmt = $db->prepare("INSERT INTO stores (user_id, store_name, store_slug, category, logo_url, whatsapp_number) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user['id'], $storeName, $slug, $category, $logoUrl, $whatsapp]);

        echo json_encode(['success' => true, 'slug' => $slug, 'id' => $db->lastInsertId()]);
        break;

    case 'update':
        if (!$user['store_id']) {
            echo json_encode(['error' => 'Pas de boutique']);
            exit();
        }

        $fields = [];
        $params = [];

        if (isset($_POST['store_name']) && !empty(trim($_POST['store_name']))) {
            $fields[] = 'store_name = ?';
            $params[] = trim($_POST['store_name']);
        }
        if (isset($_POST['whatsapp'])) {
            $fields[] = 'whatsapp_number = ?';
            $params[] = trim($_POST['whatsapp']);
        }
        if (isset($_POST['description'])) {
            $fields[] = 'description = ?';
            $params[] = trim($_POST['description']);
        }
        if (isset($_POST['currency'])) {
            $fields[] = 'currency = ?';
            $params[] = $_POST['currency'];
        }

        if (!empty($fields)) {
            $params[] = $user['store_id'];
            $sql = "UPDATE stores SET " . implode(', ', $fields) . " WHERE id = ?";
            $db->prepare($sql)->execute($params);
        }

        // Handle logo upload
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
            $upload = uploadImage($_FILES['logo'], 'logos');
            if (isset($upload['url'])) {
                $db->prepare("UPDATE stores SET logo_url = ? WHERE id = ?")->execute([$upload['url'], $user['store_id']]);
            }
        }

        // Redirect back to dashboard for form submissions
        if (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            echo json_encode(['success' => true]);
        } else {
            header("Location: ../dashboard.php");
        }
        break;

    case 'stats':
        $stats = getSellerStats($user['id']);
        echo json_encode(['success' => true, 'stats' => $stats]);
        break;

    default:
        echo json_encode(['error' => 'Action inconnue']);
}
