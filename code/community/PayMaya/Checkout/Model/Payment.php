<?php

class PayMaya_Checkout_Model_Payment extends Mage_Payment_Model_Method_Abstract
{
    const CODE = 'paymaya_checkout';


    protected $_code = self::CODE;
    protected $_formBlockType = 'paymaya_checkout/form';
    protected $_infoBlockType = 'paymaya_checkout/info';
    protected $_supportedCurrencyCodes = array('PHP');
    protected $_isInitializeNeeded      = true;
    protected $_canUseInternal          = false;
    protected $_canUseForMultishipping  = false;

    public function isAvailable($quote = null)
    {
        if(!$this->getConfigData('public_key')
            || !$this->getConfigData('secret_key')
        ){
            return false;
        }

        return parent::isAvailable($quote);
    }

    public function canUseForCurrency($currencyCode)
    {
        if (!in_array($currencyCode, $this->_supportedCurrencyCodes)) {
            return false;
        }
        return true;
    }

    /**
     * Get instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }

    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('paymaya/checkout/redirect');
    }
}