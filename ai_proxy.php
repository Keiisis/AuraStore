<?php
/**
 * Aura AI Proxy - Bridge to Zhipu AI (GLM-4)
 */
require_once 'includes/security.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

initSecureSession();
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['reply' => "Vous devez être connecté pour utiliser l'IA."]);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$msg = $input['message'] ?? '';
$personaType = $input['persona'] ?? 'default';

if (!$msg) {
    echo json_encode(['reply' => "Je n'ai pas compris votre question."]);
    exit();
}

$db = getDB();
$apiKey = $db->query("SELECT setting_value FROM platform_settings WHERE setting_key = 'zhipu_api_key'")->fetchColumn();

if (!$apiKey) {
    $apiKey = getenv('ZHIPU_API_KEY'); // Fallback to Env
}

if (!$apiKey) {
    echo json_encode(['reply' => "⚠️ Erreur: Clé API Zhipu (GLM) non configurée. Renseignez 'zhipu_api_key' dans platform_settings."]);
    exit();
}

// Stats context for the assistant
$context = "Tu es Aura Intelligence, l'IA d'AuraStore.";

if ($personaType === 'seller') {
    $user = getCurrentUser();
    $stats = getSellerStats($user['id']);
    $context = "Tu es 'The Wolf of Aura', un expert en neuromarketing et psychologie de la vente.
                Ton client est un vendeur sur la plateforme AuraStore.
                Stats du client : {$stats['views']} vues, {$stats['products']} produits, {$stats['tryons']} essayages virtuels.
                Ton but : Être direct, agressif mais inspirant pour booster ses ventes. Utilise des termes comme 'valeur perçue', 'scarcity', 'friction'.";
} elseif ($personaType === 'admin') {
    $totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $context = "Tu es 'Cyber Oracle', l'assistant stratégique de l'administrateur d'AuraStore.
                Tu as accès à toute la plateforme. Total utilisateurs: {$totalUsers}.
                Ton but : Donner des conseils de scalabilité SaaS, de rétention et de monétisation.";
}

try {
    // Zhipu AI (GLM-4) Endpoint - OpenAI Compatible
    $ch = curl_init('https://open.bigmodel.cn/api/paas/v4/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    // Zhipu requires simple Bearer token for V4 API
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);

    $payload = [
        'model' => 'glm-4', // Using GLM-4 as requested (closest to "4.7" if it implies latest)
        'messages' => [
            ['role' => 'system', 'content' => $context],
            ['role' => 'user', 'content' => $msg]
        ],
        'temperature' => 0.7,
        'top_p' => 0.7,
        'requestId' => uniqid('aura_')
    ];

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        throw new Exception(curl_error($ch));
    }

    $data = json_decode($response, true);

    $reply = $data['choices'][0]['message']['content'] ?? "Erreur API GLM: " . ($data['error']['message'] ?? "Réponse vide");
    echo json_encode(['reply' => $reply]);

} catch (Exception $e) {
    error_log("Zhipu AI Error: " . $e->getMessage());
    echo json_encode(['reply' => "Erreur technique lors de la communication avec le cerveau GLM."]);
}
