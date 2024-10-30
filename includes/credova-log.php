<?php
/**
 * Credova_Log class
 *
 * @class         Credova_Log
 * @version        0.0.1
 * @package        Credova_Log/Includes
 * @category    Logs
 * @author         By Credova
 */
class Credova_Log
{

    /* The domain handler used to name the log */
    private $_domain = 'credova';

    /* The WC_Logger instance */
    private $_logger;

    /**
     * __construct.
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        $this->_logger = new WC_Logger();
    }

    /**
     * add function.
     *
     * Uses the build in logging method in WooCommerce.
     * Logs are available inside the System status tab
     *
     * @access public
     * @param  string|array|object
     * @return void
     */
    public function add($param)
    {
        if (is_array($param)) {
            $param = print_r($param, true);
        } else if (is_object($param)) {
            $param = print_r(get_object_vars($param), true);
        }

        $this->_logger->add($this->_domain, $param);
    }

    /**
     * clear function.
     *
     * Clears the entire log file
     *
     * @access public
     * @return void
     */
    public function clear()
    {
        $this->_logger->clear($this->_domain);
    }

    /**
     * separator function.
     *
     * Inserts a separation line for better overview in the logs.
     *
     * @access public
     * @return void
     */
    public function info($file, $line, $method)
    {
        $this->add('--- File: ' . $file . ' (' . $line . ') ---');
        $this->add('--- Method: ' . $method . ' ---');
    }

    /**
     * separator function.
     *
     * Inserts a separation line for better overview in the logs.
     *
     * @access public
     * @return void
     */
    public function separator()
    {
        $this->add('----------------------------------');
    }

    /**
     * get_domain function.
     *
     * Returns the log text domain
     *
     * @access public
     * @return string
     */
    public function get_domain()
    {
        return $this->_domain;
    }
}
