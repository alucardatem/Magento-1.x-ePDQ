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

class Appmerce_Epdq_Model_Api_Ccdirect extends Appmerce_Epdq_Model_Cc
{
    protected $_code = 'epdq_ccdirect';

    // Allow partial payments
    protected $_canRefundInvoicePartial = true;

    // Operations
    const OP_AUTH_CLOSE = 'RES';
    const OP_AUTH_RENEW = 'REN';
    const OP_CAPTURE_CLOSE = 'SAS';
    const OP_CAPTURE_OPEN = 'SAL';
    const OP_REFUND_CLOSE = 'RFS';
    const OP_REFUND_OPEN = 'RFD';
    const OP_DELETE_CLOSE = 'DES:';
    const OP_DELETE_OPEN = 'DEL:';

    /**
     * Key for storing transaction id in additional information of payment model
     * @var string
     */
    protected $_realTransactionIdKey = 'real_transaction_id';

    /**
     * Return checkout session
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Return order process instance
     *
     * @return Appmerce_Epdq_Model_Process
     */
    public function getProcess()
    {
        return Mage::getSingleton('epdq/process');
    }

    /**
     * Get config action to process initialization
     *
     * @return string
     */
    public function getConfigPaymentAction()
    {
        $paymentAction = $this->getConfigData('payment_action');
        return empty($paymentAction) ? true : $paymentAction;
    }

    /**
     * Authorize a payment for future capture
     *
     * @var Variant_Object $payment
     * @var Float $amount
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        if ($amount <= 0) {
            Mage::throwException(Mage::helper('epdq')->__('Invalid amount for authorization.'));
        }
        $payment->setAmount($amount);
        $order = $payment->getOrder();

        $this->_authorize($payment, $order, self::OP_AUTH_CLOSE);

        $payment->setSkipTransactionCreation(true);
        return $this;
    }

    /**
     * Authorize a payment for future capture
     *
     * @var Variant_Object $payment
     * @var Float $amount
     */
    public function capture(Varien_Object $payment, $amount)
    {
        if ($amount <= 0) {
            Mage::throwException(Mage::helper('epdq')->__('Invalid amount for capture.'));
        }
        $payment->setAmount($amount);
        $order = $payment->getOrder();

        $transactionId = $payment->getTransactionId();
        if (!$transactionId) {
            $this->_capture($payment, $order, self::OP_CAPTURE_OPEN);
        }
        else {
            $this->_maintenance($payment, $order, self::OP_CAPTURE_OPEN);
        }

        $payment->setSkipTransactionCreation(true);
        return $this;
    }

    /**
     * Authorize a payment for future capture
     *
     * @var Variant_Object $payment
     * @var Float $amount
     */
    public function refund(Varien_Object $payment, $amount)
    {
        if ($amount <= 0) {
            Mage::throwException(Mage::helper('epdq')->__('Invalid amount for refund.'));
        }
        $payment->setAmount($amount);
        $order = $payment->getOrder();

        $this->_maintenance($payment, $order, self::OP_REFUND_OPEN);

        $payment->setSkipTransactionCreation(true);
        return $this;
    }

    /**
     * Void the payment through gateway
     *
     * @param  Mage_Payment_Model_Info $payment
     * @return Mage_Paygate_Model_Authorizenet
     */
    public function void(Varien_Object $payment)
    {
        $order = $payment->getOrder();

        $this->_maintenance($payment, $order, self::OP_DELETE_CLOSE);

        $payment->setSkipTransactionCreation(true);
        return $this;
    }

