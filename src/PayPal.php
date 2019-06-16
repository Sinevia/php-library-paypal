<?php

namespace Sinevia;

/**
 * The PayPal class makes integration of the PayPal payment system convenient
 * and provides easy methods to generate a submit form.
 * <code>
 * // In test mode
 * $paypal = new \Sinevia\PayPal();
 * // For active mode
 * // $paypal = new \Sinevia\PayPal(false);
 *
 * // The amount to be paid
 * $paypal->amount(20);
 *
 * // The email to be paid to
 * $paypal->email("youremail@server.com");
 *
 * // The item name
 * $paypal->item_name("My New eBook");
 *
 * // The return on success URL
 * $paypal->on_success(s::this_url());
 *
 * // The return on cancel URL
 * $paypal->on_cancel(s::this_url());
 *
 * // The return on notify URL
 * $paypal->on_notify(s::this_url());
 *
 * if($paypal->status()=="success"){
 *     echo "You have successfully paid through PayPal";
 *     exit;
 * }
 * </code>
 */
class PayPal
{
    private $paypal_url;
    public $ipn_error = "";
    public $payment_status = false;
    private $fields = array("cmd" => "_xclick", "currency_code" => "USD", "lc" => "US", "page_style" => "PayPal", "rm" => "2");
    private $log = false;
    private $log_directory = false;

    /**
     * @param $is_test whether to run the code in sandbox
     */
    public function __construct($is_test = true)
    {
        if ($is_test == true) {
            $this->paypal_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
        } else {
            $this->paypal_url = 'https://www.paypal.com/cgi-bin/webscr';
        }
    }
    
    /**
     * The ammount to be paid by the service buyer. This is required field for
     * the PayPal form generation.
     *
     * @param float $amount
     * @return PayPal
     */
    public function amount($amount)
    {
        if (is_numeric($amount) == false) {
            throw new \InvalidArgumentException('In class <b>' . get_class($this) . '</b> in method <b>amount($amount)</b>: Parameter <b>$amount</b> MUST BE of type Integer or Float - <b>' . (is_object($amount) ? get_class($amount) : gettype($amount)) . '</b> given!');
        }
        $this->field('amount', $amount);
        return $this;
    }
    
    /**
     * The ammount to be paid by the service buyer. This is required field for
     * the PayPal form generation.
     *
     * @param float $amount
     * @return PayPal
     */
    public function currency_code($currency_code)
    {
        $currency_codes = array(
            'AUD', 'BRL', 'CAD', 'CZK',
            'EUR', 'HKD', 'HUF', 'ILS',
            'JPY', 'MYR', 'MXN', 'NOK',
            'NZD', 'PHP', 'PLN', 'GBP',
            'SGD', 'SEK', 'CHF', 'TWD',
            'THB', 'TRY', 'USD',
        );
        if (is_string($currency_code) == false) {
            throw new \InvalidArgumentException('In class <b>' . get_class($this) . '</b> in method <b>currency_code($currency_code)</b>: Parameter <b>$currency_code</b> MUST BE of type Integer or Float - <b>' . (is_object($currency_code) ? get_class($currency_code) : gettype($currency_code)) . '</b> given!');
        }
        if (in_array($currency_code, $currency_codes) == false) {
            throw new \InvalidArgumentException('In class <b>' . get_class($this) . '</b> in method <b>currency_code($currency_code)</b>: Parameter <b>$currency_code</b> is NOT ACCEPTED by PayPal - <b>' . (is_object($currency_code) ? get_class($currency_code) : gettype($currency_code)) . '</b> given!');
        }
        $this->field('currency_code', $currency_code);
        return $this;
    }
    
    /**
     * The email the payment is to be made to. This is the "sellers" email.
     * This is required field for the PayPal form generation.
     *
     * @param String $email
     * @return PayPal
     */
    public function email($email)
    {
        if (is_string($email) == false) {throw new \InvalidArgumentException('In class <b>' . get_class($this) . '</b> in method <b>email($email)</b>: Parameter <b>$email</b> MUST BE of type String - <b>' . (is_object($email) ? get_class($email) : gettype($email)) . '</b> given!');}
        $this->field('business', $email);
        return $this;
    }

