<?php

class PayMaya_Checkout_Model_ConfigPayment
{
    const SANDBOX_MODE = 'SANDBOX';
    const PRODUCTION_MODE = 'PRODUCTION';

    public function getConfigPath($key)
    {
        return 'payment/' . PayMaya_Checkout_Model_Payment::CODE . '/' . $key;
    }

    public function getPublicKey(){
        return Mage::getStoreConfig($this->getConfigPath('public_key'));
    }

    public function getSecretKey(){
        return Mage::getStoreConfig($this->getConfigPath('secret_key'));
    }

    public function getWebhookSuccess(){
        return Mage::getStoreConfig($this->getConfigPath('webhook_success'));
    }

    public function getWebhookFailure(){
        return Mage::getStoreConfig($this->getConfigPath('webhook_failure'));
    }

    public function getWebhookToken(){
        return Mage::getStoreConfig($this->getConfigPath('webhook_token'));
    }

    public function getEnvironment(){
        $is_sandbox = Mage::getStoreConfig($this->getConfigPath('sandbox_mode'));
        return $is_sandbox ? self::SANDBOX_MODE : self::PRODUCTION_MODE;
    }

    public function getFailureRoute()
    {
        return Mage::getStoreConfig($this->getConfigPath('failure_page'));
    }

    public function registerWebhook($type = 'success'){
        $public_key = $this->getPublicKey();
        $secret_key = $this->getSecretKey();
        $environment = $this->getEnvironment();
        $url = ($type== "success") ? $this->getWebhookSuccess() : $this->getWebhookFailure();
        $token = $this->getWebhookToken();
        $webhook_name = ($type== "success") ? \PayMaya\API\Webhook::CHECKOUT_SUCCESS : \PayMaya\API\Webhook::CHECKOUT_FAILURE;

        if($public_key && $secret_key){
            \PayMaya\PayMayaSDK::getInstance()->initCheckout($public_key, $secret_key, $environment);
            $webhook = new \PayMaya\API\Webhook();
            $webhook->name = $webhook_name;
            $webhook->callbackUrl = $url . '?wht=' . $token;
            $register = json_decode($webhook->register());
            if(isset($register->error)) {
                Mage::getSingleton('core/session')->addError("There was an error saving your webhook. (" . $register->error->code . " : " . $register->error->message .")");
                return false;
            }
            return true;
        }
        return false;
    }

    public function deleteWebhook(){
        $public_key = $this->getPublicKey();
        $secret_key = $this->getSecretKey();
        $environment = $this->getEnvironment();
        if($public_key && $secret_key){
            \PayMaya\PayMayaSDK::getInstance()->initCheckout($public_key, $secret_key, $environment);
            $webhooks = \PayMaya\API\Webhook::retrieve();
            for($i = 0; $i < count($webhooks); $i++) {
                $webhook = new \PayMaya\API\Webhook();
                $webhook->id = $webhooks[$i]->id;
                $webhook->delete();
            }
        }
    }

    public function formatAmount($amount){
        return number_format($amount, 2, ".", "");
    }

    public function createCheckoutToken()
    {
        return uniqid("paymaya-pg-", true);
    }

    public function log($message, $type = "info"){
        $log_file = $this->getLogFile();
        $message = date('Y-m-d H:i:s') . " " . strtoupper($type) . " " . $message . "\r\n";
        @file_put_contents($log_file, $message, FILE_APPEND);
    }

    public function getLog(){
        $log_file = $this->getLogFile();
        if(!file_exists($log_file))
            return "";

        return file_get_contents($log_file);
    }

    public function deleteLog(){
        $log_file = $this->getLogFile();
        @unlink($log_file);
        return true;
    }

    public function getLogFile(){
        $log_path = Mage::getBaseDir('log');
        if(!is_dir($log_path)){
            @mkdir($log_path, 0777, true);
        }
        $log_file = $log_path . '/paymaya_checkout.log';
        return $log_file;
    }
}