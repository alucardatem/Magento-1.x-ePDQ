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

class Appmerce_Epdq_Model_Config extends Mage_Payment_Model_Config
{
    const PAYMENT_SERVICES_PATH = 'payment_services/appmerce_epdq/';
    const API_CONTROLLER_PATH = 'epdq/api/';
    const PUSH_CONTROLLER_PATH = 'epdq/push/';
    const DIRECT_CONTROLLER_PATH = 'epdq/direct/';

    // Default order statuses
    const DEFAULT_STATUS_PENDING = 'pending';
    const DEFAULT_STATUS_PENDING_PAYMENT = 'pending_payment';
    const DEFAULT_STATUS_PROCESSING = 'processing';

    // Source model Appmerce_Epdq_Model_Source_Template
    const TEMPLATE_EPDQ = 'epdq';
    const TEMPLATE_MAGENTO = 'magento';

    // Source model Appmerce_Epdq_Model_Source_Paymentaction
    const OPERATION_AUTHORIZE = 'RES';
    const OPERATION_CAPTURE = 'SAL';

    // Source model Appmerce_Epdq_Model_Source_Hashalgorithm
    const ALGORITHM_SHA1 = 'sha1';
    const ALGORITHM_SHA256 = 'sha256';
    const ALGORITHM_SHA512 = 'sha512';

    // Source model Appmerce_Epdq_Model_Source_Signaturemethod
    const SIGNATURE_V1 = '1';
    const SIGNATURE_V2 = '2';

    /**
     * Get store configuration
     */
    public function getPaymentConfigData($method, $key, $storeId = null)
    {
        return Mage::getStoreConfig('payment/' . $method . '/' . $key, $storeId);
    }

    public function getServiceConfigData($key, $storeId = null)
    {
        return Mage::getStoreConfig(self::PAYMENT_SERVICES_PATH . $key, $storeId);
    }

    /**
     * Get gateway Urls
     */
    public function getGatewayUrls()
    {
        return array(
            'redirect' => array(
                'test' => 'https://mdepayments.epdq.co.uk/ncol/test/orderstandard_utf8.asp',
                'live' => 'https://payments.epdq.co.uk/ncol/prod/orderstandard_utf8.asp',
            ),
            'directlink' => array(
                'test' => 'https://mdepayments.epdq.co.uk/ncol/test/orderdirect.asp',
                'live' => 'https://payments.epdq.co.uk/ncol/prod/orderdirect.asp',
            ),
            'maintenance' => array(
                'test' => 'https://mdepayments.epdq.co.uk/ncol/test/maintenancedirect.asp',
                'live' => 'https://payments.epdq.co.uk/ncol/prod/maintenancedirect.asp',
            ),
        );
    }

    /**
     * Return available CC types
     */
    public function getePDQCcType($type)
    {
        $types = array(
            'EPDQ_TXCB' => '3XCB',
            'EPDQ_AIRPLUS' => 'AIRPLUS',
            'AE' => 'American Express',
            'EPDQ_AURORA' => 'Aurora',
            'EPDQ_AURORE' => 'Aurore',
            'EPDQ_BILLY' => 'Billy',
            'EPDQ_CB' => 'CB',
            'EPDQ_COFINOGA' => 'Cofinoga',
            'EPDQ_DANKORT' => 'Dankort',
            'EPDQ_DINERSCLUB' => 'Diners Club',
            'JCB' => 'JCB',
            'EPDQ_LASER' => 'Laser',
            'EPDQ_MAESTROUK' => 'MaestroUK',
            'MC' => 'MasterCard',
            'SO' => 'Solo',
            'EPDQ_UATP' => 'UATP',
            'VI' => 'VISA',
        );

        return $types[$type];
    }

