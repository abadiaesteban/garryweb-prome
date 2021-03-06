<?php
session_start();

$page = 'inc';
include('functions.php');

/**
 * Deny any non authorised requests
 */
if (!prometheus::loggedin()) {
    die('To access this you need a valid session');
}

if (isset($_SESSION['lastPurchase'])) {
    $lastPurchase = $_SESSION['lastPurchase'];

    if (time() <= $lastPurchase + 10) {
        util::redirect('../profile.php?cm');
        exit;
    }
}

$_SESSION['lastPurchase'] = time();

if (!csrf_check()) {
    return util::error("Invalid CSRF token!");
}

$uids = explode(',', $_POST['uid']);

$receiver_uid = $uids[0];
$buyer_uid = $uids[0];
if (count($uids) > 1) {
    $buyer_uid = $uids[1];
}

$coupon = false;
if (isset($_POST['coupon'])) {
    $coupon = $_POST['coupon'];
}

$buyer_name = $db->getOne("SELECT name FROM players WHERE uid = ?", $buyer_uid);

$receiver_name = $db->getOne("SELECT name FROM players WHERE uid = ?", $receiver_uid);
if (!$receiver_name) {
    $receiver_name = 'Not registered';
}

/**
 * Die if no type has been set
 */
if (!isset($_POST['type'])) {
    die('No type has been set');
} else {
    $type = $_POST['type'];
}

/**
 * Die if no itemID has been set
 */
if (!isset($_POST['itemID'])) {
    die('No itemID has been set');
} else {
    $itemID = $_POST['itemID'];
}

$verify = new verification('credits', $receiver_uid, $itemID, $coupon);
$verify->setBuyerId($buyer_uid);

/**
 * If the type is package, perform package code
 * @var [type]
 */
if ($type == 'package') {
    $custom_price = (bool)$db->getOne("SELECT custom_price FROM packages WHERE id = ?", $itemID);

    /**
     * If the credits gateway is not enabled, check if the package is a free one
     */
    if (!gateways::enabled('credits')) {
        $price = $verify->getPrice('package');

        if ((float)$price === 0.00) {
            if (packages::ownsFree($itemID)) {
                die('You already own this free package, no getting it twice!');
            }

            $p_array = array(
                "id" => $itemID,
                "trans_id" => 0,
                "uid" => $receiver_uid,
                "buyer_id" => $buyer_uid,
                "type" => 1,
            );
            addAction($p_array);

            $db->execute("INSERT INTO transactions SET name = ?, buyer = ?, email = ?, uid = ?, buyer_uid = ?, package = ?, price = ?, gateway = 'credits'", [
                $receiver_name, $buyer_name, 'Free package', $receiver_uid, $buyer_uid, $itemID, 0,
            ]);

            util::redirect('../profile.php?cm');
        }
    } elseif (getSetting('credits_only', 'value2') == 0 && !$custom_price) {
        /**
         * If credits gateway is enabled and pay only with credits is disabled, check if the package is free with money anyways
         */

        $price = $verify->getPrice('package');

        if ((float)$price === 0.00) {
            if (packages::ownsFree($itemID)) {
                die('You already own this free package, no getting it twice!');
            }

            $p_array = array(
                "id" => $itemID,
                "trans_id" => 0,
                "uid" => $receiver_uid,
                "buyer_id" => $buyer_uid,
                "type" => 1,
            );
            addAction($p_array);

            $db->execute("INSERT INTO transactions SET name = ?, buyer = ?, email = ?, uid = ?, buyer_uid = ?, package = ?, price = ?, gateway = 'credits'", [
                $receiver_name, $buyer_name, 'Free package', $receiver_uid, $buyer_uid, $itemID, 0,
            ]);

            util::redirect('../profile.php?cm');
        }
    }

    /**
     * If the credits gateway is enabled, check if the package costs 0 credits
     */
    if (gateways::enabled('credits')) {
        $price = $verify->getPrice('package', 'credits');

        if ($custom_price) {
            $price = $_POST['price'];
        }

        /**
         * If the credits price is 0, else do something else
         * @var redirect to profile page
         */
        if ((float)$price === 0.00) {
            if (packages::ownsFree($itemID)) {
                die('You already own this free package, no getting it twice!');
            }

            $p_array = array(
                "id" => $itemID,
                "trans_id" => 0,
                "uid" => $receiver_uid,
                "buyer_id" => $buyer_uid,
                "type" => 1,
            );
            addAction($p_array);

            $db->execute("INSERT INTO transactions SET name = ?, buyer = ?, email = ?, uid = ?, buyer_uid = ?, package = ?, credits = ?, gateway = 'credits'", [
                $receiver_name, $buyer_name, 'Free package with credits', $receiver_uid, $buyer_uid, $itemID, 0,
            ]);

            util::redirect('../profile.php?cm');
        } else {
            if (credits::hasEnough($buyer_uid, $itemID, 'package', $coupon, $receiver_uid, $price) && (!$custom_price || packages::isLegalMinPrice($itemID, $price))) {
                $db->execute("INSERT INTO transactions SET name = ?, buyer = ?, email = ?, uid = ?, buyer_uid = ?, package = ?, credits = ?, gateway = 'credits'", [
                    $receiver_name, $buyer_name, 'Purchased with credits', $receiver_uid, $buyer_uid, $itemID, $price,
                ]);

                credits::withdraw($buyer_uid, $itemID, 'package', $coupon, $receiver_uid, $price);
                coupon::useCoupon($coupon);

                $p_array = array(
                    "id" => $itemID,
                    "trans_id" => 0,
                    "uid" => $receiver_uid,
                    "buyer_id" => $buyer_uid,
                    "type" => 1,
                );
                addAction($p_array);

                util::redirect('../profile.php?cm');
            } else {
                util::redirect('../profile.php?fail');
            }
        }
    }
}

