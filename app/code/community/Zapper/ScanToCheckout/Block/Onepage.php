<?php

class Zapper_ScanToCheckout_Block_Onepage extends Mage_Checkout_Block_Onepage
{
    public function getSteps()
    {
        $steps = array();
        $enabled = intval(Mage::getStoreConfig('payment/scantocheckout/active', Mage::app()->getStore()));
        if($enabled == '1' ) 
        {
            $stepCodes = array('scantocheckout', 'login',  'billing', 'shipping_method',  'payment', 'review');
        }
        else 
        {
            $stepCodes = array('login',  'billing', 'shipping', 'shipping_method', 'payment', 'review');
        }
        
        if ($this->isCustomerLoggedIn()) {
            $stepCodes = array_diff($stepCodes, array('login'));
        }

        foreach ($stepCodes as $step) {
            $steps[$step] = $this->getCheckout()->getStepData($step);
        }

        return $steps;
    }
    public function getActiveStep()
    {
        $enabled = intval(Mage::getStoreConfig('payment/scantocheckout/active', Mage::app()->getStore()));
        if($enabled == '1' ) 
        {        
            return 'scantocheckout'; 
        }
        else {
            return $this->isCustomerLoggedIn() ? 'billing' : 'login';
        }
        
    }
}