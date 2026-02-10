<?php
/**
 * AuraStore - Products API
 * Handles CRUD operations for products
 */
require_once 'includes/auth.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Non authentifié']);
    exit();
}

$user = getCurrentUser();
if (!$user || !$user['store_id']) {
    echo json_encode(['error' => 'Aucune boutique associée']);
    exit();
}

$action = $_GET['action'] ?? '';
$db = getDB();

switch ($action) {
    case 'create':
        $name = trim($_POST['name'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $oldPrice = !empty($_POST['old_price']) ? floatval($_POST['old_price']) : null;
        $description = trim($_POST['description'] ?? '');
        $sizes = trim($_POST['sizes'] ?? '');
        $colors = trim($_POST['colors'] ?? '');
        $stock = intval($_POST['stock'] ?? 0);
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;

        if (empty($name) || $price <= 0) {
            echo json_encode(['error' => 'Nom et prix sont obligatoires']);
            exit();
        }

        $imageUrl = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $upload = uploadImage($_FILES['image'], 'products');
            if (isset($upload['url']))
                $imageUrl = $upload['url'];
        }

        $stmt = $db->prepare("INSERT INTO products (store_id, name, description, price, old_price, image_url, sizes, colors, stock, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user['store_id'], $name, $description, $price, $oldPrice, $imageUrl, $sizes, $colors, $stock, $isFeatured]);

        echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
        break;

    case 'update':
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $price = floatval($_POST['price'] ?? 0);

        // Verify product belongs to this store
        $check = $db->prepare("SELECT id FROM products WHERE id = ? AND store_id = ?");
        $check->execute([$id, $user['store_id']]);
        if (!$check->fetch()) {
            echo json_encode(['error' => 'Produit non trouvé']);
            exit();
        }

        $stmt = $db->prepare("UPDATE products SET name=?, description=?, price=?, old_price=?, sizes=?, colors=?, stock=?, is_featured=? WHERE id=? AND store_id=?");
        $stmt->execute([
            $name,
            trim($_POST['description'] ?? ''),
            $price,
            !empty($_POST['old_price']) ? floatval($_POST['old_price']) : null,
            trim($_POST['sizes'] ?? ''),
            trim($_POST['colors'] ?? ''),
            intval($_POST['stock'] ?? 0),
            isset($_POST['is_featured']) ? 1 : 0,
            $id,
            $user['store_id']
        ]);

        // Update image if a new one is uploaded
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $upload = uploadImage($_FILES['image'], 'products');
            if (isset($upload['url'])) {
                $db->prepare("UPDATE products SET image_url = ? WHERE id = ?")->execute([$upload['url'], $id]);
            }
        }

        echo json_encode(['success' => true]);
        break;

    case 'delete':
        $id = intval($_POST['id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM products WHERE id = ? AND store_id = ?");
        $stmt->execute([$id, $user['store_id']]);
        echo json_encode(['success' => true]);
        break;

    case 'list':
        $products = getStoreProducts($user['store_id']);
        echo json_encode(['success' => true, 'products' => $products]);
        break;

    default:
        echo json_encode(['error' => 'Action inconnue']);
}