/**
 * If the type is raffle, give the user the ticket if they can afford it
 * @var redirect to profile page
 */
if ($type === 'raffle') {
    if (gateways::enabled('credits')) {
        if (credits::hasEnough($buyer_uid, $itemID, 'raffle', false, $receiver_uid)) {
            $count = $db->getOne("SELECT count(*) FROM raffle_tickets WHERE raffle_id = ? AND uid = ?", [
                $itemID, $buyer_uid,
            ]);

            $max_per_person = $db->getOne("SELECT max_per_person FROM raffles WHERE id = ?", $itemID);

            if ((int)$count < (int)$max_per_person) {
                credits::withdraw($buyer_uid, $itemID, 'raffle', false, $receiver_uid);
                $price = $verify->getPrice('raffle', 'credits');

                $db->execute("INSERT INTO transactions SET name = ?, buyer = ?, email = ?, uid = ?, buyer_uid = ?, raffle_package = ?, credits = ?", [
                    $receiver_name, $buyer_name, 'Purchased with credits', $receiver_uid, $buyer_uid, $itemID, $price,
                ]);

                $db->execute("INSERT INTO raffle_tickets SET raffle_id = ?, uid = ?", [
                    $itemID, $buyer_uid,
                ]);

                util::redirect('../profile.php?cm');
            }
        }

        util::redirect('../profile.php?fail');
    } else {
        if (raffle::getEdit($itemID, 'price') == 0) {
            $count = $db->getOne("SELECT count(*) AS value FROM raffle_tickets WHERE raffle_id = ? AND uid = ?", [
                $itemID, $buyer_uid,
            ]);

            $max_per_person = $db->getOne("SELECT max_per_person FROM raffles WHERE id = ?", $itemID);

            if ($count != $max_per_person) {
                $db->execute("INSERT INTO transactions SET name = ?, buyer = ?, email = ?, uid = ?, buyer_uid = ?, raffle_package = ?, credits = ?", [
                    $receiver_name, $buyer_name, 'Purchased for free', $receiver_uid, $buyer_uid, $itemID, $price,
                ]);

                $db->execute("INSERT INTO raffle_tickets SET raffle_id = ?, uid = ?", [
                    $itemID, $buyer_uid,
                ]);
            }

            util::redirect('../profile.php?cm');
        } else {
            util::redirect('../profile.php?fail');
        }
    }
}

cache::clear('purchase', $receiver_uid);