    /**
     * Perform the authorize logic
     */
    public function _authorize(Varien_Object $payment, $order, $operation)
    {
        $gatewayUrl = $this->getGatewayUrl('directlink');
        $directFields = $this->getFormFields($order, Appmerce_Epdq_Model_Config::OPERATION_AUTHORIZE);
        $queryString = http_build_query($directFields, '', '&');

        // Debug out
        if ($this->getConfig()->getServiceConfigData('debug_flag')) {
            $data = print_r($this->getFormFields($order), true);
            Mage::getModel('epdq/api_debug')->setDir('out')->setUrl('checkout/onepage')->setData('data', $data)->save();
        }

        $response = new stdClass;
        $request = $this->curlPost($gatewayUrl, FALSE, '?' . $queryString, TRUE);
        if ($request) {
            $xml = new SimpleXMLElement($request);
            $attributes = $xml->attributes();

            $gatewayTransactionId = (string)$attributes['PAYID'];
            $gatewayStatus = (int)$attributes['STATUS'];
            $gatewayError = (int)$attributes['NCERROR'];
            $gatewayErrorMessage = (string)$attributes['NCERRORPLUS'];

            // Optional
            $gatewayNCStatus = (int)$attributes['NCSTATUS'];
            $gatewayPm = (string)$attributes['PM'];
            $gatewayBrand = (string)$attributes['BRAND'];
            $gatewayAcceptance = (int)$attributes['ACCEPTANCE'];

            // Flush all but card type and last4
            $this->_clearAssignedData($payment);

            $note = Mage::helper('epdq')->__('STATUS: %s', $gatewayStatus);
            $note .= ', ' . Mage::helper('epdq')->__('PM: %s', $gatewayPm);
            $note .= ', ' . Mage::helper('epdq')->__('BRAND: %s', $gatewayBrand);
            $note .= ', ' . Mage::helper('epdq')->__('NCSTATUS: %s', $gatewayNCStatus);
            $note .= ', ' . Mage::helper('epdq')->__('ACCEPTANCE: %s', $gatewayAcceptance);

            switch ($gatewayStatus) {
                case Appmerce_Epdq_Model_Cc::STATUS_DIRECT_PAYMENT_REQUESTED :
                    $this->_addTransaction($payment, $gatewayTransactionId, Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE, array('is_transaction_closed' => 0), array($this->_realTransactionIdKey => $gatewayTransactionId), $note);
                    break;

                case Appmerce_Epdq_Model_Cc::STATUS_DIRECT_AUTHORIZED :
                    $this->_addTransaction($payment, $gatewayTransactionId, Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, array('is_transaction_closed' => 0), array($this->_realTransactionIdKey => $gatewayTransactionId), $note);
                    break;

                case Appmerce_Epdq_Model_Cc::STATUS_DIRECT_IDENT_WAITING :
                case Appmerce_Epdq_Model_Cc::STATUS_DIRECT_AUTH_WAITING :
                case Appmerce_Epdq_Model_Cc::STATUS_DIRECT_AUTH_UNKNOWN :
                case Appmerce_Epdq_Model_Cc::STATUS_DIRECT_PAYMENT_UNKNOWN :
                    $this->_addTransaction($payment, $gatewayTransactionId, Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, array('is_transaction_closed' => 0), array($this->_realTransactionIdKey => $gatewayTransactionId), $note);

                    // Pending, do not authorize
                    $payment->setIsTransactionPending(true);

                    // DirectLink + 3D-Secure
                    // @todo test this! My test account does not support
                    // Directlink 3D...
                    if (isset($xml->HTML_ANSWER)) {
                        $html_output = base64_decode($xml->HTML_ANSWER);
                        $this->getResponse()->setBody($this->getLayout()->createBlock('epdq/placement3d')->setHtmlOutput($html_output)->toHtml());
                        return;
                    }
                    break;

                case Appmerce_Epdq_Model_Cc::STATUS_DIRECT_AUTH_REFUSED :
                case Appmerce_Epdq_Model_Cc::STATUS_DIRECT_INVALID :
                default :
                    if ($gatewayError == '20001001') {
                        $this->_addTransaction($payment, $gatewayTransactionId, Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, array('is_transaction_closed' => 0), array($this->_realTransactionIdKey => $gatewayTransactionId), $note);

                        // Pending, do not capture
                        $payment->setIsTransactionPending(true);
                    }
                    else {
                        Mage::throwException(Mage::helper('epdq')->__('Payment refused. %s (%s)', $gatewayErrorMessage, $gatewayError));
                    }
            }

            // Debug in
            if ($this->getConfig()->getServiceConfigData('debug_flag')) {
                $data = print_r($attributes, true);
                Mage::getModel('epdq/api_debug')->setDir('in')->setUrl('checkout/onepage')->setData('data', $data)->save();
            }
        }
        else {
            Mage::throwException(Mage::helper('epdq')->__('Payment request failed. Please contact the merchant.'));
        }

        // !important setLastTransId for CreditMemo
        $payment->setLastTransId($gatewayTransactionId);
        return $this;
    }

