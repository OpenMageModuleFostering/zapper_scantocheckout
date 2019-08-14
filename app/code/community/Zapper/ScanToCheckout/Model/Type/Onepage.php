<?php
class Zapper_ScanToCheckout_Model_Checkout_Type_Onepage extends Mage_Checkout_Model_Type_Onepage
{
    public function saveScanToCheckout($data){
        $this->getCheckout()
        ->setStepData('scantocheckout', 'allow', true)
        ->setStepData('scantocheckout', 'complete', true)
        ->setStepData('login', 'allow', true); 
        return array();
    }
}