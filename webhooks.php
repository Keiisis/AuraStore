<?php
/**
 * AuraStore - Global Webhook Handler
 * Automates subscriptions and triggers invoices
 */
require_once 'includes/functions.php';
require_once 'includes/invoice_system.php';
require_once 'config/database.php';

$db = getDB();
$payload = file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

// 1. STRIPE WEBHOOK (Automation for Seller Subscriptions)
if ($sig_header) {
    // This is likely a Stripe Webhook
    $event = json_decode($payload, true);

    if ($event['type'] === 'checkout.session.completed') {
        $session = $event['data']['object'];
        $userId = $session['client_reference_id'];
        $planId = $session['metadata']['plan_id'];

        // Upgrade user plan
        $db->prepare("UPDATE users SET plan_id = ?, subscription_status = 'active', plan_expires_at = (CURRENT_DATE + INTERVAL '1 month') WHERE id = ?")
            ->execute([$planId, $userId]);

        http_response_code(200);
        exit();
    }
}

// 2. KKIAPAY WEBHOOK (Customer purchases in shops)
$kkiaData = json_decode($payload, true);
if (isset($kkiaData['transactionId']) && isset($kkiaData['amount'])) {
    // Verify Kkiapay signature here for production...

    // Logic for order confirmation
    // Trigger the AI Invoice
    // $orderData = [...];
    // sendPremiumInvoice($customerEmail, $orderData);

    http_response_code(200);
    exit();
}

http_response_code(400);
echo "Invalid Webhook Source";
