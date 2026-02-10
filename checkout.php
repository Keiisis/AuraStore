<?php
/**
 * AuraStore - Smart Checkout Hub
 * Routes users to selected payment gateways or WhatsApp
 */
require_once 'includes/functions.php';
require_once 'config/database.php';

$db = getDB();

$productId = intval($_GET['p'] ?? 0);
$method = $_GET['m'] ?? 'whatsapp';
$storeSlug = $_GET['s'] ?? '';

if (!$productId) {
    die("Produit invalide.");
}

$product = $db->prepare("SELECT p.*, s.whatsapp_number, s.currency FROM products p JOIN stores s ON p.store_id = s.id WHERE p.id = ?");
$product->execute([$productId]);
$p = $product->fetch();

if (!$p)
    die("Produit non trouvé.");

$price = number_format($p['price'], 0, '.', ' ');
$sym = $p['currency'] === 'XAF' ? 'FCFA' : ($p['currency'] === 'EUR' ? '€' : '$');

switch ($method) {
    case 'stripe':
        // Fetch Platform Stripe Settings for Subscriptions
        $stripeKey = $db->query("SELECT setting_value FROM platform_settings WHERE setting_key = 'stripe_secret'")->fetchColumn();

        // Initiation Logic (Simulated for this template)
        // Header redirection to dynamic stripe session...
        echo "<div style='background:#0f0f12; color:white; height:100vh; display:flex; align-items:center; justify-content:center; font-family:sans-serif;'>
                <div style='text-align:center;'>
                    <h2>Sécurisation de la commande...</h2>
                    <p>Redirection vers Stripe Checkout API</p>
                    <div style='border:2px solid #6772e5; padding:10px; border-radius:8px;'>Clé Détectée: " . substr($stripeKey, 0, 8) . "...</div>
                </div>
              </div>";
        break;

    case 'kkiapay':
        // Kkiapay Redirection logic
        $kkiapayKey = $db->query("SELECT setting_value FROM platform_settings WHERE setting_key = 'kkiapay_public'")->fetchColumn();
        echo "<script src='https://cdn.kkiapay.me/k.js'></script>
              <script>
                // This would trigger the Kkiapay widget
                console.log('Kkiapay widget load for {$p['name']}');
              </script>";
        die("Initialisation Kkiapay... (Redirection)");
        break;

    case 'test_invoice':
        // Special method to show the AI invoice power
        require_once 'includes/invoice_system.php';
        $testOrder = [
            'product_name' => $p['name'],
            'price' => $p['price'],
            'currency' => $p['currency'],
            'brand_color' => $db->query("SELECT setting_value FROM landing_settings WHERE setting_key = 'primary_color'")->fetchColumn() ?: '#FE7501',
            'store_name' => $p['store_name'] ?? 'Ma Boutique Luxe'
        ];
        sendPremiumInvoice('test@customer.com', $testOrder);
        die("<div style='background:#0f0f12; color:#00FF94; padding:50px; text-align:center; height:100vh;'>
                <h1>✅ Reçu Intelligent Envoyé (Test)!</h1>
                <p>Consultez votre boîte mail ou le fichier logs/last_mail.html</p>
                <a href='store.php?s=" . $storeSlug . "' style='color:white;'>Retour boutique</a>
             </div>");
        break;

    case 'whatsapp':
    default:
        $text = "Bonjour ! Je souhaite commander ce produit :\n\n";
        $text .= "📦 *{$p['name']}*\n";
        $text .= "💰 *Prix :* {$price} {$sym}\n";
        $text .= "🔗 *Lien :* " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]\n\n";
        $text .= "Merci !";

        $url = "https://wa.me/{$p['whatsapp_number']}?text=" . urlencode($text);
        header("Location: $url");
        exit();
}
