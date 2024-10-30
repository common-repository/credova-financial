<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once dirname(__FILE__) . '/credova-log.php';

class CredovaClient
{

    private $username;
    private $password;
    private $access_token;
    private $api_url;
    const PRODUCTION = "https://lending-api.credova.com";
    const STAGING    = "https://sandbox-lending-api.credova.com";
    const TIMEOUT    = 10;

    public function __construct($username, $password, $sandbox)
    {
        if (!function_exists('curl_init') || !function_exists('curl_setopt')) {
            throw new Exception("cURL support is required, but can't be found.");
        }

        $this->username = $username;
        $this->password = $password;
        if ($sandbox == 1) {
            $this->api_url = CredovaClient::STAGING;
        }
        if ($sandbox == 0) {
            $this->api_url = CredovaClient::PRODUCTION;
        }

        $this->access_token = null;
    }

    /**
     * Performs an authentication with Credova API.
     *
     * @return bool true or false
     * @throws Exception
     */
    public function authenticate()
    {
        $rv      = false;
        $body    = http_build_query(array("username" => $this->username, "password" => $this->password), '', '&');
        $headers = array("Content-Type" => "application/x-www-form-urlencoded");

        $resp = $this->call_api("POST", "v2/token", $body, $headers, false);

        if (!empty($resp['json']) and array_key_exists("jwt", $resp['json'])) {
            $this->access_token = $resp['json']['jwt'];
            $rv                 = $this->access_token;
        }
        return $rv;
    }

    public function authenticateo()
    {
        $rv      = false;
        $body    = http_build_query(array("username" => $this->username, "password" => $this->password), '', '&');
        $headers = array("Content-Type" => "application/x-www-form-urlencoded");

        $resp = $this->call_api_one("POST", "v2/token", $body, $headers, false);
        if ($resp) {
            if (!empty($resp['json']) and array_key_exists("jwt", $resp['json'])) {
                $this->access_token = $resp['json']['jwt'];
                $rv                 = $this->access_token;
            }
            return $rv;
        } else {
            return false;
        }
    }

    /**
     * Checks if we are authenticated.
     *
     * @return bool True if client is authenticated
     */
    public function is_authenticated()
    {
        return !empty($this->access_token);
    }

    public function is_ssl()
    {
        if (isset($_SERVER['HTTPS'])) {
            if ('on' == strtolower($_SERVER['HTTPS'])) {
                return true;
            }
            if ('1' == $_SERVER['HTTPS']) {
                return true;
            }
        } elseif (isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'])) {
            return true;
        }
        return false;
    }

