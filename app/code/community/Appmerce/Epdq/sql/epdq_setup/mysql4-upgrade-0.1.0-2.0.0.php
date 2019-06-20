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

$installer = $this;
/* @var $installer Mage_Core_Model_Mysql4_Setup */

$installer->startSetup();

/**
 * Upgrade configuration from Morningtime to Appmerce
 * 
 * @note Before this update will run it is required to delete 'epdq_setup'
 * from core_resource
 */
$installer->run("

UPDATE `{$this->getTable('core/config_data')}` 
    SET path = REPLACE(path, 'payment_services/morningtime_epdq', 'payment_services/appmerce_epdq')
    WHERE path LIKE '%payment_services/morningtime_epdq%';

");

$installer->endSetup();
