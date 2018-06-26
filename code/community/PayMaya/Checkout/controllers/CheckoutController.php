<?php

class PayMaya_Checkout_CheckoutController extends Mage_Core_Controller_Front_Action
{
    public function redirectAction()
    {
        Mage::helper('paymaya_checkout')->loadLibrary();
        /* @var $configPayment PayMaya_Checkout_Model_ConfigPayment */
        $configPayment = Mage::getModel('paymaya_checkout/configPayment');

        /* @var $checkoutSession Mage_Checkout_Model_Session */
        $checkoutSession = Mage::getSingleton('checkout/session');
        $orderSession = $checkoutSession->getLastRealOrder();
        $incrementId = $orderSession->getIncrementId();

        /* @var $order Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($incrementId);

        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();
        $orderItems = $order->getAllItems();
        $order_currency = $order->getOrderCurrencyCode();

        $public_key = $configPayment->getPublicKey();
        $secret_key = $configPayment->getSecretKey();
        $environment = $configPayment->getEnvironment();

        \PayMaya\PayMayaSDK::getInstance()->initCheckout($public_key, $secret_key, $environment);
        $checkout = new \PayMaya\API\Checkout();

        $buyer = new \PayMaya\Model\Checkout\Buyer();
        $buyer->firstName = $order->getCustomerFirstname();
        $buyer->lastName = $order->getCustomerLastname();

        $contact = new \PayMaya\Model\Checkout\Contact();
        $contact->phone = $billingAddress->getTelephone();
        $contact->email = $order->getCustomerEmail();
        $buyer->contact = $contact;

        $addressBilling = new \PayMaya\Model\Checkout\Address();
        $streets = $billingAddress->getStreet();
        $addressBilling->line1 = isset($streets[0]) ? $streets[0] : '';
        $addressBilling->line2 = isset($streets[1]) ? $streets[1] : '';
        $addressBilling->city = $billingAddress->getCity();
        $addressBilling->state = $billingAddress->getRegionCode();
        $addressBilling->zipCode = $billingAddress->getPostcode();
        $addressBilling->countryCode = $billingAddress->getCountryId();

        $addressShipping = new \PayMaya\Model\Checkout\Address();
        $streets = $shippingAddress->getStreet();
        $addressShipping->line1 = isset($streets[0]) ? $streets[0] : '';
        $addressShipping->line2 = isset($streets[1]) ? $streets[1] : '';
        $addressShipping->city = $shippingAddress->getCity();
        $addressShipping->state = $shippingAddress->getRegionCode();
        $addressShipping->zipCode = $shippingAddress->getPostcode();
        $addressShipping->countryCode = $shippingAddress->getCountryId();

        $buyer->billingAddress = $addressBilling;
        $buyer->shippingAddress = $addressShipping;

        $checkout->buyer = $buyer;

        $checkout->items = array();

        /* @var $orderItem Mage_Sales_Model_Order_Item */
        foreach($orderItems as $orderItem){

            $orderProduct = $orderItem->getProduct();

            $itemProduct = new \PayMaya\Model\Checkout\ItemAmount();
            $itemProduct->currency = $order_currency;
            $itemProduct->value = $configPayment->formatAmount($orderProduct->getPrice());
            $itemProduct->details = new \PayMaya\Model\Checkout\ItemAmountDetails();

            $lineItem = new \PayMaya\Model\Checkout\ItemAmount();
            $lineItem->currency = $order_currency;
            $lineItem->value = $configPayment->formatAmount($orderItem->getPrice());
            $lineItem->details = new \PayMaya\Model\Checkout\ItemAmountDetails();

            $item = new \PayMaya\Model\Checkout\Item();
            $item->name = $orderProduct->getName();
            $item->code = $orderProduct->getSku();
            $item->description = "";
            $item->quantity = $orderItem->getQtyOrdered();
            $item->totalAmount = $lineItem;
            $item->amount = $itemProduct;

            $checkout->items[] = $item;
        }

        $totalAmount = new \PayMaya\Model\Checkout\ItemAmount();
        $totalAmount->currency = $order_currency;
        $totalAmount->value = $configPayment->formatAmount($order->getGrandTotal());
        $totalAmount->details = new \PayMaya\Model\Checkout\ItemAmountDetails();

        $checkout_token = $configPayment->createCheckoutToken();

        $checkout->totalAmount = $totalAmount;
        $checkout->requestReferenceNumber = $incrementId;
        $checkout->redirectUrl = array(
            "success" => Mage::getUrl('paymaya/checkout/response', array('increment_id' => $incrementId, 'result' => 'success')),
            "failure" => Mage::getUrl('paymaya/checkout/response', array('increment_id' => $incrementId, 'result' => 'failure')),
            "cancel"  => Mage::getUrl('paymaya/checkout/response', array('increment_id' => $incrementId, 'result' => 'cancel')),
        );

        $checkout->execute();

        $order->setPaymayaCheckoutId($checkout->id)
            ->setPaymayaCheckoutUrl($checkout->url)
            ->setPaymayaNonce($checkout_token);
        $order->save();

        $this->_redirectUrl($checkout->url);
        return;
    }

