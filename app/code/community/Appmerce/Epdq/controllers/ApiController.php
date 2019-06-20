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

class Appmerce_Epdq_ApiController extends Appmerce_Epdq_Controller_Common
{
    /**
     * Render placement form and set New Order Status [multi-method]
     *
     * @see epdq/api/placement
     */
    public function placementAction()
    {
        $this->saveCheckoutSession();

        if ($this->getApi()->getConfig()->getServiceConfigData('debug_flag')) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($this->getCheckout()->getLastRealOrderId());
            if ($order->getId()) {
                $url = $this->getRequest()->getPathInfo();
                $data = print_r($this->getApi()->getFormFields($order), true);
                Mage::getModel('epdq/api_debug')->setDir('out')->setUrl($url)->setData('data', $data)->save();
            }
        }

        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Payment action; show inline ePDQ payment form in Magento theme
     *
     * @see epdq/api/payment
     */
    public function paymentAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Accepted action
     *
     * @see epdq/api/accept
     */
    public function acceptAction()
    {
        $this->getProcess()->done();
        $this->_redirect('checkout/onepage/success', array('_secure' => true));
    }

    /**
     * Exception action
     *
     * @see epdq/api/exception
     */
    public function exceptionAction()
    {
        $this->getProcess()->repeat();
        $this->_redirect('checkout/cart', array('_secure' => true));
    }

    /**
     * Decline action
     *
     * @see epdq/api/decline
     */
    public function declineAction()
    {
        $this->getProcess()->repeat();
        $this->_redirect('checkout/cart', array('_secure' => true));
    }

    /**
     * Cancel action
     *
     * @see epdq/api/cancel
     */
    public function cancelAction()
    {
        $this->getProcess()->repeat();
        $this->_redirect('checkout/cart', array('_secure' => true));
    }

}
