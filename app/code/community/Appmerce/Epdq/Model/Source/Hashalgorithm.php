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

class Appmerce_Epdq_Model_Source_Hashalgorithm
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => Appmerce_Epdq_Model_Config::ALGORITHM_SHA1,
                'label' => Mage::helper('epdq')->__('SHA-1')
            ),
            array(
                'value' => Appmerce_Epdq_Model_Config::ALGORITHM_SHA256,
                'label' => Mage::helper('epdq')->__('SHA-256')
            ),
            array(
                'value' => Appmerce_Epdq_Model_Config::ALGORITHM_SHA512,
                'label' => Mage::helper('epdq')->__('SHA-512')
            ),
        );
    }

}
