<?php

class verification
{
    private $itemID;
    private $gateway;
    private $uid;
    private $buyer_id;

    /**
     * @var bool
     */
    private $coupon;

    /**
     * verification constructor.
     * @param $gateway
     * @param $uid
     * @param $itemID
     * @param bool $coupon
     */
    public function __construct($gateway, $uid, $itemID, $coupon = false)
    {
        $this->gateway = $gateway;
        $this->uid = $uid;
        $this->itemID = $itemID;
        $this->coupon = $coupon;
    }

    /**
     * @param $buyer_id
     */
    public function setBuyerId($buyer_id)
    {
        $this->buyer_id = $buyer_id;
    }

    /**
     * verifyPackage
     * @param int or null $price
     * @return array containing error(bool) and msg(string)
     */
    public function verifyPackage($price = null)
    {
        $for = $this->uid;
        $itemID = $this->itemID;

        $error = false;
        $msg = '';

        if (packages::notCompatible($itemID, $for)) {
            $error = true;

            if ($for == null) {
                $msg = lang('buy_not_compatible');
            } else {
                $msg = lang('buy_they_not_compatible');
            }
        }

        if (packages::alreadyOwn($itemID, $for) && !packages::rebuyable($itemID)) {
            $error = true;
            $msg = lang('buy_already_own');

            if ($for !== null) {
                $msg = lang('buy_they_already_own');
            }
        }

        if (packages::ownedOnce($itemID, $for) && getEditPackage($itemID, 'once') == 1) {
            $error = true;
            $msg = 'You can not buy this package more than once';
        }

        if (packages::disabled($itemID)) {
            $error = true;
            $msg = 'This package is disabled!';
        }

        if ($price !== null && getEditPackage($itemID, 'custom_price') == 1) {
            if ($price <= 0) {
                $error = true;
                $msg = 'This package can not have a price of 0';
            }

            if (!packages::isLegalMinPrice($itemID, $price)) {
                $error = true;
                $msg = 'Attempted minimum price bypass!';
            }
        }

        $customJob = isset($_GET['pid']) && actions::get($_GET['pid'], 'customjob', '');
        if ($customJob) {
            $pre = prepurchase::hasPre($_SESSION['uid'], 'customjob');
            if ($pre == false) {
                $error = true;

                $msg = 'You have not created a custom job for this package!';
            }
        }

        $hide = packages::hide($itemID, $for);

        if ($hide['hide']) {
            $error = true;

            if ($for == null) {
                $msg = lang('package_cantbuy', null, [
                    $hide['packages']
                ]);
            } else {
                $msg = lang('package_they_cantbuy', null, [
                    $hide['packages']
                ]);
            }
        }

        return [
            'error' => $error,
            'msg' => $msg
        ];
    }

    /**
     * @param  $POST array
     * @param  $e array; price, custom_price, cur
     * @return boolean
     */
    public function verify($POST, $e)
    {
        global $db;

        $gateway = $this->gateway;
        $itemID = $this->itemID;

        if ($gateway === 'paypal') {
            /**
             * Package code
             */
            $price = $e['price'];
            $custom_price = $e['custom_price'];
            $cur = $e['cur'];
            $alt_pp = $e['alt_pp'];

            $min_price = 0;
            if ($custom_price == 1) {
                $min_price = $db->getOne("SELECT custom_price_min FROM packages WHERE id = ?", $itemID);
            }

            if ((strtolower($POST['receiver_email']) || strtolower($POST['business'])) == (strtolower(getSetting('paypal_email', 'value')) || strtolower($alt_pp))) { // Similar in all the below cases, so included globally instead
                if ($custom_price == 0 && $POST['payment_status'] == "Completed" && $POST['mc_currency'] == $cur && ($POST['mc_gross'] >= $price || $POST['payment_gross'] >= $price) // Normal payment, no custom and no recurring
                    || $custom_price == 1 && $POST['payment_status'] == "Completed" && $POST['mc_currency'] == $cur && $POST['mc_gross'] >= $min_price // Normal w/custom price
                    || $custom_price == 1 && $POST['txn_type'] == 'recurring_payment' && $POST['mc_currency'] == $cur && $POST['mc_amount3'] >= $min_price // Recurring payment w/custom price
                    || $custom_price == 0 && $POST['txn_type'] == 'recurring_payment' && $POST['mc_amount3'] == $price // Normal recurring payment
                ) {
                    $db->execute("INSERT INTO requests SET error = 0, msg = 'PayPal verification successful - POST', debug = ?", json_encode($POST));
                    $db->execute("INSERT INTO requests SET error = 0, msg = 'PayPal verification successful - EXTRA', debug = ?", "Custom Price: $custom_price | Currency: $cur | PKG Price: $price");

                    return true;
                } else {
                    $db->execute("INSERT INTO requests SET error = 1, msg = 'PayPal verification failed - POST', debug = ?", json_encode($POST));
                    $db->execute("INSERT INTO requests SET error = 1, msg = 'PayPal verification failed - EXTRA', debug = ?", "Custom Price: $custom_price | Currency: $cur | PKG Price: $price");

                    return false;
                }
            }
        }

        return false;
    }

    /**
     * @param  $POST array
     * @return boolean
     */
    public function isChargeback($POST)
    {
        $gateway = $this->gateway;

        if ($gateway == 'paypal') {
            return ($POST['payment_status'] == 'Reversed' && ($POST['reason_code'] == 'chargeback'
                    || $POST['reason_code'] == 'buyer-complaint'
                    || $POST['reason_code'] == 'refund'
                    || $POST['reason_code'] == 'unauthorized_claim'
                    || $POST['reason_code'] == 'unauthorized_spoof')
            );
        }

        return false;
    }

