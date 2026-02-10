<?php
/**
 * AuraStore - Intelligent VTO Proxy
 * Automatically switches between Free (HuggingFace) and Pro (Fal.ai)
 */
require_once '../includes/functions.php';
// require_once '../includes/security.php'; // Temporary comment if security.php doesn't exist yet

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// ═══ 1. Get configuration from Database ═══
$db = getDB();
try {
    $settingsRes = $db->query("SELECT * FROM platform_settings")->fetchAll();
    $settings = [];
    foreach ($settingsRes as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    $settings = ['vto_provider' => 'free', 'fal_api_key' => ''];
}

$vtoProvider = $settings['vto_provider'] ?? 'free';
$falKey = $settings['fal_api_key'] ?? "";
$hfToken = $settings['hf_token'] ?? "";

$data = json_decode(file_get_contents('php://input'), true);
$productImage = $data['product_image'] ?? '';
$userImage = $data['user_image'] ?? '';

if (!$productImage || !$userImage) {
    echo json_encode(['error' => 'Données manquantes']);
    exit();
}

// ═══ 2. Logic Selection ═══

if ($vtoProvider === 'fal' && !empty($falKey)) {
    // 🚀 PRO MODE: FAL.AI
    $payload = [
        "human_image_url" => $userImage,
        "garment_image_url" => $productImage
    ];
    $ch = curl_init("https://fal.run/fal-ai/idm-vton");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Key $falKey", "Content-Type: application/json"]);
    $response = curl_exec($ch);
    curl_close($ch);
    echo $response;
} else {
    // ☁️ FREE MODE: HuggingFace Space Bridge (Real but Unstable)
    try {
        $hfSpaceUrl = $settings['hf_space_url'] ?? "https://yisol-idm-vton.hf.space/api/predict";

        // Local testing bridge
        $isLocal = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || $_SERVER['HTTP_HOST'] === '127.0.0.1');
        $finalProductImg = $productImage;
        if ($isLocal && strpos($productImage, 'http') === false) {
            $finalProductImg = "https://images.unsplash.com/photo-1523275335684-37898b6baf30?q=80&w=600";
        }

        $payload = [
            "data" => [
                ["data" => $userImage, "name" => "human.jpg"],
                ["data" => $finalProductImg, "name" => "garment.jpg"],
                "A stylish outfit",
                true,
                true,
                30,
                2.5
            ],
            "fn_index" => 1
        ];

        $headers = ["Content-Type: application/json"];
        if (!empty($hfToken)) {
            $headers[] = "Authorization: Bearer $hfToken";
        }

        $ch = curl_init($hfSpaceUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($httpCode === 200 && (isset($result['data'][0]['url']) || isset($result['data'][0]['name']))) {
            $url = $result['data'][0]['url'] ?? null;
            if (!$url && isset($result['data'][0]['name'])) {
                $baseUrl = str_replace('/api/predict', '', $hfSpaceUrl);
                $url = $baseUrl . "/file=" . $result['data'][0]['name'];
            }
            echo json_encode(['url' => $url, 'status' => 'success', 'provider' => 'HuggingFace']);
        } else {
            $errorMsg = "HuggingFace Indisponible (HTTP $httpCode)";
            if ($httpCode == 503)
                $errorMsg = "L'IA est surchargée, réessayez dans 1 min.";
            if ($httpCode == 401)
                $errorMsg = "Token HuggingFace invalide.";
            throw new Exception($errorMsg);
        }

    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'error' => $e->getMessage(),
            'url' => 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&q=80&w=600', // Still show something 
            'note' => 'Simulation active suite à : ' . $e->getMessage()
        ]);
    }
}