    /**
     * Return an array of accepted payment methods for selection
     *
     * @return array of payment methods, brands, labels and image filenames
     */
    public function getPm($_code = false)
    {
        if (!$_code) {
            return;
        }

        $_pmList = array(
            'epdq_cc' => array(
                'PM' => 'CreditCard',
                'BRAND' => '',
            ),
            'epdq_americanexpress' => array(
                'PM' => 'CreditCard',
                'BRAND' => 'American Express',
            ),
            'epdq_mastercard' => array(
                'PM' => 'CreditCard',
                'BRAND' => 'MasterCard',
            ),
            'epdq_visa' => array(
                'PM' => 'CreditCard',
                'BRAND' => 'VISA',
            ),
            'epdq_ccdirect' => array(
                'PM' => 'CreditCard',
                'BRAND' => '',
            ),
            'epdq_bcmc' => array(
                'PM' => 'CreditCard',
                'BRAND' => 'BCMC',
            ),
            'epdq_maestro' => array(
                'PM' => 'CreditCard',
                'BRAND' => 'Maestro',
            ),
            'epdq_postfinancecard' => array(
                'PM' => 'PostFinance Card',
                'BRAND' => 'PostFinance + card',
            ),
            'epdq_netreserve' => array(
                'PM' => 'CreditCard',
                'BRAND' => 'NetReserve',
            ),
            'epdq_privilege' => array(
                'PM' => 'CreditCard',
                'BRAND' => 'PRIVILEGE',
            ),
            'epdq_uneurocom' => array(
                'PM' => 'UNEUROCOM',
                'BRAND' => 'UNEUROCOM',
            ),
            'epdq_amazoncheckout' => array(
                'PM' => 'Amazon Checkout',
                'BRAND' => 'Amazon Checkout',
            ),
            'epdq_cashticket' => array(
                'PM' => 'cashticket',
                'BRAND' => 'cashticket',
            ),
            'epdq_cbconline' => array(
                'PM' => 'CBC Online',
                'BRAND' => 'CBC Online',
            ),
            'epdq_centeaonline' => array(
                'PM' => 'CENTEA Online',
                'BRAND' => 'CENTEA Online',
            ),
            'epdq_dexiadirectnet' => array(
                'PM' => 'Dexia Direct Net',
                'BRAND' => 'Dexia Direct Net',
            ),
            'epdq_sofortueberweisung' => array(
                'PM' => 'DirectEbankingDE',
                'BRAND' => 'DirectEbankingDE',
            ),
            'epdq_edankort' => array(
                'PM' => 'eDankort',
                'BRAND' => 'eDankort',
            ),
            'epdq_eps' => array(
                'PM' => 'EPS',
                'BRAND' => 'EPS',
            ),
            'epdq_fortispaybutton' => array(
                'PM' => 'Fortis Pay Button',
                'BRAND' => 'Fortis Pay Button',
            ),
            'epdq_giropay' => array(
                'PM' => 'giropay',
                'BRAND' => 'giropay',
            ),
            'epdq_ideal' => array(
                'PM' => 'iDEAL',
                'BRAND' => 'iDEAL',
            ),
            'epdq_inghomepay' => array(
                'PM' => 'ING HomePay',
                'BRAND' => 'ING HomePay',
            ),
            'epdq_kbconline' => array(
                'PM' => 'KBC Online',
                'BRAND' => 'KBC Online',
            ),
            'epdq_mpass' => array(
                'PM' => 'MPASS',
                'BRAND' => 'MPASS',
            ),
            'epdq_paysafecard' => array(
                'PM' => 'paysafecard',
                'BRAND' => 'paysafecard',
            ),
            'epdq_postfinanceefinance' => array(
                'PM' => 'PostFinance e-finance',
                'BRAND' => 'PostFinance e-finance',
            ),
            'epdq_directdebits' => array(
                'AT' => array(
                    'PM' => 'Direct Debits AT',
                    'BRAND' => 'Direct Debits AT',
                ),
                'DE' => array(
                    'PM' => 'Direct Debits DE',
                    'BRAND' => 'Direct Debits DE',
                ),
                'NL' => array(
                    'PM' => 'Direct Debits NL',
                    'BRAND' => 'Direct Debits NL',
                ),
            ),
            'epdq_acceptgiro' => array(
                'PM' => 'Acceptgiro',
                'BRAND' => 'Acceptgiro',
            ),
            'epdq_banktransfer' => array(
                'BE' => array(
                    'PM' => 'Bank Transfer BE',
                    'BRAND' => 'Bank Transfer BE',
                ),
                'DE' => array(
                    'PM' => 'Bank Transfer DE',
                    'BRAND' => 'Bank Transfer DE',
                ),
                'FR' => array(
                    'PM' => 'Bank Transfer FR',
                    'BRAND' => 'Bank Transfer FR',
                ),
                'NL' => array(
                    'PM' => 'Bank Transfer NL',
                    'BRAND' => 'Bank Transfer NL',
                ),
            ),
            'epdq_installments' => array(
                'DE' => array(
                    'PM' => 'Installments DE',
                    'BRAND' => 'Installments DE',
                ),
                'DK' => array(
                    'PM' => 'Installments DK',
                    'BRAND' => 'Installments DK',
                ),
                'FI' => array(
                    'PM' => 'Installments FI',
                    'BRAND' => 'Installments FI',
                ),
                'NL' => array(
                    'PM' => 'Installments NL',
                    'BRAND' => 'Installments NL',
                ),
                'NO' => array(
                    'PM' => 'Installments NO',
                    'BRAND' => 'Installments NO',
                ),
                'SE' => array(
                    'PM' => 'Installments SE',
                    'BRAND' => 'Installments SE',
                ),
            ),
            'epdq_openinvoice' => array(
                'DE' => array(
                    'PM' => 'Open Invoice DE',
                    'BRAND' => 'Open Invoice DE',
                ),
                'DK' => array(
                    'PM' => 'Open Invoice DK',
                    'BRAND' => 'Open Invoice DK',
                ),
                'FI' => array(
                    'PM' => 'Open Invoice FI',
                    'BRAND' => 'Open Invoice FI',
                ),
                'NL' => array(
                    'PM' => 'Open Invoice NL',
                    'BRAND' => 'Open Invoice NL',
                ),
                'NO' => array(
                    'PM' => 'Open Invoice NO',
                    'BRAND' => 'Open Invoice NO',
                ),
                'SE' => array(
                    'PM' => 'Open Invoice SE',
                    'BRAND' => 'Open Invoice SE',
                ),
            ),
            'epdq_paymentondelivery' => array(
                'PM' => 'Payment on Delivery',
                'BRAND' => 'Payment on Delivery',
            ),
            'epdq_intersolve' => array(
                'PM' => 'InterSolve',
                'BRAND' => 'Intersolve Giftcards',
            ),
            'epdq_minitix' => array(
                'PM' => 'MiniTix',
                'BRAND' => 'MiniTix',
            ),
            'epdq_pingping' => array(
                'PM' => 'PingPing',
                'BRAND' => 'PingPing',
            ),
            'epdq_tunz' => array(
                'PM' => 'TUNZ',
                'BRAND' => 'TUNZ',
            ),
            'epdq_cashu' => array(
                'PM' => 'cashU',
                'BRAND' => 'cashU',
            ),
            'epdq_paypal' => array(
                'PM' => 'PAYPAL',
                'BRAND' => 'PAYPAL',
            ),
        );

        return $_pmList[$_code];
    }

