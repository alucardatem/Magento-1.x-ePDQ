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

class Appmerce_Epdq_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Return payment API model
     *
     * @return Appmerce_Epdq_Model_Api
     */
    protected function getApi()
    {
        return Mage::getSingleton('epdq/api');
    }

    /**
     * Return Hash Algorithm
     *
     * @return string
     */
    public function getHashAlgorithm()
    {
        return $this->getApi()->getConfig()->getServiceConfigData('hash_algorithm');
    }

    /**
     * Crypt Data by SHA ctypting algorithm by secret key
     *
     * @param array $data
     * @param string $key
     * @return hash
     */
    public function shaCrypt($data, $key = '')
    {
        if (is_array($data)) {
            return hash($this->getHashAlgorithm(), implode("", $data));
        }
        if (is_string($data)) {
            return hash($this->getHashAlgorithm(), $data);
        }
        else {
            return;
        }
    }

    /**
     * Check hash crypted by SHA with existing data
     *
     * @param array $data
     * @param string $hash
     * @param string $key
     * @return bool
     */
    public function shaCryptValidation($data, $hash, $key = '')
    {
        if (is_array($data)) {
            return (bool)(strtoupper(hash($this->getHashAlgorithm(), implode("", $data))) == $hash);
        }
        elseif (is_string($data)) {
            return (bool)(strtoupper(hash($this->getHashAlgorithm(), $data)) == $hash);
        }
        else {
            return false;
        }
    }

    /**
     * Build the signature string SHA-OUT
     * Signature version 1: 'Main parameters only'
     *
     * @param array $params
     * @param string $secretKey
     * @return string
     */
    public function getShaOutSetV1($params, $secretKey)
    {
        $secretSet = '';
        $secretSet .= $params['orderID'];
        $secretSet .= $params['currency'];
        $secretSet .= $params['amount'];
        $secretSet .= $params['PM'];
        $secretSet .= $params['ACCEPTANCE'];
        $secretSet .= $params['STATUS'];
        $secretSet .= $params['CARDNO'];
        $secretSet .= $params['PAYID'];
        $secretSet .= $params['NCERROR'];
        $secretSet .= $params['BRAND'];
        $secretSet .= $secretKey;

        return $secretSet;
    }

    /**
     * Build the signature string SHA-OUT
     * Signature version 2: 'Main parameters followed by value'
     *
     * @param array $params
     * @param string $secretKey
     * @return string
     */
    public function getShaOutSetV2($params, $secretKey)
    {
        $secretSet = '';

        // Rule 1: KEY=VALUE + SHA-IN
        // Rule 2: Do not include key with empty values
        // Rule 3: Sort keys alphabetically, case insensitive
        // Rule 4: Do not include SHASIGN
        uksort($params, "strnatcasecmp");
        foreach ($params as $key => $value) {
            if ($value <> '' && !in_array($key, array('SHASIGN'))) {
                $secretSet .= strtoupper($key) . '=' . $value . $secretKey;
            }
        }

        return $secretSet;
    }

    /**
     * Build the signature string SHA-IN
     * Signature version 1: 'Main parameters only'
     *
     * @param array $params
     * @param string $secretKey
     * @return string
     */
    public function getShaInSetV1($params, $secretKey)
    {
        $secretSet = '';
        $secretSet .= $params['orderID'];
        $secretSet .= $params['amount'];
        $secretSet .= $params['currency'];
        $secretSet .= $params['PSPID'];
        $secretSet .= $params['OPERATION'];
        $secretSet .= $secretKey;

        return $secretSet;
    }

    /**
     * Build the signature string SHA-IN
     * Signature version 2: 'Main parameters followed by value'
     *
     * @param array $params
     * @param string $secretKey
     * @return string
     */
    public function getShaInSetV2($formFields, $secretKey)
    {
        $secretSet = '';

        // Rule 1: KEY=VALUE + SHA-IN
        // Rule 2: do not include keys with empty values
        // Rule 3: sort keys alphabetically like: aAbB, not like: ABab
        uksort($formFields, "strnatcasecmp");
        foreach ($formFields as $key => $value) {
            if ($value <> '') {
                $secretSet .= strtoupper($key) . '=' . $value . $secretKey;
            }
        }

        return $secretSet;
    }

    /**
     * Get the real IP address of a visitor
     *
     * @return string
     */
    public function getRealIpAddr()
    {
        $ip = null;
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

}
