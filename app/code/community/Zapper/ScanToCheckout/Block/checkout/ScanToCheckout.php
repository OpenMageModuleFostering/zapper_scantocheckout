<?php


class Zapper_ScanToCheckout_Block_Onepage_ScanToCheckout extends Mage_Checkout_Block_Onepage_Abstract
{

    protected function _construct()
    {
        $enabled = intval(Mage::getStoreConfig('payment/scantocheckout/active', Mage::app()->getStore()));
        if($enabled == '1' ) 
        {
            $this->getCheckout()->setStepData('scantocheckout', array(
                'label'     => Mage::helper('checkout')->__('Scan-to-Checkout'),
                'is_show'   => $this->isShow()
            ));
            $this->getCheckout()->setStepData('scantocheckout', 'allow', true);
        }
        else 
        {
            $this->getCheckout()->setStepData('billing', array(
                'label'     => Mage::helper('checkout')->__('Billing '),
                'is_show'   => $this->isShow()
            ));
    
            if ($this->isCustomerLoggedIn()) {
                $this->getCheckout()->setStepData('billing', 'allow', true);
            }
        }
        parent::_construct();
    }



    
}
