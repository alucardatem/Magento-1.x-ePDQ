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

class Appmerce_Epdq_Model_Source_Cctypes extends Mage_Payment_Model_Source_Cctype
{
    public function getAllowedTypes()
    {
        return array(
            'AE',
            'VI',
            'MC',
            'JCB',
            'SO',
            'EPDQ_DINERSCLUB',
            'EPDQ_DANKORT',
            'EPDQ_LASER',
            'EPDQ_MAESTROUK',
            'EPDQ_COFINOGA',
            'EPDQ_UATP',
            'EPDQ_AIRPLUS',
            'EPDQ_TXCB',
            'EPDQ_AURORA',
            'EPDQ_AURORE',
            'EPDQ_CB',
            'EPDQ_BILLY'
        );
    }

}
