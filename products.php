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
        $vtoTarget = intval($_POST['vto_target_image'] ?? 1);

        if (empty($name) || $price <= 0) {
            echo json_encode(['error' => 'Nom et prix sont obligatoires']);
            exit();
        }

        $images = ['', '', ''];
        for ($i = 1; $i <= 3; $i++) {
            $key = $i === 1 ? 'image' : 'image_' . $i;
            if (isset($_FILES[$key]) && $_FILES[$key]['error'] === 0) {
                $upload = uploadImage($_FILES[$key], 'products');
                if (isset($upload['url']))
                    $images[$i - 1] = $upload['url'];
            }
        }

        $stmt = $db->prepare("INSERT INTO products (store_id, name, description, price, old_price, image_url, image_2_url, image_3_url, sizes, colors, stock, is_featured, vto_target_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user['store_id'], $name, $description, $price, $oldPrice, $images[0], $images[1], $images[2], $sizes, $colors, $stock, $isFeatured, $vtoTarget]);

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

        $stmt = $db->prepare("UPDATE products SET name=?, description=?, price=?, old_price=?, sizes=?, colors=?, stock=?, is_featured=?, vto_target_image=? WHERE id=? AND store_id=?");
        $stmt->execute([
            $name,
            trim($_POST['description'] ?? ''),
            $price,
            !empty($_POST['old_price']) ? floatval($_POST['old_price']) : null,
            trim($_POST['sizes'] ?? ''),
            trim($_POST['colors'] ?? ''),
            intval($_POST['stock'] ?? 0),
            isset($_POST['is_featured']) ? 1 : 0,
            intval($_POST['vto_target_image'] ?? 1),
            $id,
            $user['store_id']
        ]);

        // Update images if new ones are uploaded
        for ($i = 1; $i <= 3; $i++) {
            $key = $i === 1 ? 'image' : 'image_' . $i;
            $dbCol = $i === 1 ? 'image_url' : 'image_' . $i . '_url';
            if (isset($_FILES[$key]) && $_FILES[$key]['error'] === 0) {
                $upload = uploadImage($_FILES[$key], 'products');
                if (isset($upload['url'])) {
                    $db->prepare("UPDATE products SET $dbCol = ? WHERE id = ?")->execute([$upload['url'], $id]);
                }
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
