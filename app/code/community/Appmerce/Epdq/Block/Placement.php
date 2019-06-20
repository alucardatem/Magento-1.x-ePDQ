<?php
/**
 * Appmerce - Applications for Ecommerce
 * http://www.appmerce.com
 *
 * @extension   Barclaycard ePDQ
 * @type        Payment method
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category    Magento
 * @package     Appmerce_Epdq
 * @copyright   Copyright (c) 2011-2014 Appmerce (http://www.appmerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Appmerce_Epdq_Block_Placement extends Mage_Core_Block_Template
{
    public function __construct()
    {
    }

    /**
     * Return checkout session
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Return payment API model
     *
     * @return Appmerce_Epdq_Model_Api_Hosted
     */
    protected function getApi()
    {
        return Mage::getSingleton('epdq/api');
    }

    /**
     * Return order instance by lastRealOrderId
     *
     * @return Mage_Sales_Model_Order
     */
    protected function _getOrder()
    {
        if ($this->getOrder()) {
            $order = $this->getOrder();
        }
        elseif ($this->getCheckout()->getLastRealOrderId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($this->getCheckout()->getLastRealOrderId());
        }

        return $order;
    }

    /**
     * Return placement form fields
     *
     * @return array
     */
    public function getFormData()
    {
        $order = $this->_getOrder();
        return $order->getPayment()->getMethodInstance()->getFormFields($this->_getOrder());
    }

    /**
     * Return gateway path from admin settings
     *
     * @return string
     */
    public function getFormAction()
    {
        $order = $this->_getOrder();
        return $order->getPayment()->getMethodInstance()->getGatewayUrl('redirect');
    }

}
