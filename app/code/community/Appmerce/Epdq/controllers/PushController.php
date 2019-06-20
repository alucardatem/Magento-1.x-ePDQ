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

class Appmerce_Epdq_PushController extends Appmerce_Epdq_Controller_Common
{
    /**
     * Validate incoming SHA Signature
     *
     * @param $order Mage_Sales_Model_Order
     * @return boolean
     */
    protected function validateePDQData($order)
    {
        $storeId = $order->getStoreId();
        $params = $this->getRequest()->getParams();
        $secretKey = $this->getApi()->getConfig()->getServiceConfigData('sha_out', $storeId);

        // Get secret set
        $signatureMethod = $this->getApi()->getConfig()->getServiceConfigData('signature_method');
        switch ($signatureMethod) {
            case Appmerce_Epdq_Model_Config::SIGNATURE_V1 :
                $secretSet = Mage::helper('epdq')->getShaOutSetV1($params, $secretKey);
                break;

            case Appmerce_Epdq_Model_Config::SIGNATURE_V2 :
                $secretSet = Mage::helper('epdq')->getShaOutSetV2($params, $secretKey);
                break;

            default :
                $secretSet = '';
        }

        if (Mage::helper('epdq')->shaCryptValidation($secretSet, $params['SHASIGN']) != true) {
            return false;
        }

        return true;
    }

    /**
     * ePDQ postback action; result shown to user upon return
     *
     * Since ePDQ only has 1 postback URL, we make sure the user is
     * redirected to the correct store.
     *
     * @see epdq/push/accept
     */
    public function acceptAction()
    {
        $params = $this->getRequest()->getParams();
        $this->saveDebugIn($params);

        if (isset($params['orderID'])) {
            $orderId = $params['orderID'];
            $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
            if ($this->validateePDQData($order)) {
                $paymentStatus = $params['STATUS'];
                switch ($paymentStatus) {
                    case Appmerce_Epdq_Model_Api::STATUS_PAYMENT_REQUESTED :
                    case Appmerce_Epdq_Model_Api::STATUS_BY_MERCHANT :
                        $note = Mage::helper('epdq')->__('Payment Status: Success.');
                        $this->getProcess()->success($order, $note, $params['PAYID'], $params['STATUS']);
                        break;

                    case Appmerce_Epdq_Model_Api::STATUS_AUTHORIZED :
                    case Appmerce_Epdq_Model_Api::STATUS_AUTH_WAITING :
                    case Appmerce_Epdq_Model_Api::STATUS_WAITING_CLIENT :
                    case Appmerce_Epdq_Model_Api::STATUS_PAYMENT_INCOMPLETE :
                    case Appmerce_Epdq_Model_Api::STATUS_AUTH_UNKNOWN :
                    case Appmerce_Epdq_Model_Api::STATUS_PAYMENT_UNKNOWN :
                    case Appmerce_Epdq_Model_Api::STATUS_PAYMENT_UNCERTAIN :
                    case Appmerce_Epdq_Model_Api::STATUS_IDENT_WAITING :
                        $note = Mage::helper('epdq')->__('Response Code %s: %s.', $params['STATUS'], $params['NCERRORPLUS']);
                        $this->getProcess()->pending($order, $note, $params['PAYID'], $params['STATUS']);
                        break;

                    case Appmerce_Epdq_Model_Api::STATUS_INVALID :
                    case Appmerce_Epdq_Model_Api::STATUS_TECH_PROBLEM :
                    case Appmerce_Epdq_Model_Api::STATUS_AUTH_REFUSED :
                    default :
                        $note = Mage::helper('epdq')->__('Response Code %s: %s.', $params['STATUS'], $params['NCERRORPLUS']);
                        $this->getProcess()->cancel($order, $note, $params['PAYID'], $params['STATUS']);
                }
            }
        }
        else {
            echo "epdq/push/accept: no data";
        }
        exit();
    }

    /**
     * ePDQ postback action; result shown to user upon return
     *
     * @see epdq/push/cancel
     */
    public function cancelAction()
    {
        $params = $this->getRequest()->getParams();
        $this->saveDebugIn($params);

        if (isset($params['orderID'])) {
            $orderId = $params['orderID'];
            $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
            if ($this->validateePDQData($order)) {
                $paymentStatus = $this->getRequest()->getParam('STATUS');
                $note = Mage::helper('epdq')->__('Response Code %s: %s.', $params['STATUS'], $params['NCERROR']);
                $this->getProcess()->cancel($order, $note, $params['PAYID'], $params['STATUS']);
            }
        }
        else {
            echo "epdq/push/cancel: no data";
        }
        exit();
    }

    /**
     * Offline status update action
     *
     * @see epdq/push/offline
     */
    public function offlineAction()
    {
        $params = $this->getRequest()->getParams();
        $this->saveDebugIn($params);

        if (isset($params['orderID'])) {
            $orderId = $params['orderID'];
            $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
            if ($this->validateePDQData($order)) {
                $paymentStatus = $this->getRequest()->getParam('STATUS');
                switch ($paymentStatus) {
                    case Appmerce_Epdq_Model_Api::STATUS_AUTHORIZED :
                    case Appmerce_Epdq_Model_Api::STATUS_PAYMENT_REQUESTED :
                        $note = Mage::helper('epdq')->__('Payment Status: Success.');
                        $this->getProcess()->success($order, $note, $params['PAYID'], $params['STATUS']);
                        break;

                    case Appmerce_Epdq_Model_Api::STATUS_AUTH_WAITING :
                    case Appmerce_Epdq_Model_Api::STATUS_PAYMENT_INCOMPLETE :
                    case Appmerce_Epdq_Model_Api::STATUS_AUTH_UNKNOWN :
                    case Appmerce_Epdq_Model_Api::STATUS_PAYMENT_UNKNOWN :
                    case Appmerce_Epdq_Model_Api::STATUS_PAYMENT_UNCERTAIN :
                        $note = Mage::helper('epdq')->__('Response Code %s: %s.', $params['STATUS'], $params['NCERROR']);
                        $this->getProcess()->pending($order, $note, $params['PAYID'], $params['STATUS']);
                        break;

                    case Appmerce_Epdq_Model_Api::STATUS_INVALID :
                    case Appmerce_Epdq_Model_Api::STATUS_TECH_PROBLEM :
                    case Appmerce_Epdq_Model_Api::STATUS_AUTH_REFUSED :
                    default :
                        $note = Mage::helper('epdq')->__('Response Code %s: %s.', $params['STATUS'], $params['NCERROR']);
                        $this->getProcess()->cancel($order, $note, $params['PAYID'], $params['STATUS']);
                }
            }
        }
        else {
            echo "epdq/push/offline: no data";
        }
        exit();
    }

    /**
     * Debug in
     */
    public function saveDebugIn($in)
    {
        if ($this->getApi()->getConfig()->getServiceConfigData('debug_flag')) {
            $url = $this->getRequest()->getPathInfo();
            $data = print_r($in, true);
            Mage::getModel('epdq/api_debug')->setDir('in')->setUrl($url)->setData('data', $data)->save();
        }
    }

}
