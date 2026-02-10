<?php
/**
 * AuraStore - Intelligent Invoice & Email System
 * Powered by Groq AI
 */

require_once __DIR__ . '/functions.php';

/**
 * Generates an AI-powered personalized message for the invoice
 */
function generateAIInvoiceText($productName, $price, $currency, $customerName = "Client")
{
    $db = getDB();
    $apiKey = $db->query("SELECT setting_value FROM platform_settings WHERE setting_key = 'groq_api_key'")->fetchColumn();

    if (!$apiKey)
        return "Merci pour votre achat chez AuraStore ! Profitez bien de votre nouveau produit.";

    $prompt = "Tu es Aura Intelligence. Un client vient d'acheter '{$productName}' pour {$price} {$currency}.
               Rédige un court message (3 lignes max) ultra-élégant, chaleureux et premium pour le remercier. 
               Si c'est un vêtement, donne un conseil de style rapide. Si c'est autre chose, félicite-le pour son goût.
               Langue: Français. Ton: Luxe, Exclusif.";

    try {
        $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]);

        $payload = [
            'model' => 'llama3-70b-8192',
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'temperature' => 0.8
        ];

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        $response = curl_exec($ch);
        $data = json_decode($response, true);
        return $data['choices'][0]['message']['content'] ?? "Félicitations pour votre acquisition !";
    } catch (Exception $e) {
        return "Merci pour votre confiance en AuraStore.";
    }
}

/**
 * Sends a premium branded HTML invoice email
 */
function sendPremiumInvoice($toEmail, $orderData)
{
    $product = $orderData['product_name'] ?? 'Produit';
    $price = $orderData['price'] ?? 0;
    $currency = $orderData['currency'] ?? 'XAF';
    $brandColor = $orderData['brand_color'] ?? '#FE7501';
    $storeName = $orderData['store_name'] ?? 'AuraStore';

    $aiMessage = generateAIInvoiceText($product, $price, $currency, $storeName);

    $subject = "Votre reçu AURA - {$storeName}";
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: AuraStore <noreply@aurastore.com>" . "\r\n";

    // DYNAMIC LOGO LOGIC
    $logoHtml = $orderData['logo_url']
        ? "<img src='{$orderData['logo_url']}' style='height:45px; border-radius:12px; margin-bottom:15px; border:1px solid rgba(255,255,255,0.1);'>"
        : "<h1 style='color:white; margin:0; font-size:1.5rem; letter-spacing:2px;'>" . strtoupper($storeName) . "</h1>";

    // ULTRA MODERN EMAIL TEMPLATE
    $html = "
    <!DOCTYPE html>
    <html lang='fr'>
    <head>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Sora:wght@400;800&display=swap');
            body { background-color: #0c0c0e; color: #ffffff; font-family: 'Sora', sans-serif; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 40px auto; background: #141417; border-radius: 40px; overflow: hidden; border: 1px solid rgba(255,255,255,0.05); }
            .header { padding: 50px 40px; background: linear-gradient(135deg, {$brandColor}, #000000); text-align: center; }
            .content { padding: 40px; }
            .title { font-size: 24px; font-weight: 800; margin-bottom: 25px; text-transform: uppercase; letter-spacing: 2px; text-align: center; }
            .price-box { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 25px; padding: 35px; text-align: center; margin: 30px 0; }
            .price { font-size: 44px; font-weight: 800; color: {$brandColor}; }
            .ai-note { background: linear-gradient(90deg, rgba(255,117,1,0.1), transparent); border-left: 4px solid #FE7501; padding: 25px; margin: 35px 0; font-style: italic; color: #a0a0a5; font-size: 0.95rem; border-radius: 0 20px 20px 0; }
            .footer { padding: 35px; text-align: center; opacity: 0.5; font-size: 11px; border-top: 1px solid rgba(255,255,255,0.05); letter-spacing: 1px; }
            @media (max-width: 600px) { .container { margin: 0; border-radius: 0; } .header { padding: 40px 20px; } .content { padding: 30px 20px; } .price { font-size: 32px; } }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                {$logoHtml}
                <div style='font-size:0.7rem; color:rgba(255,255,255,0.6); letter-spacing:3px; margin-top:5px;'>CONFIRMATION DE PAIEMENT</div>
            </div>
            <div class='content'>
                <div class='title'>Votre commande est prête</div>
                <p style='text-align:center; opacity:0.8;'>Votre achat chez <strong>{$storeName}</strong> a été validé avec succès par Aura Intelligence.</p>
                
                <div class='price-box'>
                    <div style='font-size: 0.8rem; opacity: 0.5; margin-bottom: 12px; letter-spacing:1px;'>TOTAL PAYÉ</div>
                    <div class='price'>" . number_format($price) . " {$currency}</div>
                    <div style='margin-top:15px; font-weight:600; font-size:1.1rem;'>{$product}</div>
                </div>

                <div class='ai-note'>
                    <strong style='color:white;'>Le mot d'Aura Intelligence :</strong><br>
                    <span style='opacity:0.9;'>\"{$aiMessage}\"</span>
                </div>

                <div style='text-align:center; font-size: 0.75rem; opacity: 0.5; margin-top:40px;'>Transaction ID : " . strtoupper(uniqid('AURA-')) . "</div>
            </div>
            <div class='footer'>
                © " . date('Y') . " {$storeName} — PROPULSÉ PAR AURASTORE & GROQ AI
            </div>
        </div>
    </body>
    </html>
    ";

    // For production, you could use PHPMailer or SendGrid.
    // For now, redirecting to logs/mail simulation if env is local
    if (getenv('APP_ENV') === 'local') {
        file_put_contents(__DIR__ . '/../logs/last_mail.html', $html);
        return true;
    }

    return mail($toEmail, $subject, $html, $headers);
}
