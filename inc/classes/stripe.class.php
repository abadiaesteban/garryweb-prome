<?php

class stripe
{
    public static function checkout($type, $pid, $uid, $q_price = null)
    {
        global $db;

        if ($uid == 0) {
            die('Attempted Steam64ID fraud');
        }

        $api_key = getSetting('stripe_apiKey', 'value');

        $bad = false;

        \Stripe\Stripe::setApiKey($api_key);

        $coupon = false;
        if (isset($_GET['coupon'])) {
            $coupon = $_GET['coupon'];
            coupon::useCoupon($coupon);
        }

        $verify = new verification('stripe', $uid, $pid, $coupon);

        if ($type == 'pkg') {
            $res = $db->getAll("SELECT * FROM packages WHERE id = ?", $pid);

            foreach ($res as $row) {
                $title = $row['title'];
                $custom_price = $row['custom_price'];
                $custom_price_min = $row['custom_price_min'];
            }

            if ($custom_price == 1) {
                $price = $q_price;
            } else {
                $price = $verify->getPrice('package');
            }

            if ($custom_price == 1 && $custom_price_min > $q_price) {
                $bad = true;
            }
        }

        if ($type == 'credits') {
            $res = $db->getAll("SELECT * FROM credit_packages WHERE id = ?", $pid);

            foreach ($res as $row) {
                $title = $row['title'];
                $price = $row['price'];
            }
        }

        if ($type == 'raffle') {
            $res = $db->getAll("SELECT * FROM raffles WHERE id = ?", $pid);

            foreach ($res as $row) {
                $title = $row['title'];
                $price = $row['price'];
            }
        }

        $curID = getSetting('dashboard_main_cc', 'value2');
        $currency = $db->getOne("SELECT cc FROM currencies WHERE id = ?", $curID);

        $charge_price = $price * 100;

        if (!$bad) {
            $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";

            $actual_link = explode('store.php', $actual_link);
            $success_url = $actual_link[0] . 'profile.php?cm';
            $cancel_url  = $actual_link[0] . 'store.php';

            $buyer_id = $_SESSION['uid'];
            $metadata = array("type" => $type, "price" => $charge_price, "itemID" => $pid, "uid" => $uid, "buyer_id" => $buyer_id);

            try {
                $session = \Stripe\Checkout\Session::create([
                    "success_url" => $success_url,
                    "cancel_url" => $cancel_url,
                    "payment_method_types" => ["card"],
                    "client_reference_id" => implode(',', $metadata),
                    "line_items" => [
                        [
                            "name" => $title,
                            "amount" => $charge_price,
                            "currency" => $currency,
                            "quantity" => 1,
                        ],
                    ]
                ]);

                return json_encode([
                    'id' => $session['id']
                ]);
            } catch (Exception $e) {
                return $e;
            }
        }
    }
}
