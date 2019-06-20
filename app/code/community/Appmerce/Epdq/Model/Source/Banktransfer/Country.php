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

class Appmerce_Epdq_Model_Source_Banktransfer_Country
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => 'Bank transfer',
                'label' => Mage::helper('epdq')->__('No specific country')
            ),
            array(
                'value' => 'Bank transfer BE',
                'label' => Mage::helper('epdq')->__('Belgium')
            ),
            array(
                'value' => 'Bank transfer DE',
                'label' => Mage::helper('epdq')->__('Germany')
            ),
            array(
                'value' => 'Bank transfer FR',
                'label' => Mage::helper('epdq')->__('France')
            ),
            array(
                'value' => 'Bank transfer NL',
                'label' => Mage::helper('epdq')->__('Netherlands')
            ),
        );
    }

}
