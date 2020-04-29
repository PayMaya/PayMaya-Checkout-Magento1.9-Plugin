<?php

class PayMaya_Checkout_Adminhtml_Paymaya_CheckoutController
    extends Mage_Adminhtml_Controller_Action
{

    protected function _isAllowed(){
        return Mage::getSingleton('admin/session')->isAllowed('paymaya_checkout/log');
    }

    public function indexAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('paymaya_checkout')
            ->_title(Mage::helper('paymaya_checkout')->__('Payment Log'));
        $this->renderLayout();
    }

    public function deleteAction()
    {
        /* @var $configPayment PayMaya_Checkout_Model_ConfigPayment */
        $configPayment = Mage::getModel('paymaya_checkout/configPayment');
        $configPayment->deleteLog();
        $this->_redirect('*/*/index');
    }
}
