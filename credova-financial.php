<?php

/**
 * Plugin Name: Credova Financial
 * Plugin URI: https://wordpress.org/plugins/credova-financial/
 * Description: Credova is about choices and the freedom to shop and pay how you want.
 * Author: Credova
 * Author URI: https://credova.com/
 * Version: 2.3.9
 * Requires at least: 5.0.0
 * Tested up to: 6.6
 * $woocommerce requires at least: 3.8.1
 * $woocommerce tested up to: 4.1.1
 *
 */

defined('ABSPATH') || exit;

// Define CREDOVA_PLUGIN_FILE.
if (!defined('CREDOVA_PLUGIN_FILE')) {
    define('CREDOVA_PLUGIN_FILE', __FILE__);
}

// Include the main Credova_Financial class.
if (!class_exists('Credova_Financial')) {
    include_once dirname(__FILE__) . '/includes/credova.php';
}

global $credova_db_version;
$credova_db_version = '1.7';

define('BASE_URL', get_bloginfo('url'));
define('WC_GATEWAY_CREDOVA_VERSION', '2.3.9');

function credova_install()
{
    global $wpdb;
    global $credova_db_version;
    $table_name        = $wpdb->prefix . 'credova_info';
    $credova_checkout      = $wpdb->prefix . 'credova_checkout';

    $charset_collate = $wpdb->get_charset_collate();
    $sql             = "CREATE TABLE $table_name (
        id int(11) NOT NULL AUTO_INCREMENT,
        shop_order_id varchar(255) NOT NULL,
        store_name varchar(255) NOT NULL,
        federal_license varchar(255) NOT NULL,
        fl_public_id varchar(255) NOT NULL,
        fl_upload_status varchar(255) NOT NULL,
        cart_id varchar(255) NOT NULL,
        transaction_date varchar(255) NOT NULL,
        customer_name varchar(255) NOT NULL,
        customer_address text NOT NULL,
        customer_city varchar(255) NOT NULL,
        customer_state varchar(255) NOT NULL,
        customer_zipcode varchar(255) NOT NULL,
        customer_email varchar(255) NOT NULL,
        customer_phone varchar(255) NOT NULL,
        payment_status varchar(255) NOT NULL,
        created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        total_inc_tax varchar(255) NOT NULL,
        woo_order_status varchar(255) NOT NULL,
        credova_public_id varchar(255) NOT NULL,
        credova_lender_name varchar(255) NOT NULL,
        credova_lender_code varchar(255) NOT NULL,
        credova_approval_amount varchar(255) NOT NULL,
        credova_borrowed_amount varchar(255) NOT NULL,
        financing_partner_name varchar(255) NOT NULL,
        financing_partner_code varchar(255) NOT NULL,
        invoice_upload text NOT NULL,
        delivery_info text NOT NULL,
        refund_status text NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    $sql_credova_checkout = "CREATE TABLE " . $credova_checkout . " (
             `id` int(11) NOT NULL AUTO_INCREMENT,
              `wc_session` longtext,
              `ipn` varchar(255) DEFAULT NULL,
              `success_order_id` varchar(255) DEFAULT NULL,
              `redirectkey` varchar(255) DEFAULT NULL,
              `updated_at` datetime NOT NULL,
              PRIMARY KEY (`id`)
        ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
    dbDelta($sql_credova_checkout);
    add_option('credova_db_version', $credova_db_version);
}
register_activation_hook(__FILE__, 'credova_install');
require_once dirname(__FILE__) . '/includes/credova-admin.php';

global $credova_enabled, $credova_title, $credova_min_finance_amount, $credova_max_finance_amount, $credova_testmode, $credova_api_username, $credova_api_password, $aslowaslist, $flow_type, $popup_type, $ala_type;
$credova_details            = get_option('woocommerce_credova_settings');
$credova_enabled            = $credova_details['enabled'];
$credova_title              = $credova_details['title'];
$credova_min_finance_amount = $credova_details['min_finance_amount'];
$credova_max_finance_amount = $credova_details['max_finance_amount'];
$credova_testmode           = $credova_details['testmode'];
$credova_api_username       = $credova_details['api_username'];
$credova_api_password       = $credova_details['api_password'];
$aslowaslist                = $credova_details['aslowaslist'];
$flow_type                  = $credova_details['flow_type'];
$popup_type                 = $credova_details['popup_type'];
$ala_type                   = $credova_details['ala_type'];

function woocommerce_credova_missing_wc_notice()
{
    /* translators: 1. URL link. */
    echo '<div class="error"><p><strong>' . sprintf(esc_html__('Credova requires WooCommerce to be installed and active. You can download %s here.', 'woocommerce-credova'), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>') . '</strong></p></div>';
}
if (!function_exists('plugins_api')) {
    require_once(ABSPATH . 'wp-admin/includes/plugin-install.php');
}
function old_credova_wc_notice()
{
    $args = array(
        'slug' => 'credova-financial',
        'fields' => array(
            'version' => true
        )
    );

    /** Prepare our query */
    $call_api = plugins_api('plugin_information', $args);

    /** Check for Errors & Display the results */
    if (is_wp_error($call_api)) {
        $api_error = $call_api->get_error_message();
    } else {
        if (!empty($call_api->version)) {
            $version_latest = $call_api->version;
            $homepage = $call_api->homepage;
            if ($version_latest != WC_GATEWAY_CREDOVA_VERSION) {
?>
                <div class="notice notice-warning is-dismissible">
                    <p><?php _e('There is a new version of Credova Financial available.'); ?> <a target="_blank" href="<?php echo $homepage; ?>">View version <?php echo WC_GATEWAY_CREDOVA_VERSION; ?> details</a> or <a href="<?php echo admin_url() ?>plugins.php">update now</a></p>
                </div>
<?php
            }
        }
    }
}
/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter('woocommerce_payment_gateways', 'credova_add_gateway_class');
function credova_add_gateway_class($gateways)
{
    $gateways[] = 'WC_Credova_Gateway'; // your class name is here
    return $gateways;
}

function show_credova_on_product_page($atts)
{
    $credova_details      = get_option('woocommerce_credova_settings');
    $credova_enabled      = $credova_details['enabled'];
    $credova_testmode     = $credova_details['testmode'];
    $credova_api_username = $credova_details['api_username'];
    $credova_ala_type     = $credova_details['ala_type'];
    $credova_popup_type   = $credova_details['popup_type'];
    $credova_hide_brand   = $credova_details['hide_brand'];
    $credova_min_finance_amount   = $credova_details['min_finance_amount'];
    $credova_max_finance_amount   = $credova_details['max_finance_amount'];

    $credova_brand_attr = ($credova_hide_brand == "yes") ? 'data-hide-brand="true"' : '';

    extract(shortcode_atts(array(
        'amount' => 1,
        'type' => '',
    ), $atts, 'credova-button'));
    $pric = $amount;
    if ($type == 'single-product') {
        if (!is_admin() && is_product()) {
            global $product;
            if (!is_object($product)) $product = wc_get_product(get_the_ID());
            if ($product->is_type('variable')) {
                $pric = $product->get_variation_price();
            } else {
                $pric = $product->get_price();
            }
        }
    }
    if ($credova_enabled == "yes" && ($pric >= $credova_min_finance_amount) && ($pric <= $credova_max_finance_amount)) {

        if ($credova_ala_type == "yes") {
            $return_string = '<p class="credova-button" id="credova_white" data-amount="' . esc_html($pric) . '" data-type="' . $credova_popup_type . '"' . $credova_brand_attr . '></p>';
        } else {
            $return_string = '<p class="credova-button" data-amount="' . esc_html($pric) . '" data-type="' . $credova_popup_type . '" ' . $credova_brand_attr . '></p>';
        }

        $return_string .= '<script src="https://plugin.credova.com/plugin.min.js"></script><script>';

        if ($credova_testmode == "no") {
            $return_string .= 'CRDV.plugin.config({environment: CRDV.Environment.Production, store: "' . $credova_api_username . '" });';
        } else {
            $return_string .= 'CRDV.plugin.config({environment: CRDV.Environment.Sandbox, store: "' . $credova_api_username . '" });';
        }

        $return_string .= 'CRDV.plugin.inject("credova-button"); </script>';
    } else {
        $return_string = "";
    }

    return $return_string;
}

function register_shortcodes()
{
    add_shortcode('credova-button', 'show_credova_on_product_page');
}
add_action('init', 'register_shortcodes');

/*
 * custom scripts add for all pages
 */

if ($credova_enabled == 'yes' && $credova_api_username) {
    add_action('wp_enqueue_scripts', 'credova_plugin_js');
    function credova_plugin_js()
    {
        wp_register_script('credovaa', 'https://plugin.credova.com/plugin.min.js', false, null, true);
        wp_enqueue_script('credovaa');
        wp_register_style('credova_style', plugins_url('assets/css/credova_style.css', __FILE__));
        wp_enqueue_style('credova_style');
    }

    function remove_version_scripts_styles($src)
    {
        if (strpos($src, 'yourfile.js')) {
            $src = remove_query_arg('ver', $src);
        }
        return $src;
    }
    add_filter('script_loader_src', 'remove_version_scripts_styles', 9999);

    add_action('wp_footer', 'credova_custom_scripts');
    function credova_custom_scripts()
    {
        wp_enqueue_script('woocommerce_product_credova', plugins_url('assets/js/product_credova.js', __FILE__), array('jquery'), false, true);
        $credova_details = get_option('woocommerce_credova_settings');
        $credova_details = array_intersect_key($credova_details, array_flip(['api_username', 'enabled', 'testmode', 'aslowaslist', 'min_finance_amount', 'max_finance_amount', 'flow_type', 'popup_type', 'ala_type', 'checkout_mode']));
        wp_localize_script('woocommerce_product_credova', 'product_credova_params', array(
            'credova_details' => $credova_details,
        ));
        wp_localize_script('woocommerce_product_credova', 'myAjax', array('ajaxurl' => admin_url('admin-ajax.php')));
        wp_enqueue_script('woocommerce_product_credova');
    }
}

function credova_back_enqueue_admin_script($hook)
{
    if ('admin.php' != $hook) {
        wp_enqueue_script('credova_back_script', plugin_dir_url(__FILE__) . 'assets/js/back.js', array(), '1.0');
        add_thickbox();
    }
}

add_action('admin_enqueue_scripts', 'credova_back_enqueue_admin_script');

/*
 * This action hook unset WooCommerce payment gateway
 */
add_filter('woocommerce_available_payment_gateways', 'credova_payment_gateway_disable_total_amount');
function credova_payment_gateway_disable_total_amount($available_gateways)
{
    if (isset($available_gateways['credova'])) {
        global $woocommerce, $product_object;
        $min_finance_amount = $available_gateways['credova']->settings['min_finance_amount'];
        $max_finance_amount = $available_gateways['credova']->settings['max_finance_amount'];
        $disable            = 0;

        if (isset($woocommerce->cart) && is_checkout()) {
            if (isset($available_gateways['credova']) && $woocommerce->cart->total <= $min_finance_amount || $woocommerce->cart->total >= $max_finance_amount) {
                unset($available_gateways['credova']);
            } else {
                if (isset($available_gateways['credova'])) {
                    foreach (WC()->cart->get_cart_contents() as $key => $values) {
                        $credova_product_check = wc_get_product($values['product_id']);
                        $credoa_values_check   = $credova_product_check->get_meta('_credova_product_block');
                        if ($credoa_values_check == 1) {
                            $disable = 1;
                        }
                    }

                    if ($disable == 1) {
                        unset($available_gateways['credova']);
                    }
                }
            }

            $credova_details = get_option('woocommerce_credova_settings');
            $testmode   = ($credova_details['testmode'] == 'yes') ? 1 : 0;
            $username   = $credova_details['api_username'];
            $password   = $credova_details['api_password'];
            $respp  = array();
            $client = new CredovaClient($username, $password, $testmode);
            $auth = $client->authenticateo();
            if ($auth == false) {
                unset($available_gateways['credova']);
            }

            return $available_gateways;
        } else {
            return $available_gateways;
        }
    } else {
        return $available_gateways;
    }
}

function credova_product_create_custom_field()
{
    global $product_object;
    $credova_values_endis  = $product_object->get_meta('_credova_product_endis_new');
    $credova_product_block = $product_object->get_meta('_credova_product_block');

    if ($credova_values_endis == '') {
        $credova_values_endis = '0';
    } elseif ($credova_values_endis == '1') {
        $credova_values_endis = '1';
    } elseif ($credova_values_endis == '0') {
        $credova_values_endis = '0';
    }

    if ($credova_product_block == '') {
        $credova_product_block = '0';
    } elseif ($credova_product_block == '1') {
        $credova_product_block = '1';
    } elseif ($credova_product_block == '0') {
        $credova_product_block = '0';
    }

    $args = array(
        'id'       => '_credova_product_endis_new',
        'label'    => __('Disable Credova ALA on Listing and Product page: ', 'woocommerce'),
        'selected' => true,
        'value'    => $credova_values_endis,
        'options'  => [
            '1' => __('Yes', 'woocommerce'),
            '0' => __('No', 'woocommerce'),
        ],
    );

    $args_1 = array(
        'id'       => '_credova_product_block',
        'label'    => __('Disable Credova on checkout: ', 'woocommerce'),
        'selected' => true,
        'value'    => $credova_product_block,
        'options'  => [
            '1' => __('Yes', 'woocommerce'),
            '0' => __('No', 'woocommerce'),
        ],
    );

    woocommerce_wp_select($args);
    woocommerce_wp_select($args_1);
}

function credova_product_save_custom_field($post_id)
{
    $credova_product             = wc_get_product($post_id);
    $credova_product_value       = isset($_POST['_credova_product_endis_new']) ? $_POST['_credova_product_endis_new'] : '';
    $credova_product_block_value = isset($_POST['_credova_product_block']) ? $_POST['_credova_product_block'] : '';
    $credova_product->update_meta_data('_credova_product_endis_new', sanitize_text_field($credova_product_value));
    $credova_product->update_meta_data('_credova_product_block', sanitize_text_field($credova_product_block_value));
    $credova_product->save();
}

function credovaAsLowAsListing()
{
    $credova_details = get_option('woocommerce_credova_settings');
    if ($credova_details['aslowaslist'] == 'yes') {
        global $product;

        $supported_products = apply_filters('supported_product_types', array('simple', 'variable', 'grouped', 'composite', 'bundle'));

        if (!$product->is_type($supported_products)) {
            return;
        }

        $price = $product->get_price() ? $product->get_price() : 0;

        if ($product->is_type('grouped')) {
            $price = getGroupedProductPrice($product);
        }

        if ($product->is_type('composite')) {
            $price = $product->get_min_raw_price();
        }

        $id = get_the_ID();

        $credova_product_check = wc_get_product(get_the_ID());
        $credoa_values_check   = $credova_product_check->get_meta('_credova_product_endis_new');
        if ($credoa_values_check != '1') {
            renderCredova($price, 'category', $id);
        }
    }
}

function getGroupedProductPrice($product)
{
    $children_ids = $product->get_children();
    $prices = array();
    foreach ($children_ids as $child_id) {
        $child_product = wc_get_product($child_id);
        if ($child_product && $child_product->is_type('simple') && $child_product->is_visible()) {
            $price = $child_product->get_price();
            $prices[] = $price;
        }
    }

    if (!empty($prices)) {
        $min_price = max($prices);

        return $min_price;
    }


    // $children = array_filter(array_map('wc_get_product', $product->get_children()), array($this, 'filterVisibleGroupChild'));
    // uasort($children, array($this, 'orderGroupedProductByPrice'));
    // return reset($children)->get_price();
}

function credovaAsLowAsProduct()
{
    global $product;

    $supported_products = apply_filters('supported_product_types', array('simple', 'variable', 'grouped', 'composite', 'bundle'));

    if (!$product->is_type($supported_products)) {
        return;
    }

    $price = $product->get_price() ? $product->get_price() : 0;

    if ($product->is_type('grouped')) {
        $price = getGroupedProductPrice($product);
    }

    if ($product->is_type('composite')) {
        $price = $product->get_min_raw_price();
    }

    $credova_product_check = wc_get_product(get_the_ID());
    $credoa_values_check   = $credova_product_check->get_meta('_credova_product_endis_new');
    if ($credoa_values_check != '1') {
        renderCredova($price, 'product');
    }
}

function renderCredova($amount, $pageType, $id = NULL)
{
    $credova_details            = get_option('woocommerce_credova_settings');
    $credova_min_finance_amount = $credova_details['min_finance_amount'];
    $credova_max_finance_amount = $credova_details['max_finance_amount'];
    $credova_popup_type         = $credova_details['popup_type'];
    $credova_ala_type           = $credova_details['ala_type'];
    $credova_hide_brand         = $credova_details['hide_brand'];

    $credova_brand_attr = ($credova_hide_brand == "yes") ? 'data-hide-brand="true"' : '';
    if ($credova_ala_type == "yes") {
        if ($id) {
            $message = '<p class="credova-response-amount-' . $id . '" id="credova_white" data-amount="' . $amount . '" data-type="' . $credova_popup_type . '"' . $credova_brand_attr . '></p>';

            $message  = $message . '<script>jQuery(document).ready(function(){
                CRDV.plugin.inject("credova-response-amount-' . $id . '");
            });</script>';
        } else {
            $message = '<p class="credova-response-amount" id="credova_white" data-amount="' . $amount . '" data-type="' . $credova_popup_type . '"' . $credova_brand_attr . '></p>';
        }
    } else {
        if ($id) {
            $message = '<p class="credova-response-amount-' . $id . '" data-amount="' . $amount . '" data-type="' . $credova_popup_type . '" ' . $credova_brand_attr . '></p>';
            $message  = $message . '<script>jQuery(document).ready(function(){
                CRDV.plugin.inject("credova-response-amount-' . $id . '");
            });</script>';
        } else {
            $message = '<p class="credova-response-amount" data-amount="' . $amount . '" data-type="' . $credova_popup_type . '" ' . $credova_brand_attr . '></p>';
        }
    }

    if (($amount >= $credova_min_finance_amount) && ($amount <= $credova_max_finance_amount)) {
        echo $message;
    }
}

