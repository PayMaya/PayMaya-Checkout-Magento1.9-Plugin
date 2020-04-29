<?php
/* @var $installer Mage_Sales_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$installer->addAttribute(Mage_Sales_Model_Order::ENTITY, 'paymaya_checkout_id', array('type' => Varien_Db_Ddl_Table::TYPE_VARCHAR, 'visible' => false, 'required' => false));
$installer->addAttribute(Mage_Sales_Model_Order::ENTITY, 'paymaya_checkout_url', array('type' => Varien_Db_Ddl_Table::TYPE_VARCHAR, 'visible' => false, 'required' => false));
$installer->addAttribute(Mage_Sales_Model_Order::ENTITY, 'paymaya_nonce', array('type' => Varien_Db_Ddl_Table::TYPE_VARCHAR, 'visible' => false, 'required' => false));

$table = $installer->getTable('core_config_data');
$connection = $installer->getConnection();
$config = $connection->fetchRow("SELECT * FROM {$table} WHERE path = :path", array(
    'path' => 'web/unsecure/base_url'
));
$base_url = $config['value'];
$return_url = rtrim($base_url, '/') . '/paymaya/checkout/return';
$token = uniqid("pgwh-", true) . uniqid() . uniqid();

Mage::getConfig()->saveConfig('payment/paymaya_checkout/webhook_success', $return_url);
Mage::getConfig()->saveConfig('payment/paymaya_checkout/webhook_failure', $return_url);
Mage::getConfig()->saveConfig('payment/paymaya_checkout/webhook_token', $token);

$installer->endSetup();
