<?php
    class Zapper_ScanToCheckout_IndexController extends Mage_Core_Controller_Front_Action
    {
        public function processOrderAction()
        {
            $street1 = $_POST['street1'];
            if (isset($_POST['street2']))
            {
                $street2 = $_POST['street2'];
            }
            else {
                $street2 = null;
            }
            $country = $_POST['country'];
            $city = $_POST['city'];
            $postcode = $_POST['postalcode'];
            if (isset($_POST['celnum']))
            {
                $celNum = $_POST['celnum'];
            }
            else
            {
                $celNum = '000 000 0000';   
            }
            
            $crCardNumber = $_POST['ccnum'];
            $cardType = $_POST['ctype'];
            $cardCVC = $_POST['ccvc'];
            $cardExpiryYear = $_POST['cexpy'];
            $cardExpiryMonth = $_POST['cexpm'];
            $firstName  = $_POST['firstname'];
            $lastName = $_POST['lastname'];         
            $email = $_POST['email'];   
            
            mage::getModel('scantocheckout/create')->updateQuoteAddress($firstName,$lastName,$email,$street1,$street2,$city,$postcode,$country,$celNum);
            
            //payment gateway integration called here,
            //default set to use savecc
            mage::getModel('scantocheckout/create')->updateQuotePayment($firstName,$lastName,$crCardNumber,$cardType,$cardExpiryYear,$cardExpiryMonth,$cardCVC);
            
            
            if (mage::getModel('scantocheckout/create')->createOrder())
            {
                $data='success';
            }
            else {
            	$data='failure';
            }                        
            echo $data;
        }
}
    