/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action('plugins_loaded', 'credova_init_gateway_class');
function credova_init_gateway_class()
{

    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'woocommerce_credova_missing_wc_notice');
        return;
    }

    $product_page_hook = 'woocommerce_single_product_summary';
    $producaslowast_priority = 15;

    if (!empty(get_option('woocommerce_credova_settings'))) {
        $credova_details     = get_option('woocommerce_credova_settings');
        $aslowasproduct_hook = isset($credova_details['aslowasproduct_hook']) ? $credova_details['aslowasproduct_hook'] : '';
        $aslowasproduct_priority = isset($credova_details['aslowasproduct_priority']) ? $credova_details['aslowasproduct_priority'] : '';
        if (!empty($aslowasproduct_hook)) {
            $product_page_hook = $aslowasproduct_hook;
        }
        if (!empty($aslowasproduct_priority)) {
            $producaslowast_priority = $aslowasproduct_priority;
        }
    }


    add_action('admin_notices', 'old_credova_wc_notice');
    add_action('woocommerce_product_options_general_product_data', 'credova_product_create_custom_field');
    add_action('woocommerce_process_product_meta', 'credova_product_save_custom_field');
    add_action('woocommerce_after_shop_loop_item', 'credovaAsLowAsListing', 10);
    add_action($product_page_hook, 'credovaAsLowAsProduct', $producaslowast_priority);

    global $credova_db_version;
    if (get_site_option('credova_db_version') != $credova_db_version) {
        credova_install();
    }

    // Import helper classes
    require_once 'includes/credova-log.php';
    require_once 'includes/credova-checkout.php';
    require_once 'includes/credova-helper.php';

    class WC_Credova_Gateway extends WC_Payment_Gateway
    {

        /**
         * Class constructor, more about it in Step 3
         */
        public function __construct()
        {
            $this->id                 = 'credova'; // payment gateway plugin ID
            // $this->icon               = apply_filters('woocommerce_gateway_icon', plugins_url('assets/images/credova-logo.svg', __FILE__)); // URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields         = true;
            $this->method_title       = 'Credova';
            $this->method_description = 'Financing Redefined - financing option that fits your budget and lifestyle<br/><br/><b>NOTE:</b> To add Credova ‘As low as..’ display on any single product page, you can use the following shortcode: <b>[credova-button type=single-product]</b> and for any other page use shortcode: <b>[credova-button amount=300]</b>- The amount can be adjusted.'; // will be displayed on the options page

            $this->supports = array(
                'products',
            );

            // Method with all the options fields
            $this->init_form_fields();

            $this->log = new Credova_Log();

            // Load the settings.
            $this->init_settings();
            $this->title              = $this->get_option('title');
            $this->min_finance_amount = $this->get_option('min_finance_amount');
            $this->max_finance_amount = $this->get_option('max_finance_amount');
            $this->enabled            = $this->get_option('enabled');
            $this->testmode           = 'yes' === $this->get_option('testmode');
            $this->aslowaslist        = $this->get_option('aslowaslist');
            $this->api_username       = $this->get_option('api_username');
            $this->api_password       = $this->get_option('api_password');
            $this->flow_type          = $this->get_option('flow_type');

            // This action hook saves the settings
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
            add_action('woocommerce_order_status_completed', array($this, 'webhook_credova_order_completed'));
            add_action('woocommerce_before_checkout_form', array($this, 'woocommerceOrderButtonText'));
            if (array_key_exists('section', $_REQUEST) && $_REQUEST['section'] == "credova") {
                add_filter('generate_token_notice', array(Credova_Helper::add_generate_token_notice(), 'add_generate_token_notice'));
            }

            if ($this->flow_type == 'post') {
                add_action('woocommerce_api_credova_scripts_on_checkout', array($this, 'credova_scripts_on_checkout'));
                add_action('woocommerce_calculate_totals', [$this, 'calculate_totals_for_fees_meta_data']);
                add_action('woocommerce_api_credova_payment_success', array($this, 'credova_payment_success'));
                add_action('init', array(&$this, 'check_credova_response'));
                add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'check_credova_response')); //update for woocommerce >2.0
            } else {
                add_action('woocommerce_review_order_before_payment', array($this, 'reviewOrderBeforePayment'));
                add_action('wp_enqueue_scripts', array($this, 'enqueueScripts'));
                // add_action('init', array(&$this, 'verifyCredovaResponse'));
                add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'verifyCredovaResponse'));
            }
            add_action('woocommerce_api_credova_payment_block_success', array($this, 'credova_payment_block_success'));
            add_action('woocommerce_api_credova_payment_cancel', array($this, 'credova_payment_cancel'));
        }




        public function calculate_totals_for_fees_meta_data($cart)
        {
            $fees_meta = WC()->session->get('fees_meta');
            $update    = false;

            // Loop through applied fees
            foreach ($cart->get_fees() as $fee_key => $fee) {

                // Set the fee in the fee custom meta data array
                $fees_meta[$fee_key] = $fee;
                $update = true;
            }
            // If any fee meta data doesn't exist yet, we update the WC_Session custom meta data array
            if ($update) {
                WC()->session->set('fees_meta', $fees_meta);
            }
        }

        protected function set_customer_address_fields($field, $key, $data)
        {
            $billing_value  = null;
            $shipping_value = null;

            if (isset($data["billing_{$field}"]) && is_callable(array(WC()->customer, "set_billing_{$field}"))) {
                $billing_value  = $data["billing_{$field}"];
                $shipping_value = $data["billing_{$field}"];
            }

            if (isset($data["shipping_{$field}"]) && is_callable(array(WC()->customer, "set_shipping_{$field}"))) {
                $shipping_value = $data["shipping_{$field}"];
            }

            if (!is_null($billing_value) && is_callable(array(WC()->customer, "set_billing_{$field}"))) {
                WC()->customer->{"set_billing_{$field}"}($billing_value);
            }

            if (!is_null($shipping_value) && is_callable(array(WC()->customer, "set_shipping_{$field}"))) {
                WC()->customer->{"set_shipping_{$field}"}($shipping_value);
            }
        }


        public function process_payment($order_id)
        {
            $this->log->add("Process Payment Started for Order ID: " . $order_id);
            $this->log->add("REQUEST_URI: " . $_SERVER['REQUEST_URI']);

            // Check if this is a block-based checkout
            $is_block_checkout = $this->is_block_checkout();
            $this->log->add("Is Block Checkout: " . ($is_block_checkout ? 'Yes' : 'No'));


            if ($is_block_checkout) {
                $this->log->add("Handling Block-based Checkout");
                $order = wc_get_order($order_id);
                $result = $this->prepare_order_flow($order);
                $this->log->add("Block Checkout Result: " . print_r($result, true));
                return $result;
            } else {
                // Handle traditional checkout
                if ($this->flow_type == 'pre') {
                    $order            = wc_get_order($order_id);
                    $order_key        = version_compare(WC_VERSION, '3.0', '<') ? $order->order_key : $order->get_order_key();
                    $query_vars       = WC()->query->get_query_vars();
                    $order_pay        = $query_vars['order-pay'];
                    $confirmation_url = add_query_arg(
                        array(
                            'action'    => 'credova_checkout',
                            'order_id'  => $order_id,
                            'order_key' => $order_key,
                        ),
                        WC()->api_request_url(get_class($this))
                    );
                    $redirect_url = add_query_arg(
                        array(
                            'credova'          => '1',
                            'order_id'         => $order_id,
                            'nonce'            => wp_create_nonce('credova-checkout-order-' . $order_id),
                            'key'              => $order_key,
                            'cart_hash'        => WC()->cart->get_cart_hash(),
                            'confirmation_url' => $confirmation_url,
                        ),
                        get_permalink(wc_get_page_id('checkout')) . $order_pay . '/' . $order_id . '/'
                    );

                    return array(
                        'result'   => 'success',
                        'redirect' => $redirect_url,
                    );
                } else {
                    return array(
                        'result'   => 'success',
                        'redirect' => '',
                    );
                }
            }
        }

        private function is_block_checkout()
        {
            $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            return strpos($request_uri, '/wp-json/wc/store/v1/checkout') !== false;
        }

        private function prepare_order_flow($order)
        {
            $this->log->add("Preparing Order Flow for Order ID: " . $order->get_id());
            $order_id = $order->get_id();
            $order_key = $order->get_order_key();

            $confirmation_url = add_query_arg(
                array(
                    'wc-api' => 'credova_payment_block_success',
                    'order_id'  => $order_id,
                    'order_key' => $order_key,
                    'block_checkout' => '1'
                ),
                get_permalink(wc_get_page_id('checkout'))
            );

            $cancel_url = add_query_arg(
                array(
                    'wc-api' => 'credova_payment_cancel',
                    'order_id'  => $order_id,
                    'order_key' => $order_key,
                    'block_checkout' => '1'
                ),
                get_permalink(wc_get_page_id('checkout'))
            );

            $this->log->add("Confirmation URL: " . $confirmation_url);
            $this->log->add("Cancel URL: " . $cancel_url);

            $api_username = $this->api_username;
            $api_password = $this->api_password;
            $testmode     = $this->testmode;

            $cart_products = [];

            // Add line items
            foreach ($order->get_items() as $item) {
                $product_price = $order->get_item_subtotal($item) * $item->get_quantity();
                $cart_products[] = [
                    'id'          => $item->get_product_id(),
                    'description' => $item->get_name(),
                    'quantity'    => $item->get_quantity(),
                    'value'       => $product_price,
                ];
            }

            // Add shipping
            $shipping_total = $order->get_shipping_total();
            if (!empty($shipping_total)) {
                $cart_products[] = [
                    "id" => "shipping_rate",
                    "description" => "shipping_rate",
                    "quantity" => "1",
                    "value" => $shipping_total
                ];
            }

            // Add tax
            $tax_total = $order->get_total_tax();
            if (!empty($tax_total)) {
                $cart_products[] = [
                    "id" => "tax",
                    "description" => "tax",
                    "quantity" => "1",
                    "value" => $tax_total
                ];
            }

            // Add discount
            $discount_total = $order->get_total_discount();
            if (!empty($discount_total)) {
                $cart_products[] = [
                    "id" => "coupon",
                    "description" => "coupon",
                    "quantity" => "1",
                    "value" => -$discount_total
                ];
            }

            // Add fees
            $fee_total = $order->get_total_fees();
            if (!empty($fee_total) && $fee_total > 0) {
                $cart_products[] = [
                    "id" => "fee",
                    "description" => "fee",
                    "quantity" => "1",
                    "value" => $fee_total
                ];
            }

            // Add gift cards
            $giftcards = WC()->session->get('_wc_gc_giftcards');
            if (!empty($giftcards)) {
                $gift_card_amount = $giftcards[0]['amount'];
                $cart_products[] = [
                    "id" => "giftcard",
                    "description" => "giftcard",
                    "quantity" => "1",
                    "value" => -$gift_card_amount
                ];
            }

            // Sanitize cart products
            $sant_cart_products = array_map(function ($product) {
                return array_map('sanitize_text_field', $product);
            }, $cart_products);

            $this->log->add("Cart Products: " . print_r($cart_products, true));

            // Prepare customer data
            $billing_first_name = sanitize_text_field($order->get_billing_first_name());
            $billing_last_name  = sanitize_text_field($order->get_billing_last_name());
            $billing_email      = sanitize_text_field($order->get_billing_email());
            $street             = sanitize_text_field($order->get_billing_address_1() . ' ' . $order->get_billing_address_2());
            $city               = sanitize_text_field($order->get_billing_city());
            $regionId           = sanitize_text_field($order->get_billing_state());
            $postcode           = sanitize_text_field(substr($order->get_billing_postcode(), 0, 5));
            $telephone          = sanitize_text_field($order->get_billing_phone());

            // Format telephone
            $telephone = $this->format_telephone($telephone);

            if (preg_match("/^[0-9]{3}-[0-9]{3}-[0-9]{4}$/", $telephone)) {
                $application = [
                    "publicId" => "",
                    "storeCode"     => $api_username,
                    "firstName"     => $billing_first_name,
                    "middleInitial" => "",
                    "lastName"      => $billing_last_name,
                    "dateOfBirth"   => "",
                    "mobilePhone"   => $telephone,
                    "email"         => $billing_email,
                    "address"       => array(
                        "street"  => $street,
                        "zipCode" => $postcode,
                        "city"    => $city,
                        "state"   => $regionId,
                    ),
                    "products"      => $sant_cart_products,
                    "redirectUrl"   => $confirmation_url,
                    "cancelUrl"     => $cancel_url,
                ];

                $this->log->add("application data");
                $this->log->add($sant_cart_products);
                $this->log->add("Prepared Credova Application: " . print_r($application, true));
                $client = new CredovaClient($api_username, $api_password, $testmode);
                $client->authenticate();
                $resp = $client->apply($application, $confirmation_url, null);
                $this->log->add("Credova API Response: " . print_r($resp, true));

                $this->log->add("application response");
                $this->log->add($resp);

                if (isset($resp[1]) && strpos($resp[1], "Welcome") !== false) {
                    $this->log->add("S T E P - 3");
                    $ipn = $resp[0];
                    $this->log->add("try ipn");
                    $this->log->add($ipn);

                    if ($ipn != "") {
                        $this->save_credova_info($order, $ipn, $billing_first_name, $billing_last_name, $street, $city, $regionId, $postcode, $billing_email, $telephone);
                        $this->log->add("success");
                    }

                    // Update order status to 'on-hold' or another appropriate status
                    $order->update_status('on-hold', __('Awaiting Credova payment confirmation', 'woocommerce'));

                    // Empty the cart
                    WC()->cart->empty_cart();

                    return array(
                        'result'   => 'success',
                        'redirect' => $resp[1],
                    );
                } else {
                    // If Credova process fails, update order status and return failure
                    $order->update_status('cancelled', __('Credova payment process failed', 'woocommerce'));
                    return array(
                        'result'   => 'failure',
                        'messages' => __('Failed to initiate Credova process', 'woocommerce'),
                    );
                }
            } else {
                $order->update_status('cancelled', __('Invalid phone number format', 'woocommerce'));
                return array(
                    'result'   => 'failure',
                    'messages' => __('Invalid phone number format', 'woocommerce'),
                );
            }
        }

        private function format_telephone($telephone)
        {
            $telephone = str_replace(' ', '-', $telephone);
            $telephone = preg_replace('/[^A-Za-z0-9-]/', '', $telephone);

            if (preg_match('/(\d{3})(\d{3})(\d{4})$/', $telephone, $matches)) {
                $telephone = $matches[1] . '-' . $matches[2] . '-' . $matches[3];
            }

            if (substr_count($telephone, '-') == 3) {
                $telephone = substr($telephone, strpos($telephone, "-") + 1);
            }

            return $telephone;
        }

        private function save_credova_info($order, $ipn, $firstName, $lastName, $street, $city, $regionId, $postcode, $billing_email, $telephone)
        {
            global $wpdb;
            $table_name = $wpdb->prefix . 'credova_info';
            $wpdb->insert(
                $table_name,
                array(
                    'shop_order_id'           => $order->get_id(),
                    'federal_license'         => '',
                    'fl_public_id'            => '',
                    'fl_upload_status'        => '',
                    'cart_id'                 => '',
                    'transaction_date'        => $order->get_date_created()->format('Y-m-d H:i:s'),
                    'customer_name'           => $firstName . ' ' . $lastName,
                    'customer_address'        => $street,
                    'customer_city'           => $city,
                    'customer_state'          => $regionId,
                    'customer_zipcode'        => $postcode,
                    'customer_email'          => $billing_email,
                    'customer_phone'          => $telephone,
                    'payment_status'          => '',
                    'created_at'              => current_time('mysql'),
                    'total_inc_tax'           => $order->get_total(),
                    'woo_order_status'        => 'Pending payment',
                    'credova_public_id'       => $ipn,
                    'credova_lender_name'     => '',
                    'credova_lender_code'     => '',
                    'credova_approval_amount' => '',
                    'credova_borrowed_amount' => '',
                    'financing_partner_name'  => '',
                    'financing_partner_code'  => '',
                    'invoice_upload'          => '',
                    'delivery_info'           => '',
                    'refund_status'           => '',
                )
            );
        }






        function enqueueScripts()
        {
            if (!$this->isCheckoutAutoPostPage()) {
                return;
            }

            $order = $this->validateOrderFromRequest();
            if (false === $order) {
                return;
            }

            wp_enqueue_script('woocommerce_credova_checkout', plugins_url('assets/js/credova-checkout.js', __FILE__), array('jquery', 'jquery-blockui'), WC_GATEWAY_CREDOVA_VERSION, true);

            $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();

            $order_key = version_compare(WC_VERSION, '3.0', '<') ? $order->order_key : $order->get_order_key();

            $this->log->add("================================================");
            $this->log->add("PRE ORDER");
            $this->log->add("S T E P - 1");

            $confirmation_url = add_query_arg(
                array(
                    'action'    => 'credova_checkout',
                    'order_id'  => $order_id,
                    'order_key' => $order_key,
                ),
                WC()->api_request_url(get_class($this))
            );

            $api_username       = $this->api_username;
            $api_password       = $this->api_password;
            $testmode           = $this->testmode;

            foreach ((array) $order->get_items(array('line_item')) as $item) {
                $product_price = 0;
                $qty           = $item->get_quantity();
                $product_price = $order->get_item_subtotal($item) * $qty;
                $product_id    = $item->get_product_id();
                $description   = $item->get_name();

                $cart_products[] = [
                    'id'          => $product_id,
                    'description' => $description,
                    'quantity'    => $qty,
                    'value'       => $product_price,
                ];
            } // End foreach().

            $shipping_rate = $order->get_shipping_total();
            if (!empty($shipping_rate)) {
                $shipping_price  = array("id" => "shipping_rate", "description" => "shipping_rate", "quantity" => "1", "value" => $shipping_rate);
                $cart_products[] = $shipping_price;
            }

            $tax = $order->get_total_tax();
            if (!empty($tax)) {
                $tax             = array("id" => "tax", "description" => "tax", "quantity" => "1", "value" => $tax);
                $cart_products[] = $tax;
            }

            $coupon_amount_1 = $order->get_total_discount();
            if (!empty($coupon_amount_1)) {
                $coupon_amount_content = array("id" => "coupon", "description" => "coupon", "quantity" => "1", "value" => -$coupon_amount_1);
                $cart_products[]       = $coupon_amount_content;
            }

            $fee_amount = WC()->cart->get_fee_total();
            if (!empty($fee_amount) &&  $fee_amount > 0) {
                $fee_amount_content = array("id" => "fee", "description" => "fee", "quantity" => "1", "value" => $fee_amount);
                $cart_products[]       = $fee_amount_content;
            }

            $giftcards = WC()->session->get('_wc_gc_giftcards');
            if (!empty($giftcards)) {
                $gift_card_amount = $giftcards[0]['amount'];
                $gift_card_amount_content = array("id" => "giftcard", "description" => "giftcard", "quantity" => "1", "value" => -$gift_card_amount);
                $cart_products[]       = $gift_card_amount_content;
            }

            $sant_cart_products = array();
            $sant_i             = 0;
            foreach ($cart_products as $cartproducts) {
                $sant_cart_products[$sant_i]['id']          = sanitize_text_field($cartproducts['id']);
                $sant_cart_products[$sant_i]['description'] = sanitize_text_field($cartproducts['description']);
                $sant_cart_products[$sant_i]['quantity']    = sanitize_text_field($cartproducts['quantity']);
                $sant_cart_products[$sant_i]['value']       = sanitize_text_field($cartproducts['value']);
                $sant_i++;
            }

            $billing_first_name = sanitize_text_field($order->get_billing_first_name());
            $billing_last_name  = sanitize_text_field($order->get_billing_last_name());
            $billing_email      = sanitize_text_field($order->get_billing_email());
            $street             = sanitize_text_field($order->get_billing_address_1() . $order->get_billing_address_2());
            $city               = sanitize_text_field($order->get_billing_city());
            $regionId           = sanitize_text_field($order->get_billing_state());
            $postcode           = sanitize_text_field(substr($order->get_billing_postcode(), 0, 5));
            $telephone          = sanitize_text_field($order->get_billing_phone());
            $firstName          = $billing_first_name;
            $lastName           = $billing_last_name;
            $pubid              = "";

            $telephone = str_replace(' ', '-', $telephone);
            $telephone = preg_replace('/[^A-Za-z0-9-]/', '', $telephone);

            if (preg_match('/(\d{3})(\d{3})(\d{4})$/', $telephone, $matches)) {
                $telephone = $matches[1] . '-' . $matches[2] . '-' . $matches[3];
            } else {
                $telephone = $telephone;
            }

            if (substr_count($telephone, '-') == 3) {
                $telephone = substr($telephone, strpos($telephone, "-") + 1);
            }

            if (preg_match("/^[0-9]{3}-[0-9]{3}-[0-9]{4}$/", $telephone)) {
                $application = [
                    "publicId" => $pubid,
                    "storeCode"                => $api_username,
                    "firstName"                => $firstName,
                    "middleInitial"            => "",
                    "lastName"                 => $lastName,
                    "dateOfBirth"              => "",
                    "mobilePhone"              => $telephone,
                    "email"                    => $billing_email,
                    "address"                  => array(
                        "street"  => $street,
                        "zipCode" => $postcode,
                        "city"    => $city,
                        "state"   => $regionId,
                    ),
                    "products"                 => $sant_cart_products,
                    "redirectUrl"              => $confirmation_url,
                ];

                $this->log->add("application data");
                $this->log->add($sant_cart_products);

                $this->log->add("Pub Id");
                $this->log->add($pubid);

                $client = new CredovaClient($api_username, $api_password, $testmode);
                $client->authenticate();
                $resp = $client->apply($application, $confirmation_url, null);

                $this->log->add("application response");
                $this->log->add($resp);

                if (strpos($resp[1], "Welcome") !== false) {
                    $this->log->add("S T E P - 3");
                    $ipn = $resp[0];
                    $this->log->add("try ipn");
                    $this->log->add($ipn);

                    if ($ipn != "") {
                        global $wpdb;
                        $table_name = $wpdb->prefix . 'credova_info';
                        $aa         = $wpdb->insert(
                            $table_name,
                            array(
                                'shop_order_id'           => $order->get_id(),
                                'federal_license'         => '',
                                'fl_public_id'            => '',
                                'fl_upload_status'        => '',
                                'cart_id'                 => '',
                                'transaction_date'        => $order->get_date_created(),
                                'customer_name'           => $firstName . $lastName,
                                'customer_address'        => $order->get_billing_address_1() . $order->get_billing_address_2(),
                                'customer_city'           => $city,
                                'customer_state'          => $regionId,
                                'customer_zipcode'        => $postcode,
                                'customer_email'          => $billing_email,
                                'customer_phone'          => $telephone,
                                'payment_status'          => '',
                                'created_at'              => current_time('mysql'),
                                'total_inc_tax'           => $order->get_total(),
                                'woo_order_status'        => 'Pending payment',
                                'credova_public_id'       => $ipn,
                                'credova_lender_name'     => '',
                                'credova_lender_code'     => '',
                                'credova_approval_amount' => '',
                                'credova_borrowed_amount' => '',
                                'financing_partner_name'  => '',
                                'financing_partner_code'  => '',
                                'invoice_upload'          => '',
                                'delivery_info'           => '',
                                'refund_status'           => '',
                            )
                        );
                        $this->log->add("success");
                    }
                }
            }

            wp_localize_script('woocommerce_credova_checkout', 'credovaData', array('publicId' => $ipn, 'redirect' => $resp[1]));
            wp_enqueue_script('woocommerce_credova_checkout');
        }

        public function woocommerceOrderButtonText()
        {
            wp_register_script('credova_checkout_button', plugins_url('assets/js/credova_checkout_button.js', __FILE__), array('jquery'));
            wp_enqueue_script('credova_checkout_button');
        }

        function reviewOrderBeforePayment()
        {
            if (!$this->isCheckoutAutoPostPage()) {
                return;
            }


            $order = $this->validateOrderFromRequest();
            if (false === $order) {
                wp_die(__('Checkout using Credova failed. Please try checking out again later, or try a different payment source.'));
            }
        }

        function isCheckoutAutoPostPage()
        {
            if (!is_checkout()) {
                return false;
            }

            if (!isset($_GET['credova']) || !isset($_GET['order_id']) || !isset($_GET['nonce'])) {
                return false;
            }

            return true;
        }

        function validateOrderFromRequest()
        {
            if (empty($_GET['order_id'])) {
                return false;
            }

            if (empty($_GET['cart_hash'])) {
                return false;
            }

            $order_id = wc_clean($_GET['order_id']);

            if (!is_numeric($order_id)) {
                return false;
            }

            $order_id = absint($order_id);

            if (empty($_GET['nonce'])) {
                return false;
            }

            if (WC()->cart->get_cart_hash() !== $_GET['cart_hash']) {
                if (!wp_verify_nonce($_GET['nonce'], 'credova-checkout-order-' . $order_id)) {
                    return false;
                }
            }

            $order = wc_get_order($order_id);

            if (!$order) {
                return false;
            }

            return $order;
        }

        public function verifyCredovaResponse()
        {
            $this->log->add("S T E P - 4");
            $this->log->add("verifyCredovaResponse");
            $this->log->add($_GET);

            header('HTTP/1.1 200 OK');

            global $woocommerce;
            global $wpdb;

            $data = file_get_contents('php://input');

            $response = json_decode($data);
            $this->log->info(__FILE__, __LINE__, __METHOD__);
            $this->log->add($response);

            $credova_detail = get_option('woocommerce_credova_settings');
            $testmode   = ($credova_detail['testmode'] == 'yes') ? 1 : 0;
            $sand_text = $testmode ? 'Sandbox' : 'Production';

            $is_block_checkout = isset($_GET['block_checkout']) && $_GET['block_checkout'] === '1';
            $this->log->add("Is Block Checkout: " . ($is_block_checkout ? 'Yes' : 'No'));

            if ($is_block_checkout) {
                $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
                $order_key = isset($_GET['order_key']) ? wc_clean($_GET['order_key']) : '';
                $this->log->add("Order ID: " . $order_id . ", Order Key: " . $order_key);

                $order = wc_get_order($order_id);

                if ($order && $order->get_order_key() === $order_key && ($response->status == 'Signed' || $response->status == 'Approved')) {
                    if ($response->status == 'Signed') {
                        $note = __("Credova Financial <br> Application Id : " . $response->applicationId . " <br> Public Id : " . $response->publicId . " <br>  Environment : " . $sand_text . " <br> Status : " . $response->status . "  ");
                        $order->add_order_note($note);
                        $order->update_status('processing', '', true);
                        $order->save();
                        $this->log->add("Order status updated to processing");

                        $this->log->add("S T E P - 6");
                        $this->log->add("Order Updated");
                        $this->log->add($order_id);

                        $table_name = $wpdb->prefix . 'credova_checkout';
                        $wpdb->query(
                            $wpdb->prepare("UPDATE $table_name 
                                SET success_order_id = %s 
                                WHERE ipn = %s", $order_id, $response->publicId)
                        );

                        // Add Data to credova_info table
                        $this->update_credova_info($order, $response);
                    } else {
                        $this->log->add("Order status not updated (Approved status)");
                        $this->update_credova_info($order, $response);
                    }



                    $this->log->add("Callback End Data");
                } else {
                    $this->log->add("Order not found or status is not Signed/Approved.");
                }
            } else {
                if (empty($response->status)) {
                    $this->log->add("S T E P - 5");
                    $this->log->add("Empty Response");


                    $order_id = (!empty($_GET['order_id'])) ? absint($_GET['order_id']) : WC()->session->order_awaiting_payment;
                    if ($order_id && !$is_block_checkout) {
                        $order = wc_get_order($order_id);
                        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
                        if ('cancelled' == $status) {
                            if ($order->get_status() != 'processing') {
                                $note = 'Contract is not signed.';
                                $order->add_order_note($note);
                                $order->update_status('cancelled');
                                $order->save();
                                $this->log->add('Your order {$order_id} has been cancelled.');
                                wc_add_notice("Your order has been cancelled.", 'error');
                                wp_safe_redirect(WC()->cart->get_checkout_url());
                            } else {
                                $this->log->add('Order already signed.');
                                wp_safe_redirect($this->get_return_url($order));
                            }
                        } else {
                            wp_safe_redirect($this->get_return_url($order));
                        }
                    }
                    exit;
                } else {
                    $this->log->add("S T E P - 5");
                    $ipn = $response->publicId;
                    $this->log->add('====Credova : check_credova_response ====' . $ipn . '====');

                    $table_name = $wpdb->prefix . 'credova_info';
                    $item       = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE credova_public_id = %s", $ipn), ARRAY_A);
                    $this->log->add($item);
                    if ($item) {
                        $order_id = $item['shop_order_id'];
                        $order    = wc_get_order($order_id);

                        if ($response->status == 'Signed') {
                            $colm_id = array('shop_order_id' => $order_id);
                            $colms   = array(
                                'woo_order_status'        => 'Processing',
                                'payment_status'          => $response->status,
                                'credova_lender_name'     => $response->lenderName,
                                'credova_lender_code'     => $response->lenderCode,
                                'credova_approval_amount' => $response->approvalAmount,
                                'credova_borrowed_amount' => $response->borrowedAmount,
                                'financing_partner_name'  => $response->financingPartnerName,
                                'financing_partner_code'  => $response->financingPartnerCode,
                            );
                            $result = $wpdb->update($table_name, $colms, $colm_id);
                            $this->log->add('====Credova : check_credova_response ====Added====');

                            $this->log->add("Processing payment for order {$order_id}");

                            $note = __("Credova Financial <br> Application Id : " . $response->applicationId . " <br> Public Id : " . $ipn . " <br>  Environment : : " . $sand_text . " <br> Status : " . $response->status . "  ");
                            $order->add_order_note($note);
                            $order->update_status('processing');
                            $order->save();
                        } else if ($response->status == 'Declined') {
                            $note = 'Contract is Declined.';
                            $order->add_order_note($note);
                            $order->update_status('cancelled');
                            $order->save();
                        }
                    }
                }
            }
        }

        /**
         * Plugin options, we deal with it in Step 3 too
         */
        public function init_form_fields()
        {

            $this->form_fields = array(
                'enabled'            => array(
                    'title'       => 'Enable/Disable',
                    'label'       => 'Enable Credova',
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no',
                ),
                'title'              => array(
                    'title'       => 'Title',
                    'type'        => 'text',
                    'description' => 'This controls the title which the user sees during checkout.',
                    'default'     => 'Credova Financial',
                    'desc_tip'    => true,
                ),
                'min_finance_amount' => array(
                    'title'       => 'Minimum finance amount',
                    'type'        => 'number',
                    'description' => '',
                    'default'     => '0.01',
                    'custom_attributes' => array(
                        'min'  => '0.01',
                        'step' => '0.01'
                    ),
                    'validate' => array($this, 'validate_min_finance_amount'),
                ),
                'max_finance_amount' => array(
                    'title'       => 'Maximum finance amount',
                    'type'        => 'number',
                    'description' => '',
                    'default'     => '10000',
                ),
                'aslowaslist'        => array(
                    'title'       => 'As Low As',
                    'label'       => 'Enable as low as on listing page',
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no',
                ),
                'aslowasmini'        => array(
                    'title'       => 'Mini Cart ALA',
                    'label'       => 'Enable as low as on mini cart page',
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'yes',
                ),
                'aslowascart'        => array(
                    'title'       => 'Cart ALA',
                    'label'       => 'Enable as low as on cart page',
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'yes',
                ),
                'testmode'           => array(
                    'title'       => 'Test Mode',
                    'label'       => 'Enable Test Mode',
                    'type'        => 'checkbox',
                    'description' => 'Place the payment gateway in test mode.',
                    'default'     => 'yes',
                    'desc_tip'    => true,
                ),
                'ala_type'           => array(
                    'title'       => 'White Logo',
                    'label'       => 'Enable white logo',
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no',
                ),
                'hide_brand'         => array(
                    'title'       => 'Hide Brand',
                    'label'       => 'Hide Brand on As Low As',
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no',
                ),
                'api_username'       => array(
                    'title' => 'API Username',
                    'type'  => 'text',
                ),
                'api_password'       => array(
                    'title' => 'API Password',
                    'type'  => 'password',
                ),
                'flow_type'          => array(
                    'title'   => 'Checkout Flow Type',
                    'type'    => 'select',
                    'options' => array(
                        'post' => __('Post-Order (First Contract then Order)', 'woocommerce'),
                        'pre'  => __('Pre-Order (Order first then Contract)', 'woocommerce'),
                    ),
                    'default' => 'post',
                ),
                'popup_type'         => array(
                    'title'   => 'Credova Pop-up Type',
                    'type'    => 'select',
                    'options' => array(
                        'popup' => __('Iframe Pop-up', 'woocommerce'),
                        'modal' => __('Pop-up with separate window', 'woocommerce'),
                    ),
                    'default' => 'popup',
                ),
                'checkout_mode'         => array(
                    'title'   => 'Checkout Mode',
                    'type'    => 'select',
                    'options' => array(
                        'redirect' => __('Redirect', 'woocommerce'),
                        'modal' => __('Modal', 'woocommerce'),
                    ),
                    'default' => 'redirect',
                ),
                'customisation-title' => array(
                    'title'             => 'Customization',
                    'type'              => 'title',
                    'description'       => 'Customize the presentation of the Credova elements on your web store.  <em> <a id="credova_reset-to-default-link" onclick="credova_back_restore" style="cursor:pointer;text-decoration:underline;">Restore Defaults</a> </em> </p>'
                ),
                'aslowasproduct' => array(
                    'title'             => 'ALA Monthly Payment Info On Product Page',
                    'type'              => 'title',
                    'description'       => ' There are multiple product and listing page hooks <a href="#TB_inline?&width=600&height=550&inlineId=credova-wooview-id" class="thickbox">CLICK HERE</a> to view. <div id="credova-wooview-id" style="display:none;"><div class="elementor-widget-container"><h4 class="elementor-heading-title elementor-size-default">Available WooCommerce Product Page Hooks:</h4></div><ul style="list-style-type:none;"> <li>woocommerce_before_single_product_summary</li>  <li>woocommerce_product_thumbnails</li> <li> woocommerce_single_product_summary </li> <li> woocommerce_before_add_to_cart_form </li>  <li> woocommerce_before_add_to_cart_button </li> <li> woocommerce_before_single_variation </li> <li> woocommerce_single_variation </li> <li> woocommerce_after_single_variation </li> <li> woocommerce_after_add_to_cart_button </li> <li> woocommerce_after_add_to_cart_form </li> <li> woocommerce_product_meta_start </li> <li> woocommerce_product_meta_end </li> <li> woocommerce_after_single_product_summary </li></ul> </div>',

                ),
                'aslowasproduct_hook' => array(
                    'title'             => 'Product Page Hook Name',
                    'type'              => 'text',
                    'placeholder'       => 'Enter hook name (e.g. woocommerce_single_product_summary)',
                    'default'           => 'woocommerce_single_product_summary',
                    'description'       => 'Set the hook to be used for ALA monthly payment on product pages.'
                ),
                'aslowasproduct_priority' => array(
                    'title'             => 'Priority ( 1 to 999 )',
                    'type'              => 'number',
                    'placeholder'       => 'Enter a priority number',
                    'default'           => 15,
                    'description'       => 'Set the hook priority to be used for ALA monthly payment on product pages.'
                )
            );
        }

        public function validate_min_finance_amount($key, $value)
        {
            $value = floatval($value);
            if ($value < 0.01) {
                WC_Admin_Settings::add_error("Minimum finance amount must be at least 0.01");
                return '0.01'; // Set to minimum allowed value
            }
            return wc_format_decimal($value, 2);
        }

        public function thankyou_page()
        {
            return true;
        }

        /**
         * Payment Procedure
         */
        public function payment_fields()
        {

            global $woocommerce;
            $total      = $woocommerce->cart->total;

            $fees = base64_encode(json_encode($woocommerce->cart->get_fees()));
            $shipping_amount = base64_encode(json_encode($woocommerce->cart->shipping_tax_total));
            $tax_amount = base64_encode(json_encode($woocommerce->cart->tax_total));

            $api_username       = $this->api_username;
            $testmode           = $this->testmode;
            $order_type         = $this->flow_type;

            $credova_details      = get_option('woocommerce_credova_settings');
            $mode = $credova_details['checkout_mode'] ?? '';

            if ($testmode == 0) {
                $env = 'CRDV.Environment.Production';
            } else {
                $env = 'CRDV.Environment.Sandbox';
            }

            // Check if this is a block-based checkout
            $is_block_checkout = wp_doing_ajax() && isset($_POST['wc-ajax']) && $_POST['wc-ajax'] === 'update-order-review';

            if ($is_block_checkout) {
                // For block-based checkout, we'll keep it simple
                echo '<div id="credova-payment-description">';
                echo wpautop(wp_kses_post($this->description));
                echo '</div>';
            } else {

                if (empty($_POST)) {
                    return;
                }

                $script = <<<SCRIPT

            // Call the function when the payment method is changed
            document.addEventListener('change', function(event) {
                var target = event.target;
                if (target && target.matches('input[id="payment_method_credova"]')) {
                    if(preOrder() != true) {
                        disableOrderButton(true);
                    }
                } else if (target && target.matches('input[name="payment_method"]')) {
                    disableOrderButton(false);
                }
            });
            
            function findPosition(obj) {
                var currenttop = 0;
                if (obj.offsetParent) {
                    do {
                        currenttop += obj.offsetTop;
                    } while ((obj = obj.offsetParent));
                    return [currenttop];
                }
            }

            function disableOrderButton(isCredova) {
                var paymentMethod = document.querySelector('input[name="payment_method"]:checked');
                var intervalID;

                function checkNode() {
                    var orderBtn = document.getElementById("place_order");
                    if (orderBtn) {
                        if (isCredova) {
                            orderBtn.style.display = "none";
                        } else {
                            orderBtn.style.display = "block";
                        }
                        clearInterval(intervalID);
                    }
                }

                // Set the interval to check the node every 1 second
                intervalID = setInterval(checkNode, 1000);
            }
            
            function preOrder() {
                let valid = true;
                var order_type = '$order_type';
                if(order_type == 'post'){
                    valid = false;
                }
                return valid;
            }

            function showALA() {
                if (typeof CRDV != 'undefined') {
                var a = "<p class='checkout-ala-button' data-amount=$total data-type='text'></p>";
                jQuery(".payment_method_credova label").html(a);
                CRDV.plugin.config({ environment: $env, store: '$api_username' });
                CRDV.plugin.inject("checkout-ala-button");
                }
            }

            function create_checkout(res) {
                var mode = '$mode';
                if (mode == 'modal') {
                    var checko = CRDV.plugin.checkout(res.messages, res.confirmURL, res.cancelURL);
                } else {
                    window.location.href = res.redirect;
                }
            }

            function validateFields() {
                jQuery('.woocommerce-notices-wrapper').html("");
                jQuery('.woocommerce-error').remove();
                jQuery('form.checkout').addClass('processing').block({
                    message: null,
                    overlayCSS: {
                        background: '#fff',
                        opacity: 0.6
                    }
                });

                jQuery.ajaxSetup( {
                    dataFilter: function( raw_response, dataType ) {
                        // We only want to work with JSON
                        if ( 'json' !== dataType ) {
                            return raw_response;
                        }
                
                        if (isValidJSON(raw_response)) {
                            return raw_response;
                        } else {
                            // Attempt to fix the malformed JSON
                            var maybe_valid_json = raw_response.match( /{"result.*}/ );
                
                            if ( null === maybe_valid_json ) {
                                console.log( 'Unable to fix malformed JSON' );
                            } else if (isValidJSON(maybe_valid_json[0])) {
                                console.log( 'Fixed malformed JSON. Original:' );
                                console.log( raw_response );
                                raw_response = maybe_valid_json[0];
                            } else {
                                console.log( 'Unable to fix malformed JSON' );
                            }
                        }
                
                        return raw_response;
                    }
                } );
                
                function isValidJSON(text) {
                    try {
                        JSON.parse(text);
                        return true;
                    } catch {
                        return false;
                    }
                }
                
                jQuery.ajax({
                  type: "post",
                  data: jQuery(".checkout").serialize() + "&woocommerce_checkout_update_totals=1",
                  dataType: 'json',
                  url: "?wc-ajax=checkout",
                  success : function( response ) {
                    if (response['result'] === "failure") {
                        jQuery('.woocommerce-notices-wrapper:last').append(response['messages']);
                        if ( jQuery('.woocommerce-error').length === 0 ) {
                            initCredovaSession();
                        }else{
                            window.scrollTo(0, findPosition(jQuery('.woocommerce-notices-wrapper')[0]));
                            jQuery(".credova-financing-button button").text("Continue with Credova");
                            jQuery('form.checkout').removeClass('processing').unblock();
                        }
                    }
                  }
                });
            }

            function initCredovaSession() {
                jQuery('.woocommerce-notices-wrapper').html("");
                var e = jQuery(".checkout").serialize()+ "&c_fees=$fees&s_tax=$shipping_amount&t_total=$tax_amount";
                jQuery(".credova-financing-button button").text("Loading Credova ....");
                jQuery.ajax({
                    type: "post",
                    data: e,
                    url: "?wc-api=credova_scripts_on_checkout",
                    success : function( response ) {
                      if (response['result'] === "success") {
                          create_checkout(response);
                      }else{
                          jQuery('.woocommerce-notices-wrapper:last').append(response['messages']);
                          window.scrollTo(0, findPosition(jQuery('.woocommerce-notices-wrapper')[0]));
                          jQuery(".credova-financing-button button").text("Continue with Credova");
                          jQuery('form.checkout').removeClass('processing').unblock();
                      }
                    }
                  });
            }
            
            jQuery("#place_order").click(function (e) {
                if (document.querySelector('#payment_method_credova:checked')) {
                    if(preOrder() != true) {
                        e.preventDefault();
                        validateFields();
                    }
                }
            });

            jQuery("#place_order_credova").click(function (e) {
                if (document.querySelector('#payment_method_credova:checked')) {
                    if(preOrder() != true) {
                        e.preventDefault();
                        validateFields();
                    }
                }
            });

            if (document.querySelector('#payment_method_credova:checked')) {
                disableOrderButton(true);
            }

            showALA();
    SCRIPT;

                $html = <<<SCRIPT
            <input type="hidden" id="code-credova" value="$api_username">
            <div class='checkout-credova-slide' style='display: none;text-align: center;padding: 32px 19px;font-size: 14px'>
                <div id='as-low-as-more-info-disclaimer' style='font-size: 12px;'>You will open Credova for the payment after placing your order. Your order will not be shipped until you complete the process with Credova.</div>
                <div class='credova-financing-button' style='margin-top: 18px;'><button type="submit" class="button alt" name="woocommerce_checkout_place_order" id="place_order_credova" value="Place order" data-value="Place order">Continue with Credova</button></div>
            </div>
            <script>$script</script>
            <style>               
            .payment_method_credova {
                padding: 0 !important;
            }
            </style>                        
    SCRIPT;

                echo $html;
            }
        }

        public function webhook_credova_order_completed($order_id)
        {
            $order = wc_get_order($order_id);
            $method = $order->get_payment_method();
            if ($method == "credova") {
                global $wpdb;
                $table_name = $wpdb->prefix . 'credova_info';
                $item       = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE shop_order_id = %d", $order_id), ARRAY_A);
                if ($item) {
                    $colm_id = array('shop_order_id' => $order_id);
                    $colms   = array('woo_order_status' => 'Completed');
                    $result  = $wpdb->update($table_name, $colms, $colm_id);
                }
            }
        }

        public function check_credova_response()
        {
            global $wpdb;
            $data = file_get_contents('php://input');
            $response = json_decode($data);
            if (isset($response) && ($response->status == 'Signed' || $response->status == 'Approved')) {
                $this->log->info(__FILE__, __LINE__, __METHOD__);
                $this->log->add($response);
                $ipn = $response->publicId;

                $this->log->add('====Credova : Callback Status ' . $response->status . '====' . $ipn . '====');

                $credova_detail = get_option('woocommerce_credova_settings');
                $testmode   = ($credova_detail['testmode'] == 'yes') ? 1 : 0;
                $sand_text = $testmode ? 'Sandbox' : 'Production';

                $is_block_checkout = isset($_GET['block_checkout']) && $_GET['block_checkout'] === '1';
                $this->log->add("Is Block Checkout: " . ($is_block_checkout ? 'Yes' : 'No'));

                if ($is_block_checkout) {
                    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
                    $order_key = isset($_GET['order_key']) ? wc_clean($_GET['order_key']) : '';
                    $this->log->add("Order ID: " . $order_id . ", Order Key: " . $order_key);

                    $order = wc_get_order($order_id);

                    if ($order && $order->get_order_key() === $order_key && ($response->status == 'Signed' || $response->status == 'Approved')) {
                        if ($response->status == 'Signed') {
                            $note = __("Credova Financial <br> Application Id : " . $response->applicationId . " <br> Public Id : " . $response->publicId . " <br>  Environment : " . $sand_text . " <br> Status : " . $response->status . "  ");
                            $order->add_order_note($note);
                            $order->update_status('processing', '', true);
                            $order->save();
                            $this->log->add("Order status updated to processing");

                            $this->log->add("S T E P - 6");
                            $this->log->add("Order Updated");
                            $this->log->add($order_id);

                            $table_name = $wpdb->prefix . 'credova_checkout';
                            $wpdb->query(
                                $wpdb->prepare("UPDATE $table_name 
                                    SET success_order_id = %s 
                                    WHERE ipn = %s", $order_id, $response->publicId)
                            );

                            // Add Data to credova_info table
                            $this->update_credova_info($order, $response);
                        } else {
                            $this->log->add("Order status not updated (Approved status)");
                            $this->update_credova_info($order, $response);
                        }
                        $this->log->add("Callback End Data");
                    } else {
                        $this->log->add("Order not found or status is not Signed/Approved.");
                    }
                } else {
                    define('WOOCOMMERCE_CHECKOUT', true);
                    define('WOOCOMMERCE_CART', true);

                    if ($response->status == 'Signed') {
                        $table_name = $wpdb->prefix . 'credova_checkout';
                        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE ipn = %s AND success_order_id IS NULL", $ipn), ARRAY_A);
                        if ($item) {
                            // Traditional checkout logic
                            $checkoutData = json_decode($item['wc_session']);
                            // $available_gateways = WC()->payment_gateways->get_available_payment_gateways();

                            $session_data = maybe_unserialize($checkoutData->session_data);
                            $cart_data = maybe_unserialize($checkoutData->cart_data);
                            $posted_data = maybe_unserialize($checkoutData->posted_data);
                            $packages = maybe_unserialize($checkoutData->packages);

                            $this->log->add("S T E P - 5");
                            $this->log->add("Create Order");
                            //Create Order 
                            $order = wc_create_order();
                            $orderId = $order->get_id();
                            $order = new WC_Order($orderId);

                            $fields_prefix = array(
                                'shipping' => true,
                                'billing'  => true,
                            );

                            $shipping_fields = array(
                                'shipping_method' => true,
                                'shipping_total'  => true,
                                'shipping_tax'    => true,
                            );
                            foreach ($posted_data as $key => $value) {
                                if (is_callable(array($order, "set_{$key}"))) {
                                    $order->{"set_{$key}"}($value);
                                } elseif (isset($fields_prefix[current(explode('_', $key))])) {
                                    if (!isset($shipping_fields[$key])) {
                                        $order->update_meta_data('_' . $key, $value);
                                    }
                                }
                            }

                            if (isset($posted_data['billing_email'])) {
                                $order->hold_applied_coupons($posted_data['billing_email']);
                            }

                            $order->set_created_via('checkout');
                            $order->set_cart_hash($cart_data->get_cart_hash());
                            $order->set_customer_id($checkoutData->customer_id);
                            $order->set_currency(get_woocommerce_currency());
                            $order->set_prices_include_tax('yes' === get_option('woocommerce_prices_include_tax'));
                            $order->set_customer_ip_address(WC_Geolocation::get_ip_address());
                            $order->set_customer_user_agent($posted_data['customer_user_agent'] ?? '');
                            $order->set_customer_note(isset($posted_data['order_comments']) ? $posted_data['order_comments'] : '');
                            $order->set_payment_method($posted_data['payment_method']);

                            $order_vat_exempt = $cart_data->get_customer()->get_is_vat_exempt() ? 'yes' : 'no';
                            $order->add_meta_data('is_vat_exempt', $order_vat_exempt, true);
                            $order->set_shipping_total($cart_data->get_shipping_total());
                            $order->set_discount_total($cart_data->get_discount_total());
                            $order->set_discount_tax($cart_data->get_discount_tax());
                            $order->set_cart_tax($cart_data->get_cart_contents_tax() + $cart_data->get_fee_tax());
                            $order->set_shipping_tax($cart_data->get_shipping_tax());
                            $order->set_total($cart_data->get_total('edit'));

                            WC()->checkout->create_order_line_items($order, $cart_data);
                            WC()->checkout->create_order_fee_lines($order, $cart_data);

                            if (isset($session_data->chosen_shipping_methods)) {
                                WC()->checkout->create_order_shipping_lines($order, maybe_unserialize($session_data->chosen_shipping_methods), $packages);
                            }
                            if (!empty($cart_data->avatax_rates)) {
                                $rates = maybe_unserialize($checkoutData->rates);
                                foreach (array_keys($cart_data->get_cart_contents_taxes() + $cart_data->get_shipping_taxes() + $cart_data->get_fee_taxes()) as $tax_rate_id) {
                                    if ($tax_rate_id && apply_filters('woocommerce_cart_remove_taxes_zero_rate_id', 'zero-rated') !== $tax_rate_id) {
                                        $item = new WC_Order_Item_Tax();
                                        $item->set_props(
                                            array(
                                                'rate_id'            => $tax_rate_id,
                                                'tax_total'          => $cart_data->get_tax_amount($tax_rate_id),
                                                'shipping_tax_total' => $cart_data->get_shipping_tax_amount($tax_rate_id),
                                                'rate_code'          => WC_Tax::get_rate_code($tax_rate_id),
                                                'label'              => WC_Tax::get_rate_label($tax_rate_id),
                                                'compound'           => WC_Tax::is_compound($tax_rate_id),
                                                'rate_percent'       => WC_Tax::get_rate_percent_value($tax_rate_id),
                                            )
                                        );

                                        do_action('woocommerce_checkout_create_order_tax_item', $item, $tax_rate_id, $order);
                                        foreach ($cart_data->avatax_rates as $avatax_line_rates) {
                                            /** @var WC_AvaTax_API_Tax_Rate $rate */
                                            if ($rate = $avatax_line_rates[WC_Tax::get_rate_code($tax_rate_id)] ?? null) {
                                                $item->set_label($rates[WC_Tax::get_rate_code($tax_rate_id)]);
                                                break;
                                            }
                                        }
                                        $order->add_item($item);
                                    }
                                }
                            } else {
                                WC()->checkout->create_order_tax_lines($order, $cart_data);
                            }
                            WC()->checkout->create_order_coupon_lines($order, $cart_data);

                            do_action('woocommerce_checkout_create_order', $order, $posted_data);
                            $order_id = $order->save();
                            do_action('woocommerce_checkout_update_order_meta', $order_id, $posted_data);
                            do_action('woocommerce_checkout_order_created', $order);

                            $note = __("Credova Financial <br> Application Id : " . $response->applicationId . " <br> Public Id : " . $ipn . " <br>  Environment : " . $sand_text . " <br> Status : " . $response->status . "  ");
                            $order->add_order_note($note);
                            $order->update_status('processing', '', true);
                            $order->save();
                            $this->log->add("S T E P - 6");
                            $this->log->add("Order Created");
                            $this->log->add($order_id);
                            $wpdb->query(
                                $wpdb->prepare("UPDATE $table_name 
                        SET success_order_id = %s 
                        WHERE ipn = %s", $order_id, $ipn)
                            );
                            //Add Data to table
                            $this->add_credova_info($order, $response);
                            $this->log->add(" Callback End Data ");
                        } else {
                            $this->log->add("Order already found or no matching item.");
                        }
                    } elseif ($response->status == 'Approved') {
                        $this->log->add("Approved status received for traditional checkout. No action taken.");
                    } else {
                        $this->log->add("Unexpected status received: " . $response->status);
                    }
                }
            }
        }

        private function add_credova_info($order, $response)
        {
            global $wpdb;
            $table_name = $wpdb->prefix . 'credova_info';

            $wpdb->insert(
                $table_name,
                array(
                    'shop_order_id'           => $order->get_id(),
                    'transaction_date'        => $order->get_date_created()->format('Y-m-d H:i:s'),
                    'customer_name'           => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                    'customer_address'        => $order->get_billing_address_1() . ' ' . $order->get_billing_address_2(),
                    'customer_city'           => $order->get_billing_city(),
                    'customer_state'          => $order->get_billing_state(),
                    'customer_zipcode'        => substr($order->get_billing_postcode(), 0, 5),
                    'customer_email'          => $order->get_billing_email(),
                    'customer_phone'          => $order->get_billing_phone(),
                    'payment_status'          => $response->status,
                    'created_at'              => current_time('mysql'),
                    'total_inc_tax'           => $order->get_total(),
                    'woo_order_status'        => $order->get_status(),
                    'credova_public_id'       => $response->publicId,
                    'credova_lender_name'     => $response->lenderName,
                    'credova_lender_code'     => $response->lenderCode,
                    'credova_approval_amount' => $response->approvalAmount,
                    'credova_borrowed_amount' => $response->borrowedAmount,
                    'financing_partner_name'  => $response->financingPartnerName,
                    'financing_partner_code'  => $response->financingPartnerCode,
                )
            );
        }


        private function update_credova_info($order, $response)
        {
            global $wpdb;
            $table_name = $wpdb->prefix . 'credova_info';

            $wpdb->update(
                $table_name,
                array(
                    'payment_status'          => $response->status,
                    'woo_order_status'        => $order->get_status(),
                    'credova_public_id'       => $response->publicId,
                    'credova_lender_name'     => $response->lenderName,
                    'credova_lender_code'     => $response->lenderCode,
                    'credova_approval_amount' => $response->approvalAmount,
                    'credova_borrowed_amount' => $response->borrowedAmount,
                    'financing_partner_name'  => $response->financingPartnerName,
                    'financing_partner_code'  => $response->financingPartnerCode,
                ),
                array('shop_order_id' => $order->get_id())
            );

            // Log the update operation
            $this->log->add("Updated Credova info for order ID: " . $order->get_id());
        }

        public function credova_scripts_on_checkout()
        {
            define('WOOCOMMERCE_CHECKOUT', true);
            define('WOOCOMMERCE_CART', true);

            global $woocommerce;
            global $wpdb;
            $this->log->add("================================================");
            $this->log->add("S T E P - 1");

            ob_start(); // Start buffering output

            if (!WC()->session->has_session()) {
                $this->log->add("No WC Session");
                WC()->session->set_customer_session_cookie(true);
            } else {
                $this->log->add("Has WC Session");
            }

            $posted_data = WC()->checkout()->get_posted_data();

            // Update both shipping and billing to the passed billing address first if set.
            $address_fields = array(
                'first_name',
                'last_name',
                'company',
                'email',
                'phone',
                'address_1',
                'address_2',
                'city',
                'postcode',
                'state',
                'country',
            );

            array_walk($address_fields, array($this, 'set_customer_address_fields'), $posted_data);
            WC()->customer->save();

            // Update customer shipping and payment method to posted method.
            $chosen_shipping_methods = WC()->session->get('chosen_shipping_methods');

            if (is_array($posted_data['shipping_method'])) {
                foreach ($posted_data['shipping_method'] as $i => $value) {
                    $chosen_shipping_methods[$i] = $value;
                }
            }

            WC()->session->set('chosen_shipping_methods', $chosen_shipping_methods);
            WC()->session->set('chosen_payment_method', $posted_data['payment_method']);

            // Update cart totals now we have customer address.
            do_action('woocommerce_before_calculate_totals', WC()->cart);
            new WC_Cart_Totals(WC()->cart);
            do_action('woocommerce_after_calculate_totals', WC()->cart);

            $cart_data = WC()->cart;

            if (empty($posted_data['terms']) && !empty($posted_data['terms-field'])) {
                $response = array(
                    'result'   => 'failure',
                    'messages' => '<ul class="woocommerce-error">Please read and accept the terms and conditions to proceed with your order.</ul>',
                );
                wp_send_json($response);
            }

            //trying to receive checkout fields data from post
            if (count($posted_data)) {
                $order_data = array(
                    'Address'          => trim($posted_data['billing_address_1']),
                    'Address2'         => trim(isset($posted_data['billing_address_2']) ? $posted_data['billing_address_2'] : ''),
                    'Zip'              => (isset($posted_data['billing_postcode']) && $posted_data['billing_postcode']) ? trim($posted_data['billing_postcode']) : (WC()->customer->get_shipping_country() == 'IE' ? '00000' : ''),
                    'AmountBeforeFees' => WC()->cart->total,
                    'ConsumerFullName' => trim($posted_data['billing_first_name'] . ' ' . $posted_data['billing_last_name']),
                    'firstname'        => trim($posted_data['billing_first_name']),
                    'lastname'         => trim($posted_data['billing_last_name']),
                    'Email'            => trim($posted_data['billing_email']),
                    'City'             => trim($posted_data['billing_city']),
                    'State'            => trim(isset($posted_data['billing_state']) ? $posted_data['billing_state'] : ''),
                    'Country'          => trim($posted_data['billing_country']),
                    'Phone'            => trim($posted_data['billing_phone']),
                );

                // this shouldn`t happen, but in case of: no post data contained (user flushed cookie?)
                // create empty address data, so user will be able to fill it on Splitit popup
            } else {
                $order_data = array(
                    'Address'          => '',
                    'Address2'         => '',
                    'Zip'              => '',
                    'AmountBeforeFees' => WC()->cart->total,
                );
            }

            $api_username       = $this->api_username;
            $api_password       = $this->api_password;
            $testmode           = $this->testmode;

            $items = $woocommerce->cart->get_cart();
            foreach ($items as $item => $values) {
                $_product = apply_filters('woocommerce_cart_item_product', $values['data'], $values, $item);
                $product_price = $values['line_subtotal'];

                $_product             = wc_get_product($values['data']->get_id());
                $_product_total_price = $product_price;
                $description          = apply_filters('woocommerce_cart_item_name', $_product->get_name(), $values, $item) . "<br/>" . wc_get_formatted_cart_item_data($values);
                $description = str_replace("&times;", "x", $description);
                $cart_products[] = [
                    'id'          => $values['product_id'],
                    'description' => $description,
                    'quantity'    => $values['quantity'],
                    'value'       => $_product_total_price,
                ];
            }



            $shipping_rate = WC()->cart->get_shipping_total();
            if (!empty($shipping_rate)  &&  $shipping_rate > 0) {
                $shipping_price  = array("id" => "shipping_rate", "description" => "shipping_rate", "quantity" => "1", "value" => $shipping_rate);
                $cart_products[] = $shipping_price;
            }

            $taxes_total_amount =  WC()->cart->get_cart_contents_tax() + WC()->cart->get_fee_tax() + WC()->cart->get_shipping_tax() + WC()->cart->get_discount_tax();
            if (!empty($taxes_total_amount) &&  $taxes_total_amount > 0) {
                $tax             = array("id" => "tax", "description" => "tax", "quantity" => "1", "value" => $taxes_total_amount);
                $cart_products[] = $tax;
            }

            $coupon_amount = WC()->cart->get_discount_total();
            if (!empty($coupon_amount) &&  $coupon_amount > 0) {
                $coupon_amount_content = array("id" => "coupon", "description" => "coupon", "quantity" => "1", "value" => -$coupon_amount);
                $cart_products[]       = $coupon_amount_content;
            }

            $fee_amount = WC()->cart->get_fee_total();
            if (!empty($fee_amount) &&  $fee_amount > 0) {
                $fee_amount_content = array("id" => "fee", "description" => "fee", "quantity" => "1", "value" => $fee_amount);
                $cart_products[]       = $fee_amount_content;
            }

            $giftcards = WC()->session->get('_wc_gc_giftcards');
            if (!empty($giftcards)) {
                $gift_card_amount = $giftcards[0]['amount'];
                $gift_card_amount_content = array("id" => "giftcard", "description" => "giftcard", "quantity" => "1", "value" => -$gift_card_amount);
                $cart_products[]       = $gift_card_amount_content;
            }

            $sant_cart_products = array();
            $sant_i             = 0;
            foreach ($cart_products as $cartproducts) {
                $sant_cart_products[$sant_i]['id']          = sanitize_text_field($cartproducts['id']);
                $sant_cart_products[$sant_i]['description'] = substr(sanitize_text_field($cartproducts['description']), 0, 256);
                $sant_cart_products[$sant_i]['quantity']    = sanitize_text_field($cartproducts['quantity']);
                $sant_cart_products[$sant_i]['value']       = sanitize_text_field($cartproducts['value']);
                $sant_i++;
            }

            $billing_first_name = sanitize_text_field($order_data['firstname']);
            $billing_last_name  = sanitize_text_field($order_data['lastname']);
            $billing_email      = sanitize_text_field($order_data['Email']);
            $street             = sanitize_text_field($order_data['Address']);
            $city               = sanitize_text_field($order_data['City']);
            $regionId           = sanitize_text_field($order_data['State']);
            $postcode           = sanitize_text_field($order_data['Zip']);
            $telephone          = sanitize_text_field($order_data['Phone']);
            $firstName          = $billing_first_name;
            $lastName           = $billing_last_name;

            $pubid = (WC()->session->get('credova_checkout_session_id_data')) ? WC()->session->get('credova_checkout_session_id_data') : "";

            $telephone = str_replace(' ', '-', $telephone);
            $telephone = preg_replace('/[^A-Za-z0-9-]/', '', $telephone);

            if (preg_match('/(\d{3})(\d{3})(\d{4})$/', $telephone, $matches)) {
                $telephone = $matches[1] . '-' . $matches[2] . '-' . $matches[3];
            } else {
                $telephone = $telephone;
            }

            if (substr_count($telephone, '-') == 3) {
                $telephone = substr($telephone, strpos($telephone, "-") + 1);
            }

            // Extract first 5 digits of ZIP code
            $postcode = preg_replace('/[^0-9]/', '', $postcode); // Remove non-numeric characters
            $postcode = substr($postcode, 0, 5); // Take only the first 5 digits


            $redirectkey = time();

            $cancelUrl = wc_get_checkout_url();
            $redirectConfirmUrl = add_query_arg(
                array(
                    'wc-api' => 'credova_payment_success',
                    'key' => $redirectkey,
                ),
                get_permalink(wc_get_page_id('checkout'))
            );

            if (preg_match("/^[0-9]{3}-[0-9]{3}-[0-9]{4}$/", $telephone)) {
                // if (preg_match($mobileregex, $telephone) === 1) {
                $application = [
                    "publicId" => $pubid,
                    "storeCode"                => $api_username,
                    "firstName"                => $firstName,
                    "middleInitial"            => "",
                    "lastName"                 => $lastName,
                    "dateOfBirth"              => "",
                    "mobilePhone"              => $telephone,
                    "email"                    => $billing_email,
                    "address"                  => array(
                        "street"  => $street,
                        "zipCode" => $postcode,
                        "city"    => $city,
                        "state"   => $regionId,
                    ),
                    "products"                 => $sant_cart_products,
                    "redirectUrl"              => $redirectConfirmUrl,
                    "cancelUrl"                => $cancelUrl,
                ];

                $this->log->add("application cart request");
                $this->log->add($sant_cart_products);

                $this->log->add("Public_ID");
                $this->log->add($pubid);

                $client = new CredovaClient($api_username, $api_password, $testmode);
                $resp = $client->apply($application, BASE_URL . "/?wc-api=WC_Credova_Gateway", $cart_data, $posted_data, $redirectkey);
                $this->log->add("S T E P - 3");
                $this->log->add("application response");
                $this->log->add($resp);

                if (strpos($resp[1], "Welcome") !== false) {

                    try {
                        $ipn        = $resp[0];
                        if ($ipn != "" && $posted_data != "") {
                            $this->log->add("S T E P - 4");
                            $confirmation_url = add_query_arg(
                                array(
                                    'wc-api' => 'credova_payment_success',
                                    'key' => $redirectkey,
                                ),
                                get_permalink(wc_get_page_id('checkout'))
                            );

                            $cancel_url = wc_get_checkout_url();

                            $response = array(
                                'result'     => 'success',
                                'messages'   => $ipn,
                                'confirmURL' => $confirmation_url,
                                'cancelURL'  => $cancel_url,
                                'redirect'   => $resp[1],
                            );
                            $this->log->add("success");
                        } else {
                            $response = array(
                                'result'   => 'failure',
                                'messages' => '<ul class="woocommerce-error">Request Invalid.</ul>',
                            );
                            $this->log->add("Request Invalid.");
                        }
                    } catch (Exception $e) {
                        if ($this->log) {
                            $this->log->info(__FILE__, __LINE__, __METHOD__);
                            $this->log->add($e);
                        }
                        $response = array(
                            'result'   => 'failure',
                            'messages' => $e->getMessage(),
                        );
                        wp_send_json($response);
                        return;
                    }
                } else {
                    $response = array(
                        'result'   => 'failure',
                        'messages' => '<ul class="woocommerce-error"> ' . $resp . ' </ul>',
                    );
                    wp_send_json($response);
                    return;
                }
            } else {
                $response = array(
                    'result'   => 'failure',
                    'messages' => '<ul class="woocommerce-error">Please check your phone number.</ul>',
                );
                wp_send_json($response);
                return;
            }
            ob_clean(); // Clean any output in the buffer
            // At the end of the method:
            $response = array(
                'result' => 'success',
                'redirect' => $resp[1],
                'messages' => $ipn,
            );
            wp_send_json($response);
        }

        public function get_post_id_by_meta_value($value)
        {
            global $wpdb;
            $meta = $wpdb->get_results("SELECT * FROM `" . $wpdb->postmeta . "` WHERE meta_key='" . esc_sql("public_id") . "' AND meta_value='" . esc_sql($value) . "'");
            return $meta;
        }

        public function credova_payment_success($flag = null)
        {
            $this->log->add("Response Controller Start - credova_payment_success");
            // echo site_url("checkout/order-received/"); die();
            $redirectkey = $_GET['key'];
            global $wpdb;
            global $woocommerce;
            $table_name = $wpdb->prefix . 'credova_checkout';
            $ipn = (WC()->session->get('credova_checkout_session_id_data')) ? WC()->session->get('credova_checkout_session_id_data') : false;
            $item       = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE ipn = %s", $ipn), ARRAY_A);

            if ($item !== null && isset($item['success_order_id'])) {
                $orderId = $item['success_order_id'];
            }
            if (isset($orderId)) {
                $this->log->add("Order Found " . $orderId);
                $last_order     = new WC_Order($orderId);
                $last_order_key = $last_order->order_key;
                WC()->session->__unset('credova_checkout_session_id_data');
                $woocommerce->cart->empty_cart();
                $this->log->add("E N D");
                wp_redirect(site_url("checkout/order-received/" . $orderId . "/?key=" . $last_order_key));
            } else {
                $item       = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE ipn = %s", $ipn), ARRAY_A);
                if ($item !== null && isset($item['success_order_id'])) {
                    $orderId = $item['success_order_id'];
                }
                if (isset($orderId)) {
                    $this->log->add("Order Found " . $orderId);
                    $last_order     = new WC_Order($orderId);
                    $last_order_key = $last_order->order_key;
                    WC()->session->__unset('credova_checkout_session_id_data');
                    $woocommerce->cart->empty_cart();
                    $this->log->add("E N D");
                    wp_redirect(site_url("checkout/order-received/" . $orderId . "/?key=" . $last_order_key));
                } else {
                    $item       = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE redirectkey = %s", $redirectkey), ARRAY_A);
                    if ($item !== null && isset($item['success_order_id'])) {
                        $orderId = $item['success_order_id'];
                    }
                    if (isset($orderId)) {
                        $this->log->add("Order Found " . $orderId);
                        $last_order     = new WC_Order($orderId);
                        $last_order_key = $last_order->order_key;
                        WC()->session->__unset('credova_checkout_session_id_data');
                        $woocommerce->cart->empty_cart();
                        $this->log->add("E N D");
                        wp_redirect(site_url("checkout/order-received/" . $orderId . "/?key=" . $last_order_key));
                    } else {
                        $this->log->add("Order Not Found " . $orderId);
                        wp_redirect(site_url("cart"));
                    }
                }
            }
            Credova_Helper::exit_safely();
        }

        public function credova_payment_block_success($flag = null)
        {
            global $wpdb;
            global $woocommerce;
            $table_name = $wpdb->prefix . 'credova_info';

            $this->log->add("Response Controller Start - credova_payment_block_success");

            $is_callback = ($_SERVER['REQUEST_METHOD'] === 'POST');
            $is_redirect = isset($_GET['block_checkout']) && $_GET['block_checkout'] === '1';

            if ($is_callback) {
                $this->handle_callback();
            } elseif ($is_redirect) {
                $this->handle_redirect();
            } else {
                $this->log->add("Invalid request type");
                wp_redirect(site_url("cart"));
            }

            Credova_Helper::exit_safely();
        }

        private function handle_callback()
        {
            $this->log->add("Handling Callback");
            $post_data = file_get_contents('php://input');
            $this->log->add("Received POST data: " . $post_data);

            // Process the POST data
            $decoded_data = json_decode($post_data, true);
            if ($decoded_data) {
                // Update order status based on callback data
                $order_id = $decoded_data['order_id'] ?? null;
                $status = $decoded_data['status'] ?? null;

                if ($order_id && $status) {
                    $order = wc_get_order($order_id);
                    if ($order) {
                        // Update order status based on Credova status
                        // You may need to adjust this logic based on Credova's status codes
                        if ($status === 'approved' || $status === 'signed') {
                            $order->update_status('processing', __('Credova payment approved', 'woocommerce'));
                        } elseif ($status === 'declined') {
                            $order->update_status('failed', __('Credova payment declined', 'woocommerce'));
                        }
                        $this->log->add("Order $order_id updated with status: $status");
                    }
                }
            }

            // Respond to Credova
            http_response_code(200);
            echo json_encode(['status' => 'success']);
        }

        private function handle_redirect()
        {
            $this->log->add("Handling Redirect");
            $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
            $order_key = isset($_GET['order_key']) ? wc_clean($_GET['order_key']) : '';

            $this->log->add("Order ID: " . $order_id . ", Order Key: " . $order_key);
            $order = wc_get_order($order_id);

            if ($order && $order->get_order_key() === $order_key) {
                $this->log->add("Order Found " . $order_id);

                // Check payment status in wp_credova_info table
                global $wpdb;
                $table_name = $wpdb->prefix . 'credova_info';
                $payment_status = $wpdb->get_var($wpdb->prepare(
                    "SELECT payment_status FROM $table_name WHERE shop_order_id = %d",
                    $order_id
                ));

                if ($order->get_status() === 'processing' || in_array($payment_status, ['Approved', 'Signed'])) {
                    $this->log->add("Order status is processing or payment status is Approved/Signed. Redirecting to order received page.");
                    wp_redirect(site_url("checkout/order-received/" . $order_id . "/?key=" . $order_key));
                } else {
                    $this->log->add("Order status is not processing and payment status is not Approved or Signed. Current status: " . $order->get_status() . ", Payment status: " . $payment_status);
                    $order->update_status('cancelled', __('Credova payment not completed', 'woocommerce'));
                    $this->log->add("Order cancelled due to incomplete Credova payment");
                    wp_redirect(site_url("cart"));
                }
            } else {
                $this->log->add("Invalid Order Key or Order Not Found");
                wp_redirect(site_url("cart"));
            }
        }

        public function credova_payment_cancel($flag = null)
        {
            $this->log->add("Response Controller Start - credova_payment_cancel");

            $is_block_checkout = isset($_GET['block_checkout']) && $_GET['block_checkout'] === '1';
            $this->log->add("Is Block Checkout: " . ($is_block_checkout ? 'Yes' : 'No'));

            if ($is_block_checkout) {
                $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
                $order_key = isset($_GET['order_key']) ? wc_clean($_GET['order_key']) : '';

                $this->log->add("Order ID: " . $order_id . ", Order Key: " . $order_key);
                $order = wc_get_order($order_id);
                if ($order && $order->get_order_key() === $order_key) {
                    $this->log->add("Order Found " . $order_id);
                    $this->log->add("E N D");

                    if ($order->get_status() !== 'processing') {
                        $this->log->add("Order status is not processing. Current status: " . $order->get_status());
                        $order->update_status('cancelled', __('Credova payment not completed', 'woocommerce'));
                        $this->log->add("Order cancelled due to incomplete Credova payment");

                        // Reload cart items
                        $this->reload_cart_items($order);

                        // Add error notice
                        wc_add_notice(__('Your order has been cancelled due to incomplete payment. The items have been returned to your cart.', 'woocommerce'), 'error');

                        $this->log->add("E N D");
                        wp_redirect(site_url("cart"));
                    } else {
                        $this->log->add("Order status is processing. Redirecting to order received page.");
                        $this->log->add("E N D");
                        wp_redirect(site_url("checkout/order-received/" . $order_id . "/?key=" . $order_key));
                    }
                } else {
                    $this->log->add("Invalid Order Key or Order Not Found");
                    wp_redirect(site_url("cart"));
                }
            }
            Credova_Helper::exit_safely();
        }

        private function reload_cart_items($order)
        {
            // Clear the current cart
            WC()->cart->empty_cart();

            // Get the items from the order and add them back to the cart
            foreach ($order->get_items() as $item) {
                $product_id = $item->get_product_id();
                $quantity = $item->get_quantity();
                $variation_id = $item->get_variation_id();

                // Get variation attributes
                $variation = array();
                if ($variation_id) {
                    $product_variation = wc_get_product($variation_id);
                    if ($product_variation) {
                        $variation = $product_variation->get_variation_attributes();
                    }
                }

                // Add to cart
                WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation);
            }
        }

        public function process_admin_options()
        {
            $post_data = $this->get_post_data();
            if (array_key_exists('woocommerce_credova_api_username', $post_data) && array_key_exists('woocommerce_credova_api_password', $post_data)) {
                /*Save*/
                $id = 'credova';
                $key = 'woocommerce_credova';
                $enabled = ((array_key_exists($key . '_enabled', $post_data)) && $post_data[$key . '_enabled'] == 1) ? "yes" : 'no';
                $testmode = ((array_key_exists($key . '_testmode', $post_data)) && $post_data[$key . '_testmode'] == 1) ? "yes" : 'no';
                $aslowaslist = ((array_key_exists($key . '_aslowaslist', $post_data)) && $post_data[$key . '_aslowaslist'] == 1) ? "yes" : 'no';
                $aslowasmini = ((array_key_exists($key . '_aslowasmini', $post_data)) && $post_data[$key . '_aslowasmini'] == 1) ? "yes" : 'no';
                $aslowascart = ((array_key_exists($key . '_aslowascart', $post_data)) && $post_data[$key . '_aslowascart'] == 1) ? "yes" : 'no';
                $ala_type = ((array_key_exists($key . '_ala_type', $post_data)) && $post_data[$key . '_ala_type'] == 1) ? "yes" : 'no';
                $hide_brand = ((array_key_exists($key . '_hide_brand', $post_data)) && $post_data[$key . '_hide_brand'] == 1) ? "yes" : 'no';

                // print_r($post_data);
                $save = [
                    "enabled"                       => $enabled,
                    "title"                         => $post_data[$key . '_title'],
                    "min_finance_amount"            => $post_data[$key . '_min_finance_amount'],
                    "max_finance_amount"            => $post_data[$key . '_max_finance_amount'],
                    "aslowaslist"                   => $aslowaslist,
                    "aslowasmini"                   => $aslowasmini,
                    "aslowascart"                   => $aslowascart,
                    "testmode"                      => $testmode,
                    "ala_type"                      => $ala_type,
                    "hide_brand"                    => $hide_brand,
                    "api_username"                  => $post_data[$key . '_api_username'],
                    "api_password"                  => $post_data[$key . '_api_password'],
                    "flow_type"                     => $post_data[$key . '_flow_type'],
                    "checkout_mode"                 => $post_data[$key . '_checkout_mode'],
                    "popup_type"                    => $post_data[$key . '_popup_type'],
                    "aslowasproduct_hook"           => $post_data[$key . '_aslowasproduct_hook'],
                    "aslowasproduct_priority"       => $post_data[$key . '_aslowasproduct_priority'],

                ];
                update_option($this->get_option_key(), apply_filters('woocommerce_settings_api_sanitized_fields_' . $id, $save), 'yes');
                $username = $post_data['woocommerce_credova_api_username'];
                $password = $post_data['woocommerce_credova_api_password'];
                $testmode = $post_data['woocommerce_credova_testmode'];
                $client = new CredovaClient($username, $password, $testmode);
                $generate_token = $client->generate_token($username, $password);
                if ((isset($generate_token)) && $generate_token['status_code'] == "400") {
                    if (!isset(Credova_Helper::$error)) {
                        Credova_Helper::$error = (!empty($generate_token['json'])) ? $generate_token['json']['errors'][0] : 'Username or password is incorrect.';
                        return true;
                    }
                }
                return false;
            }
        }
    }
}

add_action('wp_ajax_credova_as_low_as', 'credova_as_low_as');
add_action('wp_ajax_nopriv_credova_as_low_as', 'credova_as_low_as');
function credova_as_low_as()
{

    $credova_details = get_option('woocommerce_credova_settings');

    $testmode   = ($credova_details['testmode'] == 'yes') ? 1 : 0;
    $username   = $credova_details['api_username'];
    $password   = $credova_details['api_password'];
    $finalprice = sanitize_text_field($_POST['final_price']);

    $respp  = array();
    $client = new CredovaClient($username, $password, $testmode);
    $client->authenticate();
    try {
        $resp = $client->get_lowest_payment($finalprice);
    } catch (CredovaClientException $e) {
        $resp = $e->getMessage();
    }
    $body = json_encode($resp);
    echo $body;
    die;
}

add_action("wp_ajax_credova_list_table", "credova_list_table");
add_action("wp_ajax_nopriv_credova_list_table", "credova_list_table");
function credova_list_table()
{
    $result = array();
    $args   = array(
        'post_type' => 'product',
    );
    $loop = new WP_Query($args);
    while ($loop->have_posts()) : $loop->the_post();
        global $product;
        $credova_product_check = wc_get_product(get_the_ID());
        $credoa_values_check   = $credova_product_check->get_meta('_credova_product_endis_new');
        if ($credoa_values_check == '0') {
            $result[] = 'post-' . get_the_ID();
        }
    endwhile;
    $result = json_encode($result);
    echo $result;
    die;
}
// Dispaly credova minimum monthly payment and on the mini cart.

add_action('woocommerce_after_mini_cart', 'credova_on_minicart');
function credova_on_minicart()
{

    $credova_options = get_option('woocommerce_credova_settings');

    $aslowasmini = '';
    if (isset($credova_options['aslowasmini'])) {
        $aslowasmini   = ($credova_options['aslowasmini'] == 'yes') ? 1 : 0;
    }

    $username   = $credova_options['api_username'];
    $password   = $credova_options['api_password'];

    if ($aslowasmini == 1 && WC()->cart->total > 0) {
        $total = sanitize_text_field(WC()->cart->total);
        $items_count = WC()->cart->total;

        $credova_min_amount = $credova_options['min_finance_amount'] ?? '';
        $credova_max_amount = $credova_options['max_finance_amount'] ?? '';
        $credova_ala_type   = $credova_options['ala_type'] ?? '';
        $envMode = $credova_details['testmode'] ?? '';
        if ($envMode == 0) {
            $envMode = 'Production';
        } else {
            $envMode = 'Sandbox';
        }
        if (($total >= $credova_min_amount) && ($total <= $credova_max_amount)) {
            if ($credova_ala_type == "yes") {
                $credova_white = 'credova_white';
            } else {
                $credova_white = '';
            }
            echo '<p style="text-align:center;" class="credova_on_mini_cart" data-amount="' . $total . '" data-type="text" id = "' . $credova_white . '" ></p>';
            echo '<script>setTimeout(showCred,2000);function showCred(){CRDV.plugin.inject("credova_on_mini_cart")}</script>';
        }
    }
}

add_filter('wc_avatax_checkout_origin_address', 'filter_woocommercedtax', 10, 5);
function filter_woocommercedtax($origin_address, $cart)
{

    $totals_tax = 0;
    if (count(WC()->cart->get_tax_totals()) > 0) {
        foreach (WC()->cart->get_tax_totals() as $code => $tax) {
            $totals_tax += $tax->amount;
        }
    }
    WC()->session->set('wava_taxes', $totals_tax);
    return $origin_address;
}

// Dispaly credova minimum monthly payment and on the cart page.
add_action('woocommerce_after_cart_totals', 'wp_credova_cartpage_ALA');
function wp_credova_cartpage_ALA()
{

    $credova_options    = get_option('woocommerce_credova_settings');
    $aslowascart   = ($credova_options['aslowascart'] == 'yes') ? 1 : 0;

    // Condition if enable for cart page and cart has item

    if ($aslowascart == 1 && sizeof(WC()->cart->get_cart()) > 0 && WC()->cart->total > 0) {

        $total = WC()->cart->total;

        $credova_min_amount = $credova_options['min_finance_amount'];
        $credova_max_amount = $credova_options['max_finance_amount'];
        $credova_ala_type   = $credova_options['ala_type'];

        if (($total >= $credova_min_amount) && ($total <= $credova_max_amount)) {

            if ($credova_ala_type == "yes") {
                $credova_white = 'credova_white';
            } else {
                $credova_white = '';
            }
            $str = '<p style="text-align:center;" class="credova_on_cartpage" data-amount="' . $total . '" data-type="text" id = "' . $credova_white . '" ></p>';
            $str = $str . '<script>setTimeout(showCredCartpage,2000);function showCredCartpage(){CRDV.plugin.inject("credova_on_cartpage")}</script>';
            echo $str;
        }
    }
}

/**
 * Gets the monthly lowest payment option, financed period, and early buyout options for a specific store
 * @param integer $total cart ammount
 * @return credova minimum monthly payment
 */
function get_credova_aslowas($total)
{

    $credova_details = get_option('woocommerce_credova_settings');
    $testmode   = ($credova_details['testmode'] == 'yes') ? 1 : 0;
    $username   = $credova_details['api_username'];
    $password   = $credova_details['api_password'];
    $finalprice = sanitize_text_field($total);

    $respp  = array();
    $client = new CredovaClient($username, $password, $testmode);
    $auth = $client->authenticateo();
    if ($auth) {
        $resp = $client->get_lowest_payment_one($finalprice);
        return $resp;
    }
}

// Add WooCommerce Blocks compatibility
add_action('before_woocommerce_init', function () {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
});

// Register the Credova payment method with WooCommerce Blocks
add_action('woocommerce_blocks_loaded', function () {
    if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        return;
    }

    require_once plugin_dir_path(__FILE__) . '/class-wc-credova-payments-blocks.php';
    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
            $payment_method_registry->register(new WC_Credova_Payments_Blocks());
        }
    );
});