    /**
     * Perform the capture logic
     */
    public function _capture(Varien_Object $payment, $order, $operation)
    {
        $gatewayUrl = $this->getGatewayUrl('directlink');
        $directFields = $this->getFormFields($order, $operation);
        $queryString = http_build_query($directFields, '', '&');

        // Debug out
        if ($this->getConfig()->getServiceConfigData('debug_flag')) {
            $data = print_r($this->getFormFields($order), true);
            Mage::getModel('epdq/api_debug')->setDir('out')->setUrl('checkout/onepage')->setData('data', $data)->save();
        }

        $response = new stdClass;
        $request = $this->curlPost($gatewayUrl, FALSE, '?' . $queryString, TRUE);
        if ($request) {
            $xml = new SimpleXMLElement($request);
            $attributes = $xml->attributes();

            $gatewayTransactionId = (string)$attributes['PAYID'];
            $gatewayStatus = (int)$attributes['STATUS'];
            $gatewayError = (int)$attributes['NCERROR'];
            $gatewayErrorMessage = (string)$attributes['NCERRORPLUS'];

            // Optional
            $gatewayNCStatus = (int)$attributes['NCSTATUS'];
            $gatewayPm = (string)$attributes['PM'];
            $gatewayBrand = (string)$attributes['BRAND'];
            $gatewayAcceptance = (int)$attributes['ACCEPTANCE'];

            // Flush all but card type and last4
            $this->_clearAssignedData($payment);

            $note = Mage::helper('epdq')->__('STATUS: %s', $gatewayStatus);
            $note .= ', ' . Mage::helper('epdq')->__('PM: %s', $gatewayPm);
            $note .= ', ' . Mage::helper('epdq')->__('BRAND: %s', $gatewayBrand);
            $note .= ', ' . Mage::helper('epdq')->__('NCSTATUS: %s', $gatewayNCStatus);
            $note .= ', ' . Mage::helper('epdq')->__('ACCEPTANCE: %s', $gatewayAcceptance);

            switch ($gatewayStatus) {
                case Appmerce_Epdq_Model_Cc::STATUS_DIRECT_PAYMENT_REQUESTED :
                    $this->_addTransaction($payment, $gatewayTransactionId, Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE, array('is_transaction_closed' => 0), array($this->_realTransactionIdKey => $gatewayTransactionId), $note);
                    break;

                case Appmerce_Epdq_Model_Cc::STATUS_DIRECT_AUTHORIZED :
                case Appmerce_Epdq_Model_Cc::STATUS_DIRECT_IDENT_WAITING :
                case Appmerce_Epdq_Model_Cc::STATUS_DIRECT_AUTH_WAITING :
                case Appmerce_Epdq_Model_Cc::STATUS_DIRECT_AUTH_UNKNOWN :
                case Appmerce_Epdq_Model_Cc::STATUS_DIRECT_PAYMENT_UNKNOWN :
                    $this->_addTransaction($payment, $gatewayTransactionId, Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, array('is_transaction_closed' => 0), array($this->_realTransactionIdKey => $gatewayTransactionId), $note);

                    // Pending, do not capture
                    $payment->setIsTransactionPending(true);

                    // DirectLink + 3D-Secure
                    // @todo test this! My test account does not support
                    // Directlink 3D...
                    if (isset($xml->HTML_ANSWER)) {
                        $html_output = base64_decode($xml->HTML_ANSWER);
                        $this->getResponse()->setBody($this->getLayout()->createBlock('epdq/placement3d')->setHtmlOutput($html_output)->toHtml());
                        return;
                    }
                    break;

                case Appmerce_Epdq_Model_Cc::STATUS_DIRECT_AUTH_REFUSED :
                case Appmerce_Epdq_Model_Cc::STATUS_DIRECT_INVALID :
                default :
                    if ($gatewayError == '20001001') {
                        $this->_addTransaction($payment, $gatewayTransactionId, Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, array('is_transaction_closed' => 0), array($this->_realTransactionIdKey => $gatewayTransactionId), $note);

                        // Pending, do not capture
                        $payment->setIsTransactionPending(true);
                    }
                    else {
                        Mage::throwException(Mage::helper('epdq')->__('Payment refused. %s (%s)', $gatewayErrorMessage, $gatewayError));
                    }
            }

            // Debug in
            if ($this->getConfig()->getServiceConfigData('debug_flag')) {
                $data = print_r($attributes, true);
                Mage::getModel('epdq/api_debug')->setDir('in')->setUrl('checkout/onepage')->setData('data', $data)->save();
            }
        }
        else {
            Mage::throwException(Mage::helper('epdq')->__('Payment request failed. Please contact the merchant.'));
        }

        // !important setLastTransId for CreditMemo
        $payment->setLastTransId($gatewayTransactionId);
        return $this;
    }

