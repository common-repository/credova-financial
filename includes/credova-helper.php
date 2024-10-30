<?php
/**
 * Credova_Helper class
 *
 * @class       Credova_Helper
 * @version     0.0.1
 * @package     Credova_Helper/Includes
 * @category    Helper
 * @author      By Credova
 */

class Credova_Helper
{

    private static $exitSafelyFlag   = false;
    protected static $allowSetCookie = true;
    public static $error;

    /**
     * Error formatting function
     *
     * @param $error
     * @return string
     */
    public static function format_error($error)
    {
        return 'Error ' . $error['code'] . ': ' . $error['message'];
    }

    /**
     * Sanitize redirect url string
     */
    public static function sanitize_redirect_url($url)
    {
        if ($url != '') {
            $checkout_url = explode('checkout', wc_get_checkout_url()); //using this way to get index.php if needed
            $base_url     = rtrim($checkout_url[0], '/');
            if (strpos($url, '.') !== false) {
                //url contain file extension, like .php/.html etc.
                $url = strip_tags(trim($url, '/'));
            } else {
                $url = strip_tags(trim($url, '/')) . '/';
            }

            return $base_url . '/' . $url;
        }
        return false;
    }

    /**
     * @param bool $flag
     */
    public static function set_exit_safely_flag(bool $flag)
    {
        self::$exitSafelyFlag = $flag;
    }

    /**
     * @throws Exception
     */
    public static function exit_safely()
    {
        if (self::$exitSafelyFlag) {
            throw new Exception('Exit');
        }

        exit;
    }

    /**
     * @param $name
     * @param $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httponly
     */
    public static function setCookie($name, $value, $expire = 0, $path = '', $domain = '', $secure = false, $httponly = false)
    {
        if (self::$allowSetCookie) {
            setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
        }
    }

    /**
     * @param bool $flag
     */
    public static function setCookieFlag(bool $flag)
    {
        self::$allowSetCookie = $flag;
    }
    public static function add_generate_token_notice()
    {
        if( isset( self::$error ) ){
            echo '<div class="notice notice-error"><p>';
            echo sprintf(
                self::$error,
                admin_url( 'admin.php?page=wc-settings&tab=checkout&section=credova' )
            );
            echo '</p></div>';
            return;
        }
    }
}
