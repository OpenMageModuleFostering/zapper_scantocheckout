<?php

    require_once 'Mage/Checkout/controllers/OnepageController.php';
    
    class Zapper_ScanToCheckout_OnepageController extends Mage_Checkout_OnepageController
    {       
        
         
         public function indexAction()
        {
            //show zapper
            if (!Mage::helper('checkout')->canOnepageCheckout()) {
                Mage::getSingleton('checkout/session')->addError($this->__('The onepage checkout is disabled.'));
                $this->_redirect('checkout/cart');
                return;
            }
            $quote = $this->getOnepage()->getQuote();
            if (!$quote->hasItems() || $quote->getHasError()) {
                $this->_redirect('checkout/cart');
                return;
            }
            
            if (!$quote->validateMinimumAmount()) {
                $error = Mage::getStoreConfig('sales/minimum_order/error_message') ?
                    Mage::getStoreConfig('sales/minimum_order/error_message') :
                    Mage::helper('checkout')->__('Subtotal must exceed minimum order amount');
    
                Mage::getSingleton('checkout/session')->addError($error);
                $this->_redirect('checkout/cart');
                return;
            }
            
            Mage::getSingleton('checkout/session')->setCartWasUpdated(false);
            Mage::getSingleton('customer/session')->setBeforeAuthUrl(Mage::getUrl('*/*/*', array('_secure' => true)));
            $this->getOnepage()->initCheckout();
            $this->loadLayout();
            $this->_initLayoutMessages('customer/session');
            $this->getLayout()->getBlock('head')->setTitle($this->__('Checkout'));
            $this->renderLayout();
            
        }

        
        protected function _getPaymentMethodsHtml()
        {
            $layout = $this->getLayout();
            $update = $layout->getUpdate();
            $update->load('checkout_onepage_paymentmethod');
            $layout->generateXml();
            $layout->generateBlocks();
            $output = $layout->getOutput();
            return $output;
        }
        
        public function saveScanToLoginAction()
        {
            if ($this->_expireAjax()) {
                return;
            }
            
            $result['goto_section'] = 'billing';
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
        
        public function saveScanToCheckoutAction()
        {
            if ($this->_expireAjax()) {
                return;
            }
            if (Mage::getSingleton('customer/session')->isLoggedIn() ) 
            {
                $result['goto_section'] = 'billing';
            }
            else {
                $result['goto_section'] = 'login';    
            }
            
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));            
        }
         

        public function saveBillingAction()
        {
            if ($this->_expireAjax()) {
                return;
            }
            if ($this->getRequest()->isPost()) {
                $data = $this->getRequest()->getPost('billing', array());
                $customerAddressId = $this->getRequest()->getPost('billing_address_id', false);
                
                if (isset($data['email'])) {
                    $data['email'] = trim($data['email']);
                }
                
                $result = $this->getOnepage()->saveShipping($data, $customerAddressId);
                $result = $this->getOnepage()->saveBilling($data, $customerAddressId);
                if (!isset($result['error'])) {
                    $methods = Mage::getSingleton('shipping/config')->getActiveCarriers();
                    if ($this->getOnepage()->getQuote()->isVirtual()) {
                        if (count($methods) > '2' ){
                            //$method = freeshipping_freeshipping;
                            //$result = $this->getOnepage()->saveShippingMethod($method);
                            //Mage::getSingleton('checkout/type_onepage')->getQuote()->getShippingAddress()->setShippingMethod($method)->save();
                        }
                        if (!isset($result['error'])) {
                            $result['goto_section'] = 'payment';
                            $result['update_section'] = array(
                                'name' => 'payment-method',
                                'html' => $this->_getPaymentMethodsHtml()
                            );
                        }
                            
                        /** $result['goto_section'] = 'payment';
                        $result['update_section'] = array(
                        'name' => 'payment-method',
                        'html' => $this->_getPaymentMethodsHtml()
                        );
                         */
                    }
                    else {
                        
                        $method = 'freeshipping_freeshipping';
                        $result = $this->getOnepage()->saveShippingMethod($method);
                        Mage::getSingleton('checkout/type_onepage')->getQuote()->getShippingAddress()->setShippingMethod($method)->save();
                           
                        if (!isset($result['error'])) {
                            $result['goto_section'] = 'payment';
                            $result['update_section'] = array(
                                'name' => 'payment-method',
                                'html' => $this->_getPaymentMethodsHtml()
                            );
                        
                    
                    }
                }
    
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
            }
        } 
    }
    
    public function updateCheckoutAction()
    {
        if ($this->_expireAjax() || !$this->getRequest()->isPost()) {
            return;
        }
        

            
        /*********** DISCOUNT CODES **********/
 
        $quote              = $this->getOnepage()->getQuote();
        $couponData         = $this->getRequest()->getPost('coupon', array());
        $processCoupon      = $this->getRequest()->getPost('process_coupon', false);
       
        $couponChanged      = false;
        if ($couponData && $processCoupon) {
                if (!empty($couponData['remove'])) {
                    $couponData['code'] = '';
                     
                }
                $oldCouponCode = $quote->getCouponCode();
                if ($oldCouponCode != $couponData['code']) {
                    try {
                        $quote->setCouponCode(
                            strlen($couponData['code']) ? $couponData['code'] : ''
                        );
                        $this->getRequest()->setPost('payment-method', true);
                        $this->getRequest()->setPost('shipping-method', true);
                        if ($couponData['code']) {
                            $couponChanged = true;
                        } else {
                            $couponChanged = true;
                            Mage::getSingleton('checkout/session')->addSuccess(Mage::helper('onepagecheckout')->__('Coupon code was canceled.'));
                        }
                    } catch (Mage_Core_Exception $e) {
                        $couponChanged = true;
                        Mage::getSingleton('checkout/session')->addError($e->getMessage());
                    } catch (Exception $e) {
                        $couponChanged = true;
                        Mage::getSingleton('checkout/session')->addError(Mage::helper('onepagecheckout')->__('Cannot apply the coupon code.'));
                    }
                    
                }
            }
        
        /***********************************/ 
            
        $bill_data = $this->getRequest()->getPost('billing', array());
        $bill_data = $this->_filterPostData($bill_data);
        $bill_addr_id = $this->getRequest()->getPost('billing_address_id', false);
        $result = array();
        $ship_updated = false;
        
        if ($this->_checkChangedAddress($bill_data, 'Billing', $bill_addr_id) || $this->getRequest()->getPost('payment-method', false))
        {
            if (isset($bill_data['email']))
            {
                $bill_data['email'] = trim($bill_data['email']);
            }
            
            $bill_result = $this->getOnepage()->saveBilling($bill_data, $bill_addr_id, false);

            if (!isset($bill_result['error']))
            {
                $pmnt_data = $this->getRequest()->getPost('payment', array());
                $this->getOnepage()->usePayment(isset($pmnt_data['method']) ? $pmnt_data['method'] : null);

                $result['update_section']['payment-method'] = $this->_getPaymentMethodsHtml();

                if (isset($bill_data['use_for_shipping']) && $bill_data['use_for_shipping'] == 1 && !$this->getOnepage()->getQuote()->isVirtual())
                {
                    $result['update_section']['shipping-method'] = $this->_getShippingMethodsHtml();
                    $result['duplicateBillingInfo'] = 'true';
                    
                    $ship_updated = true;
                }
            }
            else
            {
                $result['error_messages'] = $bill_result['message'];
            }
        }

        $ship_data = $this->getRequest()->getPost('shipping', array());
        $ship_addr_id = $this->getRequest()->getPost('shipping_address_id', false);
        $ship_method    = $this->getRequest()->getPost('shipping_method', false);

        if (!$ship_updated && !$this->getOnepage()->getQuote()->isVirtual())
        {
            if ($this->_checkChangedAddress($ship_data, 'Shipping', $ship_addr_id) || $ship_method) 
            {
                $ship_result = $this->getOnepage()->saveShipping($ship_data, $ship_addr_id, false);

                if (!isset($ship_result['error']))
                {
                    $result['update_section']['shipping-method'] = $this->_getShippingMethodsHtml();
                }
            }
            

            if(!isset($result['update_section']['shipping-method']) && $this->getRequest()->getPost('shipping-method', false))
            {
                $result['update_section']['shipping-method'] = $this->_getShippingMethodsHtml();
            }
            
        }

        $check_shipping_diff    = false;

        // check how many shipping methods exist
        $rates = Mage::getModel('sales/quote_address_rate')->getCollection()->setAddressFilter($this->getOnepage()->getQuote()->getShippingAddress()->getId())->toArray();
        if(count($rates['items'])==1)
        {
            if($rates['items'][0]['code']!=$ship_method)
            {
                $check_shipping_diff    = true;

                $result['reload_totals'] = 'true';
            }
        }
        else        
            $check_shipping_diff    = true;

        // get prev shipping method
        if($check_shipping_diff){
            $shipping = $this->getOnepage()->getQuote()->getShippingAddress();
            $shippingMethod_before = $shipping->getShippingMethod();
        }

        $this->getOnepage()->useShipping($ship_method);

        $this->getOnepage()->getQuote()->collectTotals()->save();

        if($check_shipping_diff){        
            $shipping = $this->getOnepage()->getQuote()->getShippingAddress();
            $shippingMethod_after = $shipping->getShippingMethod();
        
            if($shippingMethod_before != $shippingMethod_after)
            {
                $result['update_section']['shipping-method'] = $this->_getShippingMethodsHtml();
                $result['reload_totals'] = 'true';
            }
            else
                unset($result['reload_totals']);
        }

        $result['update_section']['review'] = $this->_getReviewHtml();

        
        /*********** DISCOUNT CODES **********/
        if ($couponChanged) {
            if ($couponData['code'] == $quote->getCouponCode()) {
                Mage::getSingleton('checkout/session')->addSuccess(
                    Mage::helper('onepagecheckout')->__('Coupon code "%s" was applied.', Mage::helper('core')->htmlEscape($couponData['code']))
                );
            } else {
                Mage::getSingleton('checkout/session')->addError(
                    Mage::helper('onepagecheckout')->__('Coupon code "%s" is not valid.', Mage::helper('core')->htmlEscape($couponData['code']))
                );
            }
            $method = str_replace(' ', '', ucwords(str_replace('-', ' ', 'coupon-discount')));          
            $result['update_section']['coupon-discount'] = $this->{'_get' . $method . 'Html'}();
           
        }
        /************************************/
        
        
        
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    }