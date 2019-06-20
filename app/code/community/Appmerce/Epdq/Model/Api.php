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

class Appmerce_Epdq_Model_Api extends Mage_Payment_Model_Method_Abstract
{
    protected $_formBlockType = 'epdq/form';
    protected $_infoBlockType = 'epdq/info';

    // Magento features
    protected $_isGateway = false;
    protected $_canOrder = false;
    protected $_canAuthorize = false;
    protected $_canCapture = false;
    protected $_canCapturePartial = false;
    protected $_canRefund = false;
    protected $_canRefundInvoicePartial = false;
    protected $_canVoid = false;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = false;
    protected $_isInitializeNeeded = true;
    protected $_canFetchTransactionInfo = false;
    protected $_canReviewPayment = false;
    protected $_canCreateBillingAgreement = false;
    protected $_canManageRecurringProfiles = false;

    // Restrictions
    protected $_allowCurrencyCode = array();

    // Response codes
    const STATUS_INVALID = 0;
    const STATUS_PAYMENT_INCOMPLETE = 1;
    const STATUS_AUTH_REFUSED = 2;
    const STATUS_AUTHORIZED = 5;
    const STATUS_PAYMENT_REQUESTED = 9;
    const STATUS_WAITING_CLIENT = 41;
    const STATUS_IDENT_WAITING = 46;
    const STATUS_AUTH_WAITING = 51;
    const STATUS_AUTH_UNKNOWN = 52;
    const STATUS_PAYMENT_UNKNOWN = 91;
    const STATUS_PAYMENT_UNCERTAIN = 92;
    const STATUS_TECH_PROBLEM = 93;
    const STATUS_BY_MERCHANT = 95;

    // Local constants
    const DIRECTLINK_TIMEOUT = 15;
    const DIRECTLINK_ECI = '7';
    const WIND3DS_MODE = 'MAINW';
	const FLAG3D_NO = 'N';
    const FLAG3D_YES = 'Y';

    /**
     * Return ePDQ config instance
     *
     * @return Appmerce_Epdq_Model_Config
     */
    public function __construct()
    {
        $this->_config = Mage::getSingleton('epdq/config');
        return $this;
    }

    /**
     * Return epdq configuration instance
     *
     * @return Appmerce_Epdq_Model_Config
     */
    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * Validate if payment is possible
     *  - check allowed currency codes
     *
     * @return bool
     */
    public function validate()
    {
        parent::validate();
        $currency_code = $this->getCurrencyCode();
        if (!empty($this->_allowCurrencyCode) && !in_array($currency_code, $this->_allowCurrencyCode)) {
            $errorMessage = Mage::helper('epdq')->__('Selected currency (%s) is not compatible with this payment method.', $currency_code);
            Mage::throwException($errorMessage);
        }
        return $this;
    }

    /**
     * Return gateway path, get from confing. Setup on admin place.
     *
     * @param int $storeId
     * @return string
     */
    public function getGatewayUrl($type, $storeId = null)
    {
        $gateways = $this->getConfig()->getGatewayUrls();
        $test = $this->getConfig()->getServiceConfigData('test_flag') ? 'test' : 'live';
        return $gateways[$type][$test];
    }

    /**
     * ePDQ redirect URL for placement form
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return $this->getConfig()->getApiUrl('placement');
    }

    /**
     * Decide currency code type
     *
     * @return string
     */
    public function getCurrencyCode()
    {
        if ($this->getConfig()->getServiceConfigData('base_currency')) {
            $currencyCode = Mage::app()->getStore()->getBaseCurrencyCode();
        }
        else {
            $currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
        }
        return $currencyCode;
    }

    /**
     * Decide grand total
     *
     * @return float
     */
    public function getGrandTotal($order)
    {
        if ($this->getConfig()->getServiceConfigData('base_currency')) {
            $grandTotal = $order->getBaseGrandTotal();
        }
        else {
            $grandTotal = $order->getGrandTotal();
        }
        return round($grandTotal * 100);
    }

    /**
     * Decide discount
     *
     * @return float
     */
    public function getDiscountTotal($order)
    {
        if ($this->getConfig()->getServiceConfigData('base_currency')) {
            $total = $order->getBaseDiscountAmount();
        }
        else {
            $total = $order->getDiscountAmount();
        }
        return $total;
    }

    /**
     * Decide shipping
     *
     * @return float
     */
    public function getShippingTotal($order)
    {
        if ($this->getConfig()->getServiceConfigData('base_currency')) {
            $total = $order->getBaseShippingInclTax();
        }
        else {
            $total = $order->getShippingInclTax();
        }
        return $total;
    }