    /**
     * Perform the capture logic
     */
    public function _maintenance(Varien_Object $payment, $order, $operation)
    {
        $gatewayUrl = $this->getGatewayUrl('maintenance');
        $maintenanceFields = $this->getMaintenanceFields($order, $payment, $operation);
        $queryString = http_build_query($maintenanceFields, '', '&');

        // Debug out
        if ($this->getConfig()->getServiceConfigData('debug_flag')) {
            $data = print_r($this->getMaintenanceFields($order, $payment, $operation), true);
            Mage::getModel('epdq/api_debug')->setDir('out')->setUrl('checkout/onepage')->setData('data', $data)->save();
        }

        $response = new stdClass;
        $request = $this->curlPost($gatewayUrl, FALSE, '?' . $queryString, TRUE);
        if ($request) {
            $xml = new SimpleXMLElement($request);
            $attributes = $xml->attributes();

            $gatewayTransactionId = $payment->getTransactionId();
            $gatewayStatus = (int)$attributes['STATUS'];
            $gatewayError = (int)$attributes['NCERROR'];
            $gatewayErrorMessage = (string)$attributes['NCERRORPLUS'];

            // Optional
            $gatewayNCStatus = (int)$attributes['NCSTATUS'];
            $gatewayAcceptance = (int)$attributes['ACCEPTANCE'];

            // Flush all but card type and last4
            $this->_clearAssignedData($payment);

            $note = Mage::helper('epdq')->__('STATUS: %s', $gatewayStatus);
            $note .= ', ' . Mage::helper('epdq')->__('NCSTATUS: %s', $gatewayNCStatus);
            $note .= ', ' . Mage::helper('epdq')->__('ACCEPTANCE: %s', $gatewayAcceptance);

            switch ($gatewayStatus) {
                case Appmerce_Epdq_Model_Cc::STATUS_DIRECT_PAYMENT_UNKNOWN :
                case Appmerce_Epdq_Model_Cc::STATUS_DIRECT_PAYMENT_UNCERTAIN :
                    $this->_addTransaction($payment, $gatewayTransactionId, Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, array('is_transaction_closed' => 0), array($this->_realTransactionIdKey => $gatewayTransactionId), $note);
                    // $payment->setIsTransactionPending(true);
                    break;

                case Appmerce_Epdq_Model_Cc::STATUS_DIRECT_DELETE_WAITING :
                case Appmerce_Epdq_Model_Cc::STATUS_DIRECT_DELETE_UNCERTAIN :
                case Appmerce_Epdq_Model_Cc::STATUS_DIRECT_REFUND_WAITING :
                case Appmerce_Epdq_Model_Cc::STATUS_DIRECT_REFUND_UNCERTAIN :
                    // Do nothing, intentional
                    break;

                case Appmerce_Epdq_Model_Cc::STATUS_DIRECT_DELETE_REFUSED :
                    Mage::throwException(Mage::helper('epdq')->__('Void refused. %s (%s)', $gatewayErrorMessage, $gatewayError));
                    break;

                case Appmerce_Epdq_Model_Cc::STATUS_DIRECT_REFUND_REFUSED :
                    Mage::throwException(Mage::helper('epdq')->__('Refund refused. %s (%s)', $gatewayErrorMessage, $gatewayError));
                    break;

                case Appmerce_Epdq_Model_Cc::STATUS_DIRECT_PAYMENT_REFUSED :
                case Appmerce_Epdq_Model_Cc::STATUS_DIRECT_INVALID :
                default :
                    Mage::throwException(Mage::helper('epdq')->__('Payment refused. %s (%s)', $gatewayErrorMessage, $gatewayError));
            }

            // Debug in
            if ($this->getConfig()->getServiceConfigData('debug_flag')) {
                $data = print_r($attributes, true);
                Mage::getModel('epdq/api_debug')->setDir('in')->setUrl('checkout/onepage')->setData('data', $data)->save();
            }
        }
        else {
            Mage::throwException(Mage::helper('epdq')->__('Payment request failed.'));
        }

        // !important setLastTransId for CreditMemo
        $payment->setLastTransId($gatewayTransactionId);
        return $this;
    }

    /**
     * Add payment transaction
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param string $transactionId
     * @param string $transactionType
     * @param array $transactionDetails
     * @param array $transactionAdditionalInfo
     * @return null|Mage_Sales_Model_Order_Payment_Transaction
     */
    protected function _addTransaction(Mage_Sales_Model_Order_Payment $payment, $transactionId, $transactionType, array $transactionDetails = array(), array $transactionAdditionalInfo = array(), $message = false)
    {
        $message = $message . '<br />';
        $payment->setTransactionId($transactionId);
        $payment->resetTransactionAdditionalInfo();
        foreach ($transactionDetails as $key => $value) {
            $payment->setData($key, $value);
        }
        foreach ($transactionAdditionalInfo as $key => $value) {
            $payment->setTransactionAdditionalInfo($key, $value);
        }
        $transaction = $payment->addTransaction($transactionType, null, false, $message);
        foreach ($transactionDetails as $key => $value) {
            $payment->unsetData($key);
        }
        $payment->unsLastTransId();

        /**
         * It for self using
         */
        $transaction->setMessage($message);

        return $transaction;
    }

    /**
     * Reset assigned data in payment info model
     *
     * @param Mage_Payment_Model_Info
     * @return Mage_Paygate_Model_Authorizenet
     */
    private function _clearAssignedData($payment)
    {
        $payment->setCcOwner(null)->setCcNumber(null)->setCcCid(null)->setCcExpMonth(null)->setCcExpYear(null)->setCcSsIssue(null)->setCcSsStartMonth(null)->setCcSsStartYear(null);
        return $this;
    }

}