    /**
     * The image to be shown on the PayPal page.
     * PS. Not Working in Sandbox
     *
     * @param String $url
     * @return PayPal
     */
    public function image($url)
    {
        if (is_string($url) == false) {throw new \InvalidArgumentException('In class <b>' . get_class($this) . '</b> in method <b>image($url)</b>: Parameter <b>$url</b> MUST BE of type String - <b>' . (is_object($url) ? get_class($url) : gettype($url)) . '</b> given!');}
        if (s::str_starts_with($url, "http://") == false) {throw new \InvalidArgumentException('In class <b>' . get_class($this) . '</b> in method <b>image($url)</b>: Parameter <b>$url</b> MUST start with "http://" - <b>' . $url . '</b> given!');}
        $this->field('image_url', $url);
        return $this;
    }
    
    /**
     * The name of the item to sell. This is required field for
     * the PayPal form generation.
     *
     * @param String $item_name
     * @return PayPal
     */
    public function item_name($item_name)
    {
        if (is_string($item_name) == false) {throw new \InvalidArgumentException('In class <b>' . get_class($this) . '</b> in method <b>item_name($item_name)</b>: Parameter <b>$item_name</b> MUST BE of type String - <b>' . (is_object($item_name) ? get_class($item_name) : gettype($item_name)) . '</b> given!');}
        $this->field('item_name', $item_name);
        return $this;
    }

    /**
     * The pass through variable to track the product or service purchased.
     * The value specified will be returned back to merchant upon completion
     * to identify the product the notification refers to.
     *
     * @param Integer $number
     * @return PayPal
     */
    public function item_number($item_number)
    {
        if (is_numeric($item_number) == false) {
            throw new \InvalidArgumentException('In class <b>' . get_class($this) . '</b> in method <b>item_number($item_number)</b>: Parameter <b>$item_number</b> MUST BE of type Integer - <b>' . (is_object($item_number) ? get_class($item_number) : gettype($item_number)) . '</b> given!');
        }
        $this->field('item_number', $item_number);
        return $this;
    }

    /**
     * The URL that the buyer will see after a cancelled payment
     *
     * @param String $url
     * @return PayPal
     */
    public function on_cancel($url)
    {
        if (is_string($url) == false) {
            throw new \InvalidArgumentException('In class <b>' . get_class($this) . '</b> in method <b>on_cancel($url)</b>: Parameter <b>$url</b> MUST BE of type String - <b>' . (is_object($url) ? get_class($url) : gettype($url)) . '</b> given!');
        }
        if ($this->str_starts_with($url, "https://") == false AND $this->str_starts_with($url, "http://") == false) {
            throw new \InvalidArgumentException('In class <b>' . get_class($this) . '</b> in method <b>on_cancel($url)</b>: Parameter <b>$url</b> MUST start with "https://" or "http://" - <b>' . $url . '</b> given!');
        }
        $this->field('cancel_return', $url);
        return $this;
    }
    
    /**
     * The URL that the buyer will see after a successful payment
     *
     * @param String $url
     * @return PayPal
     */
    public function on_success($url)
    {
        if (is_string($url) == false) {
            throw new \InvalidArgumentException('In class <b>' . get_class($this) . '</b> in method <b>on_success($url)</b>: Parameter <b>$url</b> MUST BE of type String - <b>' . (is_object($url) ? get_class($url) : gettype($url)) . '</b> given!');
        }
        if ($this->str_starts_with($url, "https://") == false AND $this->str_starts_with($url, "http://") == false) {
            throw new \InvalidArgumentException('In class <b>' . get_class($this) . '</b> in method <b>on_success($url)</b>: Parameter <b>$url</b> MUST start with "https://" or "http://" - <b>' . $url . '</b> given!');
        }
        $this->field('return', $url);
        return $this;
    }