    public function responseAction()
    {
        $result = $this->getRequest()->getParam('result');
        if($result == 'success'){
            $this->_redirect('checkout/onepage/success');
            return;
        }
        /* @var $configPayment PayMaya_Checkout_Model_ConfigPayment */
        $configPayment = Mage::getModel('paymaya_checkout/configPayment');
        $route = $configPayment->getFailureRoute();
        $this->_redirect($route);
        return;
    }

    public function returnAction()
    {
        $raw_checkout_input = file_get_contents("php://input");
        $checkout = json_decode($raw_checkout_input);
        Mage::helper('paymaya_checkout')->loadLibrary();
        /* @var $configPayment PayMaya_Checkout_Model_ConfigPayment */
        $configPayment = Mage::getModel('paymaya_checkout/configPayment');

        if(!$checkout){
            $configPayment->log("-------------------");
            $configPayment->log("Response invalid: " . $raw_checkout_input);
            $configPayment->log("-------------------");

            echo json_encode(array('message' => 'nop'));
            return;
        }

        $configPayment->log("-------------------");

        $configPayment->log("Checkout ID: " . $checkout->id);
        $configPayment->log("Checkout RRN: " . $checkout->requestReferenceNumber);
        if(isset($checkout->requestReferenceNumber)) {
            $incrementId = $checkout->requestReferenceNumber;
            try {
                /* @var $order Mage_Sales_Model_Order */
                $order = Mage::getModel('sales/order');
                $order->loadByIncrementId($incrementId);
                $configPayment->log( "Checkout Order: " . $order->getPaymayaCheckoutId());
                $configPayment->log( "Checkout Nonce: " . $order->getPaymayaNonce());
                $wht = $this->getRequest()->getParam('wht');
                $configPayment->log( "Webhook Token: " . $wht );

                if(strcmp( $wht, $configPayment->getWebhookToken()) == 0 && isset($checkout->id)) {

                    $configPayment->log( "Checkout Status: " . $checkout->status );
                    $configPayment->log( "Checkout Payment Status: " . $checkout->paymentStatus );

                    if($checkout->status == "COMPLETED" && $checkout->paymentStatus == "PAYMENT_SUCCESS") {

                        $order->setStatus(Mage_Sales_Model_Order::STATE_COMPLETE)
                            ->setState(Mage_Sales_Model_Order::STATE_COMPLETE);
                        $order->save();

                        $configPayment->log("Order " . $checkout->requestReferenceNumber . " set to completed and emptied.");
                    } else {

                        $order->setStatus(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT)
                            ->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
                        $order->save();

                        $configPayment->log( "** Failed to completed order. **" );
                    }

                    $configPayment->log( "Webhook execution completed for " . $checkout->id );
                }

            } catch(\Exception $e){
                $configPayment->log( "Order Exception: " . $e->getMessage(), "error" );
            }
        }

        $configPayment->log("-------------------");

        echo json_encode(array('message' => 'nop'));
        return;
    }
}
