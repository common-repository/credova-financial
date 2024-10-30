<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_Credova_Payments_Blocks extends AbstractPaymentMethodType
{
    protected $name = 'credova';
    private $gateway;

    public function initialize()
    {
        $this->settings = get_option('woocommerce_credova_settings', []);
        $this->gateway = new WC_Credova_Gateway(); // Ensure this class exists
    }

    public function is_active()
    {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles()
    {

        $handles = [];

        // Register and enqueue the payment script
        $payment_handle = 'wc-credova-payments-blocks';
        $payment_asset_file = plugin_dir_path(__FILE__) . 'block/credova-payment.php';
        $payment_asset_data = file_exists($payment_asset_file) ? require($payment_asset_file) : array('dependencies' => array('wp-blocks', 'wp-element', 'wp-components', 'wc-blocks-registry'), 'version' => '1.0.0');

        wp_register_script(
            $payment_handle,
            plugin_dir_url(__FILE__) . 'block/credova-payment.js',
            $payment_asset_data['dependencies'],
            $payment_asset_data['version'],
            true
        );

        // Register and enqueue the widget script
        $widget_handle = 'wc-credova-widget';
        $widget_asset_file = plugin_dir_path(__FILE__) . 'block/credova-widget.php';
        $widget_asset_data = file_exists($widget_asset_file) ? require($widget_asset_file) : array('dependencies' => array('wp-polyfill', 'jquery'), 'version' => '1.0.0');

        wp_register_script(
            $widget_handle,
            plugin_dir_url(__FILE__) . 'block/credova-widget.js',
            $widget_asset_data['dependencies'],
            $widget_asset_data['version'],
            true
        );

        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations($payment_handle, 'credova-financial');
            wp_set_script_translations($widget_handle, 'credova-financial');
        }

        $handles[] = $payment_handle;
        $handles[] = $widget_handle;

        return $handles;
    }

    public function get_payment_method_data()
    {
        return [
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'supports' => $this->get_supported_features(),
            'min_amount' => floatval($this->settings['min_finance_amount'] ?? 0.01),
            'max_amount' => floatval($this->settings['max_finance_amount'] ?? 10000),
            'testmode' => isset($this->settings['testmode']) && 'yes' === $this->settings['testmode'],
            'api_username' => $this->settings['api_username'] ?? '',
            'flow_type' => $this->settings['flow_type'] ?? 'post',
            'popup_type' => $this->settings['popup_type'] ?? 'popup',
            'placeOrderButtonLabel' => __('Continue with Credova', 'woocommerce-gateway-credova'),
            'cart_total' => WC()->cart ? WC()->cart->get_total('') : 0
        ];
    }

    public function get_supported_features()
    {
        return $this->gateway->supports;
    }
}