    //========================= START OF METHOD ===========================//
    //  METHOD: on_notify                                                  //
    //=====================================================================//
    /**
     * The URL that PayPal will notify about the payment
     *
     * @param String $url
     * @return PayPal
     */
    public function on_notify($url)
    {
        if (is_string($url) == false) {
            throw new \InvalidArgumentException('In class <b>' . get_class($this) . '</b> in method <b>on_notify($url)</b>: Parameter <b>$url</b> MUST BE of type String - <b>' . (is_object($url) ? get_class($url) : gettype($url)) . '</b> given!');
        }
        if ($this->str_starts_with($url, "https://") == false AND $this->str_starts_with($url, "http://") == false) {
            throw new \InvalidArgumentException('In class <b>' . get_class($this) . '</b> in method <b>on_notify($url)</b>: Parameter <b>$url</b> MUST start with "https://" or "http://" - <b>' . $url . '</b> given!');
        }
        $this->field('notify_url', $url);
        return $this;
    }

    /**
     * Specifies, the shipping charge, if specified it will be added on
     * top of the price of the item.
     *
     * @param float $amount
     * @return PayPal
     */
    public function shipping($shipping_cost)
    {
        if (is_numeric($shipping_cost) == false) {
            throw new \InvalidArgumentException('In class <b>' . get_class($this) . '</b> in method <b>shipping($shipping_cost)</b>: Parameter <b>$shipping_cost</b> MUST BE of type Integer or Float - <b>' . (is_object($shipping_cost) ? get_class($shipping_cost) : gettype($shipping_cost)) . '</b> given!');
        }
        $this->field('shipping', $shipping_cost);
        return $this;
    }
    
    /**
     * Sets fileds to the PayPal class. For available fields see the PayPal
     * API.
     *
     * @param String $name
     * @param String $value
     * @return PayPal
     */
    public function field($name, $value)
    {
        if ($name == "return") {
            $value .= (strpos($value, "?") === false) ? "?pp_a=success" : "&pp_a=success";
        }
        if ($name == "cancel_return") {
            $value .= (strpos($value, "?") === false) ? "?pp_a=cancel" : "&pp_a=cancel";
        }
        if ($name == "notify_url") {
            $value .= (strpos($value, "?") === false) ? "?pp_a=notify" : "&pp_a=notify";
        }
        $this->fields[$name] = (string) $value;
        return $this;
    }
    
    /**
     * Returns a form ready to be send to PayPal for payment
     *
     * @return S_Form
     */
    public function form($content = "")
    {
        // START: Check needed variables
        if (isset($this->fields['amount']) == false) {
            throw new \RuntimeException("The amount for the PayPal Form is not set.");
        }

        if (isset($this->fields['business']) == false) {
            throw new \RuntimeException("The bussiness email (money receiver) for the PayPal Form is not set.");
        }

        if (isset($this->fields['item_name']) == false) {
            throw new \RuntimeException("The item name (description of the item) for the PayPal Form is not set.");
        }

        // END: Check needed variables
        $form = '';
        $form .= '<form name="FORM_PAYPAL" method="post" action="' . $this->paypal_url . '">';
        $form .= $content;
        foreach ($this->fields as $name => $value) {
            $form .= '<input type="hidden" name="' . $name . '" value="' . $value . '">';
        }
        $form .= '</form>';
        return $form;
    }
    
    /**
     * Gets the status of the PayPal request. Convenient, if the PayPal
     * class is used on one page.
     * @return mixed String (success, cancel, notify) or false
     */
    public function status()
    {
        if (isset($_REQUEST["pp_a"]) && $_REQUEST["pp_a"] == "success") {
            return "success";
        }

        if (isset($_REQUEST["pp_a"]) && $_REQUEST["pp_a"] == "cancel") {
            return "cancel";
        }

        if (isset($_REQUEST["pp_a"]) && $_REQUEST["pp_a"] == "notify") {
            return "notify";
        }

        return false;
    }

