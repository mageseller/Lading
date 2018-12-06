<?php
$installer = $this;
$installer->startSetup();
$setup = Mage::getModel('customer/entity_setup','core_setup');
$setup->addAttribute('customer','default_mobile_number',array(
	'type' => 'text',
	'input' => 'text',
	'label' => 'Default Mobile Number',
	'global' => 1,
	'visible' => 1,
	'required' => 0,
	'user_defined' => 1,
	'default' => '0',
	'visible_on_front' => 0,
	'source' => '',
));
$installer->endSetup();
?>