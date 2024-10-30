<?php
/**
 * Credova_Order class
 *
 * @class       Credova_Order
 * @version     0.0.1
 * @package     Credova_Order/includes
 * @category    Order
 * @author      By Credova
 */
class Credova_Checkout extends WC_Checkout
{
    public $log;
    
    public function __construct()
    {
        $this->log = new Credova_Log();
    }
}