    /**
     * Translate Magento gender codes to text
     */
    public function getGenderCode($magento_code)
    {
        $magento_genders = array(
            '123' => 'M',
            '124' => 'F',
        );
        return array_key_exists($magento_code, $magento_genders) ? $magento_genders[$magento_code] : 'F';
    }

    /**
     * Functions for default new/pending/processing statuses
     */
    public function getOrderStatus($code)
    {
        $status = $this->getPaymentConfigData($code, 'order_status');
        if (empty($status)) {
            $status = self::DEFAULT_STATUS_PENDING;
        }
        return $status;
    }

    public function getPendingStatus($code)
    {
        $status = $this->getPaymentConfigData($code, 'pending_status');
        if (empty($status)) {
            $status = self::DEFAULT_STATUS_PENDING_PAYMENT;
        }
        return $status;
    }

    public function getProcessingStatus($code)
    {
        $status = $this->getPaymentConfigData($code, 'processing_status');
        if (empty($status)) {
            $status = self::DEFAULT_STATUS_PROCESSING;
        }
        return $status;
    }

    /**
     * Return order description
     *
     * @param Mage_Sales_Model_Order
     * @return string
     */
    public function getOrderDescription($order)
    {
        return $order->getCustomerId() . '-' . $order->getIncrementId();
    }

    /**
     * Return URLs
     */
    public function getApiUrl($key, $storeId = null, $noSid = false)
    {
        return Mage::getUrl(self::API_CONTROLLER_PATH . $key, array(
            '_store' => $storeId,
            '_nosid' => $noSid,
            '_secure' => true
        ));
    }

    public function getPushUrl($key, $storeId = null, $noSid = false)
    {
        return Mage::getUrl(self::PUSH_CONTROLLER_PATH . $key, array(
            '_store' => $storeId,
            '_nosid' => $noSid,
            '_secure' => true
        ));
    }

    public function getDirectUrl($key, $storeId = null, $noSid = false)
    {
        return Mage::getUrl(self::DIRECT_CONTROLLER_PATH . $key, array(
            '_store' => $storeId,
            '_nosid' => $noSid,
            '_secure' => true
        ));
    }

}
