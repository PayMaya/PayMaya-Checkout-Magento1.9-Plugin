<?php

class PayMaya_Checkout_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function loadLibrary()
    {
        $module_dir = Mage::getModuleDir('community', 'PayMaya_Checkout');
        $autoload_path = rtrim($module_dir, '\\/') . '/lib/load.php';
        require_once $autoload_path;
    }


}