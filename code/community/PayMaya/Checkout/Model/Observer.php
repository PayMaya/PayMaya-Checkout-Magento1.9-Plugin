<?php

class PayMaya_Checkout_Model_Observer
{
    public function updateWebhook(Varien_Event_Observer $observer)
    {
        Mage::helper('paymaya_checkout')->loadLibrary();
        /* @var $configPayment PayMaya_Checkout_Model_ConfigPayment */
        $configPayment = Mage::getModel('paymaya_checkout/configPayment');
        $configPayment->deleteWebhook();
        $configPayment->registerWebhook('success');
        $configPayment->registerWebhook('failed');
        return $this;
    }
}