    /**
     * Sends financing application to Credova.
     * @param   array  $application application assoc array structure
     * @param   string $cburl Webhook URL (must be HTTPS!)
     * @return  array  Array with application id and redirect url
     * @throws Exception
     */
    public function apply($application, $cburl = null, $cart_data = null, $posted_data = null, $redirectKey = null)
    {

        $this->log = new Credova_Log();
        $this->log->add("S T E P - 2");
        $this->log->info(__FILE__, __LINE__, __METHOD__);

        $authentication_token = $this->authenticate();
        $is_ssl               = $this->is_ssl();
        $path                 = 'v2/applications';
        $url                  = $this->api_url . '/' . $path;
        $body                 = json_encode($application);
        $basicauth            = 'Bearer ' . $authentication_token;
        if ($cburl && $is_ssl == 1) {
            $headers = array(
                'Authorization' => $basicauth,
                'Content-type'  => 'application/json',
                'Callback-Url'  => $cburl,
            );
        } else {
            $headers = array(
                'Authorization' => $basicauth,
                'Content-type'  => 'application/json',
            );
        }
        $args = array(
            'method'  => 'POST',
            'headers' => $headers,
            'body'    => $body,
            'timeout' => 70,
        );

        $response = wp_safe_remote_post($url, $args);
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            echo "Something went wrong: $error_message";
        } else {
            $result                = array('headers' => '', 'body' => '', 'status_code' => '', 'json' => '', 'api_error' => '');
            $result['headers']     = $response['headers'];
            $result['body']        = $response['body'];
            $result['status_code'] = $response['response']['code'];
            if ($result['body']) {
                $result['json'] = json_decode($result['body'], true);

                // status_code != 200
                if ($result['json'] and array_key_exists("errors", $result['json'])) {
                    $result['api_error'] = implode("", $result['json']['errors']);
                }
            }

            if (!empty($result['json']) and array_key_exists('publicId', $result['json']) and array_key_exists('link', $result['json'])) {
                $rv = array($result['json']['publicId'], $result['json']['link']);
                $checkoutData = [];
                
                $checkoutData['session_data'] = WC()->session->get_session_data();
                $checkoutData['cart_data'] = maybe_serialize($cart_data);
                $checkoutData['posted_data'] = maybe_serialize($posted_data);
                $checkoutData['packages'] = maybe_serialize(WC()->shipping()->get_packages());


                if (!empty($cart_data->avatax_rates)) {
                    $rates = [];
                    foreach ($cart_data->avatax_rates as $avatax_line_rates) {
                        foreach ($avatax_line_rates as $key => $rate) {
                            $rates[$key] = $rate->get_label();
                        }
                        break;
                    }
                    $checkoutData['rates'] = maybe_serialize($rates);
                }

                //current customer 
                $currentCustomerId = WC()->session->get_customer_id();
                if (is_user_logged_in()) {
                    $checkoutData['is_customer_login'] = true;
                    $checkoutData['customer_id'] = $currentCustomerId;
                } else {
                    $checkoutData['is_customer_login'] = true;
                    $checkoutData['customer_id'] = '';
                }
                $this->log->add("CHECKOUT DATA");
                $this->log->add($checkoutData);

                $serializeData = json_encode($checkoutData);

                $ipn = $result['json']['publicId'];
                global $wpdb;
                $table_name = $wpdb->prefix . 'credova_checkout';
                $item       = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE ipn = %s", $ipn), ARRAY_A);
                if (!isset($item['ipn'])) {
                    $wpdb->insert(
                        $table_name,
                        array(
                            'wc_session'  => $serializeData,
                            'ipn'         => $ipn,
                            'redirectkey'         => $redirectKey,
                        )
                    );
                } else {
                    $wpdb->query(
                        $wpdb->prepare("UPDATE $table_name 
                             SET wc_session = %s 
                             WHERE ipn = %s", $serializeData, $ipn)
                    );
                }

                //TESTING ORDER START FROM HERE
                

            } else {
                $rv = $result['api_error'];
            }
            return $rv;
        }
    }

