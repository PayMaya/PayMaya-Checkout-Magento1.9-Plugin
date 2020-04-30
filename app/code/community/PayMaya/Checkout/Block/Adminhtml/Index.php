<?php

class PayMaya_Checkout_Block_Adminhtml_Index
    extends Mage_Adminhtml_Block_Template
{
    public function getPaymentLog()
    {
        /* @var $configPayment PayMaya_Checkout_Model_ConfigPayment */
        $configPayment = Mage::getModel('paymaya_checkout/configPayment');
        return $configPayment->getLog();
    }
}