    /**
     * Decide shipping tax
     *
     * @return float
     */
    public function getShippingTax($order)
    {
        if ($this->getConfig()->getServiceConfigData('base_currency')) {
            $total = $order->getBaseShippingTaxAmount();
        }
        else {
            $total = $order->getShippingTaxAmount();
        }
        return $total;
    }

    /**
     * Decide order item price
     *
     * @return float
     */
    public function getItemPrice($orderItem)
    {
        if ($this->getConfig()->getServiceConfigData('base_currency')) {
            $total = $orderItem->getBasePriceInclTax();
        }
        else {
            $total = $orderItem->getPriceInclTax();
        }
        return $total;
    }

    /**
     * Rrepare parameters array to send it to the ePDQ gateway page via POST
     *
     * @param Mage_Sales_Model_Order
     * @return array
     */
    public function getFormFields($order)
    {
        if (empty($order)) {
            if (!($order = $this->getOrder())) {
                return array();
            }
        }

        $storeId = $order->getStoreId();
        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();
        if (!$shippingAddress || !is_object($shippingAddress)) {
            $shippingAddress = $billingAddress;
        }
        $paymentMethodCode = $order->getPayment()->getMethod();
        $pmConfig = $this->getConfig()->getPm($paymentMethodCode);

        $formFields = array();
        $formFields['OPERATION'] = $this->getConfig()->getServiceConfigData('operation');
        $formFields['PSPID'] = $this->getConfig()->getServiceConfigData('pspid', $storeId);
        $formFields['USERID'] = $this->getConfig()->getServiceConfigData('userid', $storeId);
        $formFields['orderID'] = $order->getIncrementId();
        $formFields['amount'] = $this->getGrandTotal($order);
        $formFields['currency'] = $this->getCurrencyCode();
        $formFields['language'] = Mage::app()->getLocale()->getLocaleCode();

        // Customer fields
        $formFields['CN'] = $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname();
        $formFields['EMAIL'] = $order->getCustomerEmail();
        $formFields['ownerZIP'] = substr($billingAddress->getPostcode(), 0, 10);
        $formFields['ownercty'] = $billingAddress->getCountry();
        $formFields['OWNERTOWN'] = $billingAddress->getCity();
        $formFields['COM'] = str_replace('"', '', $this->getConfig()->getOrderDescription($order));
        $formFields['OWNERTELNO'] = $billingAddress->getTelephone();
        $formFields['owneraddress'] = str_replace("\n", ' ', $billingAddress->getStreet(-1));

        // Return URLs
        $formFields['ACCEPTURL'] = $this->getConfig()->getApiUrl('accept', $storeId, true);
        $formFields['declineurl'] = $this->getConfig()->getApiUrl('decline', $storeId, true);
        $formFields['exceptionurl'] = $this->getConfig()->getApiUrl('exception', $storeId, true);

        // Fraud Detection Module
        if ($this->getConfig()->getServiceConfigData('fraud_detection')) {
            $formFields['REMOTE_ADDR'] = Mage::helper('epdq')->getRealIpAddr();
        }

        // 3-D Secure
        if ($this->getConfig()->getServiceConfigData('flag3d')) {
            $formFields['FLAG3D'] = self::FLAG3D_YES;
            $formFields['WIN3DS'] = self::WIND3DS_MODE;
            $formFields['HTTP_ACCEPT'] = $_SERVER['HTTP_ACCEPT'];
            $formFields['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];
        }
		else {
			$formFields['FLAG3D'] = self::FLAG3D_NO;
		}

        // Selected payment method (must come before Payment specific fields)
        $formFields['PM'] = isset($pmConfig['PM']) ? $pmConfig['PM'] : '';
        $formFields['BRAND'] = isset($pmConfig['BRAND']) ? $pmConfig['BRAND'] : '';

        // Fields specific to DirectLink or Normal
        switch ($paymentMethodCode) {
            case 'epdq_ccdirect' :
                $secretKey = $this->getConfig()->getPaymentConfigData($paymentMethodCode, 'sha_in', $storeId);
                break;

            default :
                $secretKey = $this->getConfig()->getServiceConfigData('sha_in', $storeId);

                // Extra return URLs
                $formFields['CANCELURL'] = $this->getConfig()->getApiUrl('cancel', $storeId, true);
                $formFields['HOMEURL'] = Mage::getUrl('', array('_store' => $storeId, '_nosid' => true));
                $formFields['BACKURL'] = Mage::getUrl('checkout/cart', array('_store' => $storeId, '_nosid' => true));
                $formFields['CATALOGURL'] = Mage::getUrl('', array('_store' => $storeId, '_nosid' => true));
        }

        // Hosted template vars
        $formFields['TP'] = $this->getConfig()->getApiUrl('payment', $storeId, true);
        if ($this->getConfig()->getServiceConfigData('template') == 'epdq') {
            unset($formFields['TP']);

            $formFields['TITLE'] = $this->getConfig()->getServiceConfigData('html_title');
            $formFields['BGCOLOR'] = $this->getConfig()->getServiceConfigData('bgcolor');
            $formFields['TXTCOLOR'] = $this->getConfig()->getServiceConfigData('txtcolor');
            $formFields['TBLBGCOLOR'] = $this->getConfig()->getServiceConfigData('tblbgcolor');
            $formFields['TBLTXTCOLOR'] = $this->getConfig()->getServiceConfigData('tbltxtcolor');
            $formFields['BUTTONBGCOLOR'] = $this->getConfig()->getServiceConfigData('buttonbgcolor');
            $formFields['BUTTONTXTCOLOR'] = $this->getConfig()->getServiceConfigData('buttontxtcolor');
            $formFields['FONTTYPE'] = $this->getConfig()->getServiceConfigData('fonttype');
            $formFields['LOGO'] = $this->getConfig()->getServiceConfigData('logo');
        }

        // iPhone template
        if ($this->getConfig()->getServiceConfigData('iphone')) {
            if (strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') !== false) {
                $formFields['TP'] = 'PaymentPage_1_iPhone.htm';
            }
        }

        // Alias manager
        // Builds alias like: 'OrderID-CustomerID' (max 16 chars)
        switch ($paymentMethodCode) {
            case 'epdq_cc' :
            case 'epdq_ccdirect' :
                if ($this->getConfig()->getServiceConfigData('save_alias')) {
                    $customerId = $order->getCustomerId();
                    $formFields['ALIAS'] = $order->getId() . '-' . (!empty($customerId) ? $customerId : '0');
                    if ($this->getConfig()->getServiceConfigData('alias_usage')) {
                        $formFields['ALIASUSAGE'] = $this->getConfig()->getServiceConfigData('alias_usage');
                    }
                }
                break;

            default :
        }

        // Payment method specific fields
        switch ($paymentMethodCode) {
            case 'epdq_cc' :
                unset($formFields['PM']);
                unset($formFields['BRAND']);

                // Rewrite Magento CC types to ePDQ CC types
                $cctypes_epdq = array();
                $cctypes = $this->getConfig()->getPaymentConfigData($paymentMethodCode, 'cctypes');
                foreach (explode(',', $cctypes) as $cctype) {
                    $cctypes_epdq[] = $this->getConfig()->getePDQCcType($cctype);
                }
                $formFields['PMLIST'] = implode(';', $cctypes_epdq);
                $formFields['PMLISTTYPE'] = str_replace(',', ';', $this->getConfig()->getPaymentConfigData($paymentMethodCode, 'pmlisttype'));
                break;

            case 'epdq_banktransfer' :
            case 'epdq_directdebits' :
                $type = $this->getConfig()->getPaymentConfigData($paymentMethodCode, 'country', $storeId);
                $formFields['PM'] = $type;
                $formFields['BRAND'] = $type;
                break;

            case 'epdq_installments' :
            case 'epdq_openinvoice' :
                $dob = new DateTime($order->getCustomerDob());
                $type = $this->getConfig()->getPaymentConfigData($paymentMethodCode, 'country', $storeId);
                $formFields['PM'] = $type;
                $formFields['BRAND'] = $type;

                // Split billing address street / number (sadly, Magento stores
                // both in 1 field,...)
                preg_match('/([^\d]+)\s?(.+)/i', str_replace("\n", ' ', $billingAddress->getStreet(-1)), $result);
                $billingStreetName = $result[1];
                $billingStreetNumber = $result[2];

                // Split shipping address / Street number
                preg_match('/([^\d]+)\s?(.+)/i', str_replace("\n", ' ', $shippingAddress->getStreet(-1)), $result);
                $shippingStreetName = $result[1];
                $shippingStreetNumber = $result[2];

                // Billing fields
                $formFields['ECOM_BILLTO_POSTAL_NAME_FIRST'] = substr($billingAddress->getFirstname(), 0, 50);
                $formFields['ECOM_BILLTO_POSTAL_NAME_LAST'] = substr($billingAddress->getLastname(), 0, 50);
                $formFields['ECOM_BILLTO_POSTAL_STREET_NUMBER'] = $billingStreetNumber;
                $formFields['ECOM_BILLTO_POSTAL_COUNTRYCODE'] = $billingAddress->getCountry();

                // Shipping fields
                $formFields['ECOM_SHIPTO_POSTAL_NAME_FIRST'] = substr($shippingAddress->getFirstname(), 0, 50);
                $formFields['ECOM_SHIPTO_POSTAL_NAME_LAST'] = substr($shippingAddress->getLastname(), 0, 50);
                $formFields['ECOM_SHIPTO_POSTAL_CITY'] = $shippingAddress->getCity();
                $formFields['ECOM_SHIPTO_POSTAL_COUNTRYCODE'] = $shippingAddress->getCountry();
                $formFields['ECOM_SHIPTO_POSTAL_POSTALCODE'] = substr($billingAddress->getPostcode(), 0, 10);
                $formFields['ECOM_SHIPTO_POSTAL_STREET_LINE1'] = $shippingStreetName;
                $formFields['ECOM_SHIPTO_POSTAL_STREET_NUMBER'] = $shippingStreetNumber;
                $formFields['ECOM_SHIPTO_DOB'] = $dob->format('d/m/Y');

                $formFields['owneraddress'] = $billingStreetName;
                $formFields['CIVILITY'] = Mage::helper('epdq')->__($this->getConfig()->getGenderCode($order->getCustomerGender()));
                $formFields['DATEIN'] = date('m/d/y H:i:s');

                // Add Order Cost Full List
                // Sum must match total amount incl tax! Think about shipping,
                // discount,...
                $i = 0;
                foreach ($order->getAllItems() as $orderItem) {
                    if ($orderItem->getParentItemId()) {
                        continue;
                    }
                    ++$i;
                    $formFields['ITEMID' . $i] = $orderItem->getItemId();
                    $formFields['ITEMNAME' . $i] = substr($orderItem->getName(), 0, 40);
                    $formFields['ITEMQUANT' . $i] = intval($orderItem->getQtyOrdered() + 0.5);
                    $formFields['ITEMPRICE' . $i] = number_format($this->getItemPrice($orderItem), 4, '.', '');
                    $formFields['ITEMVATCODE' . $i] = (int)round($orderItem->getTaxPercent()) . '%';
                    $formFields['TAXINCLUDED' . $i] = 1;
                }

                // Add shipping cost
                $shipping = $this->getShippingTotal($order);
                if ($shipping != 0) {
                    ++$i;
                    $formFields['ITEMID' . $i] = 'SHIPPING';
                    $formFields['ITEMNAME' . $i] = 'SHIPPING-AMOUNT';
                    $formFields['ITEMQUANT' . $i] = 1;
                    $formFields['ITEMPRICE' . $i] = number_format($shipping, 4, '.', '');
                    $formFields['ITEMVATCODE' . $i] = round(($this->getShippingTax($order) / $shipping) * 100, 4) . '%';
                    $formFields['TAXINCLUDED' . $i] = 1;
                }

                // Add discount amount
                $discount = $this->getDiscountTotal($order);
                if ($discount != 0) {
                    ++$i;
                    $formFields['ITEMID' . $i] = 'DISCOUNT';
                    $formFields['ITEMNAME' . $i] = 'DISCOUNT-AMOUNT';
                    $formFields['ITEMQUANT' . $i] = 1;
                    $formFields['ITEMPRICE' . $i] = number_format($discount, 4, '.', '');
                    $formFields['ITEMVATCODE' . $i] = '0%';
                    $formFields['TAXINCLUDED' . $i] = 1;
                }
                break;

            default :
        }

        // Get secret set
        $signatureMethod = $this->getConfig()->getServiceConfigData('signature_method');
        switch ($signatureMethod) {
            case Appmerce_Epdq_Model_Config::SIGNATURE_V1 :
                $secretSet = Mage::helper('epdq')->getShaInSetV1($formFields, $secretKey);
                break;

            case Appmerce_Epdq_Model_Config::SIGNATURE_V2 :
                $secretSet = Mage::helper('epdq')->getShaInSetV2($formFields, $secretKey);
                break;

            default :
                $secretSet = '';
        }

        // Sign formFields
        $formFields['SHASIGN'] = Mage::helper('epdq')->shaCrypt($secretSet);
        return $formFields;
    }

    /**
     * to translate UTF 8 to ISO 8859-1
     * ePDQ system is only compatible with iso-8859-1 and does not (yet) fully
     * support the utf-8
     */
    protected function _translate($text)
    {
        return htmlentities(iconv("UTF-8", "ISO-8859-1//TRANSLIT", $text));
    }

    /**
     * Post with CURL and return response
     *
     * @param $postUrl The URL with ?key=value
     * @param $postData string Message
     * @return reponse XML Object
     */
    public function curlPost($url, $post = array(), $get = FALSE, $return = FALSE, $auth = array())
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url . $get);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, $return);
        curl_setopt($ch, CURLOPT_HEADER, false);

        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if ($auth) {
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curl, CURLOPT_USERPWD, $auth['user'] . ":" . $auth['pass']);
        }

        if ($post) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post, '', '&'));
        }

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

}