    /**
     * Check application status by its public id.
     * @param   string    $public_id application public id
     * @param   string    $phone Optional phone number
     * @return  array     Array with application status
     * @throws Exception
     */
    public function check_status($public_id = null, $phone = null)
    {
        $authentication_token = $this->authenticate();
        if ($public_id) {
            $path = sprintf("v2/applications/%s/status", $public_id);
        } elseif ($phone) {
            $path = sprintf("v2/applications/phone/%s/status", $phone);
        }

        $url       = $this->api_url . '/' . $path;
        $basicauth = 'Bearer ' . $authentication_token;
        $headerss  = array(
            'Authorization' => $basicauth,
            'Content-type'  => 'application/json',
        );
        $argss = array(
            'method'  => 'GET',
            'headers' => $headerss,
            //'body'    => $bodyy,
            'timeout' => 70,
        );

        $response = wp_safe_remote_post($url, $argss);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            echo "Something went wrong: $error_message";
        } else {
            $result                = array('headers' => '', 'body' => '', 'status_code' => '', 'json' => '', 'api_error' => '');
            $result['headers']     = $response['headers'];
            $result['body']        = $response['body'];
            $result['status_code'] = $response['response']['code'];
            if ($result['body']) {
                $result['json'] = json_decode($result['body'], true);

                // status_code != 200
                if ($result['json'] and array_key_exists("errors", $result['json'])) {
                    $result['api_error'] = implode("", $result['json']['errors']);
                }
            }
            return $result['json'];
        }
    }

    /**
     * Obtains financing offers from lenders restricted by specified filters.
     * @param   string    $amount Desired amount
     * @param   string    $lender_code Optional lender code
     * @param   string    $store_id Optional store id
     * @return  array     Array with offers
     * @throws Exception
     */
    public function get_offers($amount, $lender_code = null, $store_id = null)
    {
        if (!$lender_code and !$store_id) {
            throw new Exception("Either lender_code or store_id must be specified.");
        }

        if ($lender_code) {
            $url = sprintf("v2/Calculator/Lender/%s/Amount/%s", $lender_code, $amount);
        } elseif ($store_id) {
            $url = sprintf("v2/Calculator/Store/%s/Amount/%s", $store_id, $amount);
        }

        $resp = $this->call_api("POST", $url);

        return $resp['json'];
    }

    /**
     * Obtains a list of configured lenders for a retailer.
     * @return array Array of lenders
     * @throws Exception
     */
    public function get_lenders()
    {
        $resp = $this->call_api("GET", "v2/Lenders");
        return $resp['json'];
    }

    /**
     * Obtains a list of stores for a retailer.
     * @return array Array of stores
     * @throws Exception
     */
    public function get_stores()
    {
        $resp = $this->call_api("GET", "v2/Stores");
        return $resp['json'];
    }

    /**
     * Signal Credova about a return from a customer.
     * @param string $public_id Apllication public id
     * @return true Array with a request status
     * @throws Exception
     */
    public function request_return($public_id, $data)
    {
        $path = sprintf("v2/applications/%s/requestreturn", $public_id);

        $fields = array(
            "returnType"           => $data['returnType'],
            "returnReasonPublicId" => $data['reason'],
        );
        $body = json_encode($fields);

        $authentication_token = $this->authenticate();
        $url                  = $this->api_url . '/' . $path;
        $basicauth            = 'Bearer ' . $authentication_token;
        $headers              = array(
            'Authorization' => $basicauth,
            'Content-type'  => 'application/json',
        );
        $args = array(
            'method'  => 'POST',
            'headers' => $headers,
            'body'    => $body,
            'timeout' => 70,
        );

        $response = wp_safe_remote_post($url, $args);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            echo "Something went wrong: $error_message";
        } else {
            $result                = array('headers' => '', 'body' => '', 'status_code' => '', 'json' => '', 'api_error' => '');
            $result['headers']     = $response['headers'];
            $result['body']        = $response['body'];
            $result['status_code'] = $response['response']['code'];
            if ($result['body']) {
                $result['json'] = json_decode($result['body'], true);

                // status_code != 200
                if ($result['json'] and array_key_exists("errors", $result['json'])) {
                    $result['api_error'] = implode("", $result['json']['errors']);
                }
            }
            return $result['json'];
        }
    }

    public function return_reasons()
    {
        $path                 = "v2/returnreasons";
        $authentication_token = $this->authenticate();
        $url                  = $this->api_url . '/' . $path;
        $basicauth            = 'Bearer ' . $authentication_token;

        $headers = array(
            'Authorization' => $basicauth,
            'Content-type'  => 'application/json',
        );
        $args = array(
            'method'  => 'GET',
            'headers' => $headers,
            'timeout' => 70,
        );

        $response = wp_safe_remote_post($url, $args);
        //echo "<pre>";
        //print_r($response);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            echo "Something went wrong: $error_message";
        } else {
            $result                = array('headers' => '', 'body' => '', 'status_code' => '', 'json' => '', 'api_error' => '');
            $result['headers']     = $response['headers'];
            $result['body']        = $response['body'];
            $result['status_code'] = $response['response']['code'];
            if ($result['body']) {
                $result['json'] = json_decode($result['body'], true);

                // status_code != 200
                if ($result['json'] and array_key_exists("errors", $result['json'])) {
                    $result['api_error'] = implode("", $result['json']['errors']);
                }
            }
            return $result['json'];
        }
    }

    /**
     * Upload invoice file in PDF format to Credova.
     * @param string $public_id Apllication public id
     * @param string $invoice_file path to an invoice file (PDF)
     * @return true True on success
     * @throws Exception
     */
    public function upload_invoice($public_id, $invoice_file)
    {
        $url      = sprintf("v2/applications/%s/uploadInvoice", $public_id);
        $boundary = uniqid();
        $body     = $this->encode_multipart($boundary, array($invoice_file));

        $headers = array(
            sprintf("Content-Type: multipart/form-data; boundary=%s", $boundary),
            sprintf("Content-Length: %d", strlen($body)),
        );

        $resp = $this->call_api("POST", $url, $body, $headers);

        if (!empty($resp['json']) and array_key_exists("status", $resp['json'])) {
            $rv = $resp['json']['status'];
        } else {
            throw new Exception("Credova ${url} API returned unexpected response=${resp['body']}");
        }
        return $rv;
    }

    /**
     * Creates new delivery information
     * @param   string Application public id
     * @param   array $data Information to be sent
     * @return  array Array with a creation status
     * @throws Exception
     */
    public function delivery($public_id, $data)
    {
        //$headers = array("Content-Type: application/json");
        $body                 = json_encode($data);
        $path                 = sprintf("v2/applications/%s/deliveryInformation/", $public_id);
        $authentication_token = $this->authenticate();
        $url                  = $this->api_url . '/' . $path;
        $basicauth            = 'Bearer ' . $authentication_token;
        $headers              = array(
            'Authorization' => $basicauth,
            'Content-type'  => 'application/json',
        );
        $args = array(
            'method'  => 'POST',
            'headers' => $headers,
            'body'    => $body,
            'timeout' => 70,
        );

        $response = wp_safe_remote_post($url, $args);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            echo "Something went wrong: $error_message";
        } else {
            $result                = array('headers' => '', 'body' => '', 'status_code' => '', 'json' => '', 'api_error' => '');
            $result['headers']     = $response['headers'];
            $result['body']        = $response['body'];
            $result['status_code'] = $response['response']['code'];
            if ($result['body']) {
                $result['json'] = json_decode($result['body'], true);

                // status_code != 200
                if ($result['json'] and array_key_exists("errors", $result['json'])) {
                    $result['api_error'] = implode("", $result['json']['errors']);
                }
            }
            return $result['json'];
        }
    }

    /**
     * Insert federal license information
     * @param   array $data Information to be sent
     * @return  string PublicId
     * @throws Exception
     */
    public function create_federal_license($data)
    {
        $body    = json_encode($data);
        $headers = array("Content-Type" => "application/json");

        $resp = $this->call_api("POST", "v2/federalLicense/", $body, $headers);

        if (!empty($resp['json']) and array_key_exists('publicId', $resp['json'])) {
            $rv = $resp['json']['publicId'];
        } else {
            throw new Exception("Credova v2/federalLicense API returned unexpected response=${resp['body']}");
        }

        return $rv;
    }

    /**
     * Get federal license information by it's number
     * @param   string $fl_number Federal license number
     * @return  array license information
     * @throws Exception
     */
    public function get_federal_license($fl_number)
    {
        $url  = sprintf("v2/federalLicense/licenseNumber/%s", $fl_number);
        $resp = $this->call_api("GET", $url);
        return $resp['json'];
    }

    /**
     * Gets the monthly lowest payment option, financed period, and early buyout options for a specific store
     * @return array Array with calculations
     * @throws Exception
     */
    public function get_lowest_payment($amount)
    {
        $url  = sprintf("v2/calculator/store/%s/amount/%s/lowestPaymentOption", $this->username, $amount);
        $resp = $this->call_api("POST", $url);
        return $resp['json'];
    }

    /**
     * Gets the monthly lowest payment option, financed period, and early buyout options for a specific store
     * @return array Array with calculations
     */
    public function get_lowest_payment_one($amount)
    {
        $url  = sprintf("v2/calculator/store/%s/amount/%s/lowestPaymentOption", $this->username, $amount);
        $resp = $this->call_api_one("POST", $url);
        return $resp['json'];
    }

    /**
     * Upload federal license file in PDF format to Credova.
     * @param string $fl_public_id License public id
     * @param string $fl_license path to an license file (PDF)
     * @return true True on success
     * @throws Exception
     */
    public function upload_fl($fl_public_id, $fl_license)
    {
        $url      = sprintf("v2/federalLicense/%s/uploadFile", $fl_public_id);
        $boundary = uniqid();
        $body     = $this->encode_multipart($boundary, array($fl_license));

        $headers = array(
            sprintf("Content-Type: multipart/form-data; boundary=%s", $boundary),
            sprintf("Content-Length: %d", strlen($body)),
        );

        $resp = $this->call_api("POST", $url, $body, $headers);

        if (!empty($resp['json']) and array_key_exists("status", $resp['json'])) {
            $rv = $resp['json']['status'];
        } else {
            throw new Exception("Credova ${url} API returned unexpected response=${resp['body']}");
        }
        return $rv;
    }

    //
    // Private methods
    //
    public function call_api($method, $path, $body = null, $headers = array(), $send_auth = true)
    {

        $result = $this->send_request($method, $path, $body, $headers, $send_auth);

        if (!empty($result['api_error'])) {
            throw new Exception($result['api_error']);
        } elseif ($result['status_code'] != 200) {
            throw new Exception($result['status_code'] . '--' . $result['body']);
        }

        return $result;
    }

    public function call_api_one($method, $path, $body = null, $headers = array(), $send_auth = true)
    {

        $result = $this->send_request($method, $path, $body, $headers, $send_auth);

        if (!empty($result['api_error'])) {
            $result = false;
        } elseif ($result['status_code'] != 200) {
            $result = false;
        }

        return $result;
    }


    private function encode_multipart($boundary, $filenames, $mime = 'application/pdf')
    {
        $data = '';
        $crlf = "\r\n";

        foreach ($filenames as $name) {
            $data .= "--" . $boundary . $crlf
                . 'Content-Disposition: form-data; name="file"; filename="' . $name . '"' . $crlf
                . 'Content-Type: ' . $mime . $crlf;

            $data .= $crlf;
            $data .= file_get_contents($name) . $crlf;
        }
        $data .= "--" . $boundary . "--" . $crlf;

        return $data;
    }
    public function send_request_one($method, $path, $body = null, $headers = array(), $send_auth = true)
    {
        $url = $this->api_url . '/' . $path;

        if ($send_auth and !$this->access_token) {
            //   throw new Exception("You must call authenticate() method first.");
            return false;
        }

        if ($this->access_token) {
            $headers = array('Authorization' => 'Bearer ' . $this->access_token);
        }
        switch ($method) {
            case 'POST':
                $argss = array(
                    'method'  => $method,
                    'headers' => $headers,
                    'body'    => $body,
                    'timeout' => 70,
                );
                $response = wp_safe_remote_post($url, $argss);
                break;

            case 'GET':
                $argss = array(
                    'method'  => $method,
                    'headers' => $headers,
                    //'body'    => $bodyy,
                    'timeout' => 70,
                );
                $response = wp_remote_post($url, $argss);
                break;
        }
        $result = array('headers' => '', 'body' => '', 'status_code' => '', 'json' => '', 'api_error' => '');

        if (is_wp_error($response) || empty($response['headers'])) {
            return false;
        }

        if ($response) {
            $result['headers']     = $response['headers'];
            $result['body']        = $response['body'];
            $result['status_code'] = $response['response']['code'];
            if ($result['body']) {
                $result['json'] = json_decode($result['body'], true);

                // status_code != 200
                if ($result['json'] and array_key_exists("errors", $result['json'])) {
                    $result['api_error'] = implode("", $result['json']['errors']);
                }
            }
        }
        return $result;
    }
    public function send_request($method, $path, $body = null, $headers = array(), $send_auth = true)
    {
        $url = $this->api_url . '/' . $path;

        if ($send_auth and !$this->access_token) {
            throw new Exception("You must call authenticate() method first.");
        }

        if ($this->access_token) {
            $headers = array('Authorization' => 'Bearer ' . $this->access_token);
        }
        switch ($method) {
            case 'POST':
                $argss = array(
                    'method'  => $method,
                    'headers' => $headers,
                    'body'    => $body,
                    'timeout' => 70,
                );
                $response = wp_safe_remote_post($url, $argss);
                break;

            case 'GET':
                $argss = array(
                    'method'  => $method,
                    'headers' => $headers,
                    //'body'    => $bodyy,
                    'timeout' => 70,
                );
                $response = wp_remote_post($url, $argss);
                break;
        }
        $result = array('headers' => '', 'body' => '', 'status_code' => '', 'json' => '', 'api_error' => '');

        if (is_wp_error($response) || empty($response['headers'])) {
            return false;
        }

        if ($response) {
            $result['headers']     = $response['headers'];
            $result['body']        = $response['body'];
            $result['status_code'] = $response['response']['code'];
            if ($result['body']) {
                $result['json'] = json_decode($result['body'], true);

                // status_code != 200
                if ($result['json'] and array_key_exists("errors", $result['json'])) {
                    $result['api_error'] = implode("", $result['json']['errors']);
                }
            }
        }
        return $result;
    }
    public function add_references($public_id, $orders)
    {
        $path = $url = sprintf("v2/applications/%s/orders", $public_id);

        $body = json_encode($orders);

        $authentication_token = $this->authenticate();
        $url                  = $this->api_url . '/' . $path;
        $basicauth            = 'Bearer ' . $authentication_token;
        $headers              = array(
            'Authorization' => $basicauth,
            'Content-type'  => 'application/json',
        );
        $args = array(
            'method'  => 'POST',
            'headers' => $headers,
            'body'    => $body,
            'timeout' => 70,
        );

        $response = wp_safe_remote_post($url, $args);
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            echo "Something went wrong: $error_message";
        } else {
            $result                = array('headers' => '', 'body' => '', 'status_code' => '', 'json' => '', 'api_error' => '');
            $result['headers']     = $response['headers'];
            $result['body']        = $response['body'];
            $result['status_code'] = $response['response']['code'];
            if ($result['body']) {
                $result['json'] = json_decode($result['body'], true);

                // status_code != 200
                if ($result['json'] and array_key_exists("errors", $result['json'])) {
                    $result['api_error'] = implode("", $result['json']['errors']);
                }
            }
            return $result['json'];
        }
    }
    public function generate_token($username, $password)
    {
        if (isset($username) && isset($password)) {
            $headers = array(
                "Content-Type: application/x-www-form-urlencoded"
            );
            $fields = array(
                "username" => trim($username),
                "password" => trim($password)
            );
            $url = $this->api_url . "/v2/token";
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // we want response body
            curl_setopt($curl, CURLOPT_HEADER, true); // we want headers

            curl_setopt($curl, CURLOPT_VERBOSE, true);

            curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
            curl_setopt($curl, CURLOPT_ENCODING, ''); // we accept all supported data compress formats
            curl_setopt($curl, CURLINFO_HEADER_OUT, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($fields));

            $response = curl_exec($curl);
            $result = array('headers' => '', 'body' => '', 'status_code' => '', 'json' => '', 'api_error' => '');

            $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
            $result['headers'] = substr($response, 0, $header_size);
            $result['body'] = substr($response, $header_size);
            $result['status_code'] = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($result['body']) {
                $result['json'] = json_decode($result['body'], true);

                // status_code != 200
                if ($result['json'] and array_key_exists("errors", $result['json'])) {
                    $result['api_error'] = implode("", $result['json']['errors']);
                }
            }

            return $result;
        }
    }
}
