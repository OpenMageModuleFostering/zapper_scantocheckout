<?php
    class Zapper_ScanToCheckout_Model_Create extends Mage_Core_Model_Abstract
    {
        public function updateQuoteAddress($firstName,$lastName,$email,$street1,$street2,$city,$postcode,$country,$celNum )
        {
            $session = Mage::getSingleton('checkout/session');
            if (Mage::getSingleton('customer/session')->isLoggedIn() ) 
            {
                $customer = mage::getModel('customer/customer')->load(Mage::getSingleton('customer/session')->getCustomer()->getId());
                $passwd = '';
            }
            else 
            {
                if ($email == 'undefined')
                {
               
                    $customersearch = mage::getModel('customer/customer')->getCollection()
                        ->addAttributeToSelect('*')
                        ->addAttributeToFilter('firstname', array('like' => "$firstName"))
                        ->addAttributeToFilter('lastname', array('like' => "$lastName"));
                    if (count($customersearch)==1)
                    {
                        foreach($customersearch as $result)
                        {
                            $customer=mage::getModel('customer/customer')->load($result->getId());
                        }
                        $email = $customer->getEmail();
                    }
                    else {
                        //return error unable to verify email. please update zapper with a valid email address and try again????
                        echo 'Email verification';
                        die;
                    }
                }
                else
                {
              
                    $customersearch = mage::getModel('customer/customer')->getCollection()
                        ->addAttributeToSelect('*')
                        ->addAttributeToFilter('email', array('like' => "$email"));
                    if (count($customersearch)==1)
                    {
            			$customer = Mage::getModel('customer/customer');
            			$customer->setWebsiteId(Mage::app()->getWebsite()->getId());
                        $customer->loadByEmail($email);          
                        if ($customer->getId())
	                        Mage::getSingleton('customer/session')->loginById($customer->getId());
                    }                    
                    else {
                     
                        $passwd = $this->randomPassword();
                        
                        //create Customer
			
                        $customer = Mage::getModel('customer/customer');
                        $password = $passwd;
                        $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
                        $customer->loadByEmail($email);
                        //Zend_Debug::dump($customer->debug()); exit;
                        if(!$customer->getId()) {
                            $customer->setEmail($email);
                            $customer->setFirstname($firstName);
                            $customer->setLastname($lastName);
                            $customer->setPassword($password);
                        }
                        
                        try {
                            $customer->save();
                            $customer->setConfirmation(null);
                            $customer->save();
                            //Make a "login" of new customer
                            Mage::getSingleton('customer/session')->loginById($customer->getId());
                        }
                        catch (Exception $ex) {
                            //Zend_Debug::dump($ex->getMessage());
                        }
            
                        $_custom_address = array (
                            'firstname' =>$firstName,
                            'lastname' =>$lastName,
                            'company' => '',
                            'email' =>  $email,
                            'street' => array(
                                '0' => $street1,
                                '1' => $street2
                            ),
                            'city' => $city,
                            'region_id' => '',
                            'region' => '',
                            'postcode' => $postcode,
                            'country_id' => $country,
                            'telephone' =>  $celNum,
                            
                        );
                        $customAddress = Mage::getModel('customer/address');
                        //$customAddress = new Mage_Customer_Model_Address();
                        $customAddress->setData($_custom_address)
                                    ->setCustomerId($customer->getId())
                                    ->setIsDefaultBilling('1')
                                    ->setIsDefaultShipping('1')
                                    ->setSaveInAddressBook('1');
                        try {
                            $customAddress->save();
                        }
                        catch (Exception $ex) {
                            mage::log($ex->getMessage());
                        }
                    }
                }    
                
            }            
            
            $quote_id = $session->getQuoteId();
            $quote = Mage::getModel('sales/quote')->load($quote_id);
            $billingAddress = array(
                'firstname' =>$firstName,
                'lastname' =>$lastName,
                'company' => '',
                'email' =>  $email,
                'street' => $street1.",".$street2,
                'city' => $city,
                'region_id' => '',
                'region' => '',
                'postcode' => $postcode,
                'country_id' => $country,
                'telephone' =>  $celNum,
                'fax' => '',
                'customer_password' => $passwd,
                'confirm_password' =>  $passwd,
                'save_in_address_book' => '0',
                'use_for_shipping' => '1',
            );

            $quote->getBillingAddress()
                    ->addData($billingAddress);
             
            $quote->getShippingAddress()
                    ->addData($billingAddress)
                    ->setShippingMethod('freeshipping_freeshipping')
                    ->setPaymentMethod('ccsave') //set payment gateway module here
                    ->setCollectShippingRates(true)
                    ->collectTotals();
            $quote->save();
        }
        
        public function updateQuotePayment($firstName,$lastName,$crCardNumber,$cardType,$cardExpiryYear,$cardExpiryMonth,$cardCVC)
        {
            $session = Mage::getSingleton('checkout/session');
            $quote_id = $session->getQuoteId();
            $quote = Mage::getModel('sales/quote')->load($quote_id);
            $quote->getPayment()->importData(array(
                'method' => 'ccsave',
                'cc_owner' => $firstName.' '.$lastName,
                'cc_number' => $crCardNumber,
                'cc_last4' =>substr($crCardNumber,-4),
                'cc_type' => $cardType,
                'cc_exp_year' => $cardExpiryYear,
                'cc_exp_month' =>$cardExpiryMonth,
                'cc_cid' => $cardCVC));             
            $quote->save();
        }
        
        public function createOrder()
        {
            try
            {                
                $session = Mage::getSingleton('checkout/session');
                
                $quote_id = $session->getQuoteId();
                $quote = Mage::getModel('sales/quote')->load($quote_id);
                
                $itemQtys = array();
                $items = $quote->getAllItems();
                $convertQuoteObj = Mage::getSingleton('sales/convert_quote');
                $order = $convertQuoteObj->addressToOrder($quote->getShippingAddress());
                $order->setCustomerId(Mage::getSingleton('customer/session')->getCustomer()->getId());
                $orderPaymentObj = $convertQuoteObj->paymentToOrderPayment($quote->getPayment());
                $order->setBillingAddress($convertQuoteObj->addressToOrderAddress($quote->getBillingAddress()));
                $order->setShippingAddress($convertQuoteObj->addressToOrderAddress($quote->getShippingAddress()))
                        ->setShipping_method('ccsave');
                $order->setPayment($convertQuoteObj->paymentToOrderPayment($quote->getPayment()));
                
                foreach ($items as $item) {
                    $orderItem = $convertQuoteObj->itemToOrderItem($item);
                    if ($item->getParentItem()) {
                        $orderItem->setParentItem($order->getItemByQuoteItemId($item->getParentItem()->getId()));
                    }
                    $order->addItem($orderItem);
                }
                
                $totalDue = $order->getTotalDue();
                
    
                $order->getPayment()
                    ->setBaseAmountPaid($totalDue)
                    ->setAmountPaid($totalDue)
                    ->setAmount($totalDue);
                $order->save();

                $order->sendNewOrderEmail();
                $session->setLastSuccessQuoteId($quote_id);
                $session->setLastQuoteId($quote_id);
                $session->setQuoteId($quote_id);
                $session->setLastSuccessQuoteId($quote_id);
                
                $invoice = $order->prepareInvoice();
                $invoice->register()->pay();
                $invoice->getOrder()->setIsInProcess(true);
                $order->addRelatedObject($invoice);
                $order->addStatusToHistory(Mage_Sales_Model_Order::STATE_PROCESSING,'Payment Success'."<br /> Transaction Date :".date('Y-m-d hh:mm:ss').
                    "<br /> Transaction Amount :".$quote->getGrandTotal());                
                    
                $transaction = Mage::getModel('sales/order_payment_transaction');
                $transaction->setOrderPaymentObject($order->getPayment())
                    ->setTxnType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_PAYMENT)
                    ->setTxnId('1')
                    ->setLast_trans_id('Payment trans ID')
                    ->save();    
                
                Mage::getModel('core/resource_transaction')
                    ->addObject($invoice)
                    ->addObject($order)
                    ->save();
                
                $transaction = Mage::getModel('sales/order_payment');
    
                $session->setLastOrderId($order->getId())
                    ->setRedirectUrl('')
                    ->setLastRealOrderId($quote->getReservedOrderId);
                $session->getQuote()->setIsActive(false)->save();
                return true;
            }
            catch (Exception $e)
            {
                mage::log($e);
                return false;
            }
            
        }
	
	function randomPassword() {
            $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
            for ($i = 0; $i < 8; $i++) {
                $n = rand(0, count($alphabet)-1);
                $pass[$i] = $alphabet[$n];
            }
            return $pass;
        }
    }
