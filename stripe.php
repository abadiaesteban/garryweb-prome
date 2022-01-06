<?php

/**
 * Require all the main functions
 */
$page = 'stripe';
require_once('inc/functions.php');

/**
 * Tell it where to write error logs to
 */
ini_set('log_errors', true);
ini_set('error_log', dirname(__FILE__).'/stripe_errors.log');

\Stripe\Stripe::setApiKey(getSetting('stripe_apiKey', 'value'));
$endpoint_secret = getSetting('stripe_webhookSecret', 'value');

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$event = null;

try {
    $event = \Stripe\Webhook::constructEvent(
        $payload,
        $sig_header,
        $endpoint_secret
    );
} catch (\UnexpectedValueException $e) {
    // Invalid payload
    http_response_code(400);
    exit();
} catch (\Stripe\Error\SignatureVerification $e) {
    // Invalid signature
    http_response_code(400);
    exit();
}

// Handle the event
switch ($event->type) {
    case 'checkout.session.completed':
        $session = $event->data->object;

        list($type, $price, $itemID, $uid, $buyer_id) = explode(',', $session->client_reference_id);

        // ternary due to backwards compatibility with earlier Stripe API versions
        $currency = isset($session->display_items[0]->currency)
            ? strtoupper($session->display_items[0]->currency)
            : strtoupper($session->currency);

        $txn_id = $session->payment_intent;

        $price = $price / 100;

        if ($type == 'pkg') {
            $name = $db->getOne("SELECT name FROM players WHERE uid = ?", $uid);

            $db->execute(
                "INSERT INTO transactions SET name = ?, buyer = ?, email = ?, uid = ?, buyer_uid = ?, package = ?, currency = ?, price = ?, txn_id = ?, gateway = 'stripe'",
                array($name, $name, '', $uid, $buyer_id, $itemID, $currency, $price, $txn_id)
            );

            $trans = $db->getOne("SELECT id FROM transactions WHERE txn_id = ?", $txn_id);

            $p_array = array(
                "id" => $itemID,
                "trans_id" => $trans,
                "uid" => $uid,
                "buyer_id" => $buyer_id,
                "type" => 1
            );
            addAction($p_array);
        }

        if ($type == 'credits') {
            $name = $db->getOne("SELECT name FROM players WHERE uid = ?", $uid);
            $credits = $db->getOne("SELECT amount FROM credit_packages WHERE id = ?", $itemID);

            $db->execute("INSERT INTO transactions SET name = ?, buyer = ?, email = ?, uid = ?, buyer_uid = ?, credit_package = ?, currency = ?, price = ?, credits = ?, txn_id = ?, gateway = 'stripe'", array(
                $name, $name, '', $uid, $buyer_id, $itemID, $currency, $price, $credits, $txn_id
            ));

            $credits_old = $db->getOne("SELECT credits FROM players WHERE uid = ?", $uid);
            $credits_new = $credits_old + $credits;
            credits::set($uid, $credits_new);

            $p_array = array(
                "id" => 0,
                "trans_id" => 0,
                "uid" => $uid,
                "buyer_id" => $buyer_id,
                "amount" => $credits,
                "type" => 2
            );
            addAction($p_array);
        }

        if ($type == 'raffle') {
            $name = $db->getOne("SELECT name FROM players WHERE uid = ?", $uid);
            $credits = $db->getOne("SELECT credits FROM raffles WHERE id = ?", $itemID);

            $count = $db->getOne("SELECT count(*) AS value FROM raffle_tickets WHERE raffle_id = ?, uid = ?", array($itemID, $uid))['value'];
            $max_per_person = $db->getOne("SELECT max_per_person FROM raffles WHERE id = ?", [$itemID])['max_per_person'];

            if ($count != $max_per_person) {
                $db->execute("INSERT INTO transactions SET name = ?, buyer = ?, email = ?, uid = ?, buyer_uid = ?, raffle_package = ?, currency = ?, price = ?, credits = ?, txn_id = ?, gateway = 'stripe'", array(
                    $name, $name, '', $uid, $buyer_id, $itemID, $currency, $price, $credits, $txn_id
                ));

                $db->execute("INSERT INTO raffle_tickets SET raffle_id = ?, uid = ?", array($itemID, $uid));
            }
        }

        cache::clear('purchase', $uid);

        break;
    default:
        // Unexpected event type
        http_response_code(400);
        exit();
}

http_response_code(200);