    /**
     * Verifies the IPN success.
     * @return Boolean true, on success or false, on fail
     */
    public function verify_ipn()
    {
        $url = parse_url($this->paypal_url);
        $host = $url["host"];
        $path = $url["path"];

        // START: Prepare the post string
        $post_string = '';
        foreach ($_POST as $field => $value) {
            $this->ipn_data["$field"] = $value;
            $post_string .= $field . '=' . urlencode(stripslashes($value)) . '&';
        }
        $post_string .= "cmd=_notify-validate"; // append ipn command
        $header = "POST " . $path . " HTTP/1.1\r\n";
        $header .= "Host: " . $host . "\r\n";
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $header .= "Content-Length: " . strlen($post_string) . "\r\n";
        $header .= "Connection: close\r\n\r\n";
        $header .= $post_string . "\r\n\r\n";
        // END: Prepare the post string

        // START: Opening PayPal verification Connection
        //$fp = fsockopen($host,"80",$err_num,$err_str,30);
        $fp = fsockopen('ssl://' . $host, '443', $err_num, $err_str, 30);

        // END: Opening PayPal Verification Connection

        // START: Checking, if connection has failed Y=> return FALSE
        if ($fp === false) {if ($this->log) {
            $this->write_log("failed", "No Connection" . "\r\n\r\nPOST DATA:\r\n\r\n" . var_export($_POST, true));
        }

            $this->ipn_error = "no_connection";return false;}
        // END: Checking, if connection has failed
        //if($this->log)$this->write_log("success", $header."\r\n\r\nPOST DATA:\r\n\r\n".var_export($_POST,true));
        // assign posted variables to local variables
        //$item_name = $_POST['item_name'];
        //$item_number = $_POST['item_number'];
        //$payment_status = $_POST['payment_status'];
        //$payment_amount = $_POST['mc_gross'];
        //$payment_currency = $_POST['mc_currency'];
        //$txn_id = $_POST['txn_id'];
        //$receiver_email = $_POST['receiver_email'];
        //$payer_email = $_POST['payer_email'];
        if (isset($_POST['payment_status'])) {
            $payment_status = $_POST['payment_status'];
        }

        // START: Getting the verification result & closing connection
        $result = "";
        fputs($fp, $header);while (!feof($fp)) {$result .= fgets($fp, 1024);}fclose($fp);
        // END: Getting the verification result & closing connection

        // START: Checking, if transaction is verified Y=> return TRUE
        if (preg_match('/VERIFIED/i', $result)) {
            // START: Success
            if ($this->log) {
                $this->write_log("success", $result . "\r\n\r\nPOST DATA:\r\n\r\n" . var_export($_POST, true));
            }

            return true;
            // END: Success
        }
        // END: Checking, if transaction is verified

        // START: Failed
        if ($this->log) {
            $this->write_log("failed", $result . "\r\n\r\nPOST DATA:\r\n\r\n" . var_export($_POST, true));
        }

        $this->ipn_error = 'ipn_validation_failed';
        return false;
        // END: Failed
    }

    /**
     * Turns on the logging for this class in the specified directory.
     * @param $dirname
     * @return void
     */
    public function log($directory)
    {
        if (is_dir($directory) == false) {
            throw new \RuntimeException('PayPal Logging Directory "' . $directory . '" DOES NOT exist!');
        }

        $this->log_directory = $directory;
        $this->log = true;

    }
    
    /**
     * Turns on the logging for this class in the specified directory.
     * @param $dirname
     * @return void
     */
    private function write_log($success, $msg)
    {
        $time = date("Y.m.d_h\hi\ms\s", time());
        $log_file = $this->log_directory . DIRECTORY_SEPARATOR . "PayPal_" . $time . '.txt';
        if ($success == "success") {$msg = "SUCCESS\n\n" . $msg;} else { $msg = "FAILED\r\n\r\n" . $msg;}
        if (file_exists($log_file)) {$logs = file_get_contents($log_file);
            $msg = $logs . $msg;}
        $result = file_put_contents($log_file, $msg);
    }

    /**
     * Checks if a string starts with another string.
     * <code>
     * $result = s_str_starts_with("http://server.com","http://");
     * // $result is true
     * </code>
     * @return bool true on success, false otherwise
     */
    private function str_starts_with($string, $match)
    {
        return (substr($string, 0, strlen($match)) == $match) ? true : false;
    }
}