    /**
     * @param $type
     * @param null $moneyType
     * @param bool $cjob
     * @return float|int|mixed|string|null
     * @throws Exception
     */
    public function getPrice($type, $moneyType = null, $cjob = false)
    {
        global $db;

        $itemID = $this->itemID;
        $user_id = $this->uid;
        $coupon = $this->coupon;
        $price = false;

        if ($type == 'package') {
            if ($moneyType == null) {
                $price = $db->getOne("SELECT price FROM packages WHERE id = ?", $itemID);

                if ($price != 0) {
                    $upgrade = packages::upgradeable($itemID, null, $user_id);

                    if ($upgrade && $user_id !== null) {
                        $pkg = packages::upgradeable($itemID, 'list', $user_id);
                        $price = packages::upgradeInfo($itemID, $pkg, 'price', $price);
                    }

                    $sale_ar = getSetting('sale_packages', 'value');
                    $sale_ar = json_decode($sale_ar, true);
                    $perc = getSetting('sale_percentage', 'value2');

                    try {
                        $sale_end = new datetime(getSetting('sale_enddate', 'value'));
                        $valid_date = true;
                    } catch (Exception $e) {
                        $sale_end = null;
                        $valid_date = false;
                    }

                    if (!is_array($sale_ar)) {
                        $sale_ar = array();
                    }

                    if (in_array($itemID, $sale_ar, true) && $valid_date && $sale_end > new datetime()) {
                        $orgprice = $price;
                        $price = $perc / 100 * $orgprice;
                        $price = $orgprice - $price;
                        $price = number_format($price, 2, '.', '');
                    }
                }

                if (!$cjob) {
                    $customjob = actions::get($itemID, 'customjob', '', $user_id) ? true : false;

                    if ($customjob) {
                        $buyer_id = isset($_SESSION['uid']) ? $_SESSION['uid'] : $this->buyer_id;
                        $pre = prepurchase::hasPre($buyer_id, 'customjob');

                        if ($pre !== false) {
                            $json = prepurchase::getJson($pre);
                            $array = json_decode($json, true);

                            $price = $array['fullTotalPrice'];
                        }
                    }
                }

                if ($coupon != false && getSetting('enable_coupons', 'value2') == 1) {
                    if (coupon::isValid($coupon, $itemID)) {
                        $coupon_id = coupon::getIdByCode($coupon);

                        $coupon_percent = coupon::getValue($coupon_id, 'percent');

                        if ($coupon_percent == 100) {
                            $price = 0;
                        } else {
                            $orgprice = $price;
                            $price = $coupon_percent / 100 * $orgprice;
                            $price = $orgprice - $price;
                            $price = number_format($price, 2, '.', '');
                        }
                    }
                }

                $price = number_format($price, 2);
            }

            if ($moneyType == 'credits') {
                $price = $db->getOne("SELECT credits FROM packages WHERE id = ?", $itemID);

                if (!gateways::enabled('credits')) {
                    die('This system does not have credits enabled');
                }

                if ($price != 0) {
                    $upgrade = packages::upgradeable($itemID, null, $user_id);
                    if ($upgrade) {
                        $pkg = packages::upgradeable($itemID, 'list', $user_id);
                        $price = packages::upgradeInfo($itemID, $pkg, 'credits', $price);
                    }

                    $sale_ar = getSetting('sale_packages', 'value');
                    $sale_ar = json_decode($sale_ar, true);
                    $perc = getSetting('sale_percentage', 'value2');

                    if (!is_array($sale_ar)) {
                        $sale_ar = array();
                    }

                    if (in_array($itemID, $sale_ar, true) && new datetime(getSetting('sale_enddate', 'value')) > new datetime()) {
                        $orgprice = $price;
                        $price = $perc / 100 * $orgprice;
                        $price = $orgprice - $price;
                    }
                }

                if (!$cjob) {
                    $customJob = actions::get($itemID, 'customjob', '') ? true : false;

                    if ($customJob) {
                        $pre = prepurchase::hasPre($_SESSION['uid'], 'customjob');
                        if ($pre !== false) {
                            $json = prepurchase::getJson($pre);
                            $array = json_decode($json, true);

                            $price = $array['fullTotalCredits'];
                        }
                    }
                }

                if ($coupon != false && getSetting('enable_coupons', 'value2') == 1) {
                    if (coupon::isValid($coupon, $itemID)) {
                        $coupon_id = coupon::getIdByCode($coupon);

                        $coupon_percent = coupon::getValue($coupon_id, 'percent');

                        if ($coupon_percent == 100) {
                            $price = 0;
                        } else {
                            $orgprice = $price;
                            $price = $coupon_percent / 100 * $orgprice;
                            $price = $orgprice - $price;
                            $price = number_format($price, 2, '.', '');
                        }
                    }
                }
            }
        } elseif ($type == 'raffle') {
            if ($moneyType == null) {
                $price = $db->getOne("SELECT price FROM raffles WHERE id = ?", $itemID);
            }

            if ($moneyType == 'credits') {
                $price = $db->getOne("SELECT credits FROM raffles WHERE id = ?", $itemID);
            }
        } elseif ($type == 'credits') {
            $price = $db->getOne("SELECT price FROM credit_packages WHERE id = ?", $itemID);
        }

        return $price;
    }
}
