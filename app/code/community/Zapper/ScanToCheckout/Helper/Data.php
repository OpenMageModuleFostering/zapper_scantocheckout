<?php
    class Zapper_ScanToCheckout_Helper_Data extends Mage_Core_Helper_Abstract
    {
        function checkout()
        {
            $selfRegistrationAllowed = true;
            $demoMode = false;
            
            $baseUrl = Mage::getBaseUrl() . 'scantologin';
    
            $api_url = Mage::getStoreConfig('zapper/config/location', Mage::app()->getStore());
            $api_url = $api_url ? $api_url : 'https://zapapi.zapzap.mobi/zappertech';
            //$api_url='https://zzqa.zapzapadmin.com/staging-zappertech';
                
            if (Mage::getStoreConfig('payment/scantocheckout/sandbox') == "1")
            {
                $merchantId = '2127';
                $siteId = '2490';
            }
            else {
                $merchantId = Mage::getStoreConfig('zapper/config/merchant_id', Mage::app()->getStore());
                $siteId = Mage::getStoreConfig('zapper/scan_to_login/site_id', Mage::app()->getStore());    
            }            
            $qrSize = Mage::getStoreConfig('zapper/config/qrsize', Mage::app()->getStore());
            $timeout = Mage::getStoreConfig('zapper/config/timeout', Mage::app()->getStore());
                    
            $enable_scan = intval(Mage::getStoreConfig('payment/scantocheckout/active', Mage::app()->getStore()));
            $selector = Mage::getStoreConfig('payment/scantocheckout/selector', Mage::app()->getStore());
            $selector = $selector ? $selector : 'scan_to_checkout';
    
            $additionalParameters = Mage::getStoreConfig('payment/scantocheckout/additionalparameters', Mage::app()->getStore());
            
            $session = Mage::getSingleton('checkout/session');
            $quote_id = $session->getQuoteId();
            $quote = Mage::getModel('sales/quote')->load($quote_id);
            
            if($enable_scan == 1) { ?>
            <script>
                jQuery(function($){

                    var paymentSelector = $('<div id="scantologinpurchase-container"><div id="logo-container" class="zapperLogo"><div class="scantologin-qrcode-placeholder"></div></div><div id="scantocheckout-end-container"><span id="scantocheckout-available-for"></span><a href="http://www.zapper.com/" target="_blank" id="scantologin-zapper-link">www.zapper.com</a></div></div>');
    
                    var qrPaymentCode = new ZapperTechCheckout.QrCode({
                        merchantId: <?php echo intval($merchantId) ?>,
                        siteId: <?php echo intval($siteId) ?>,  
                        qrSize: <?php echo $qrSize ? intval($qrSize) : 4 ?>,
                        timeout: <?php echo $timeout ? intval($timeout) : 5000 ?>,
                        selector: paymentSelector,
                        baseUrl: "<?php echo $api_url?>",
                        additionalParameters: ['<?php echo strip_tags(Mage::helper('core')->currency(Mage::getSingleton('checkout/cart')->getQuote()->getGrandTotal(),true,false)) ?>']
                    });
    
                    //$('#').append('<p>Scan the purchase code for amount '+amount+' to begin</p>');
                    $('#<?php print $selector ?>').html(paymentSelector);
    
                    var payment = function(data) {
    
                        // set up some placeholders for our incoming data
                        var existingAddresses = new Array();
                        var shippingLineOne, shippingLineTwo, shippingCity, shippingPostalCode, shippingCountry;
                        var billingLineOne, billingLineTwo, billingCity, billingPostalCode, billingCountry,cellNumber;
                        
                        // iterate through the data and grab all the purchase specific data such as card info, addresses etc.
                        // you can print out data.Answers to see everything coming through
 
                        
                        $(data.Answers).each(function(i, answer) {
                            if (answer.QuestionId == 12) {
                                if ($.inArray(answer.QuestionId, existingAddresses) < 0) {
                                    shippingLineOne = answer.AnswerValue;
                                    existingAddresses.push(answer.QuestionId);
                                } else {
                                    billingLineOne = answer.AnswerValue;
                                }
                            }
                            if (answer.QuestionId == 13) {
                                if ($.inArray(answer.QuestionId, existingAddresses) < 0) {
                                    shippingLineTwo = answer.AnswerValue;
                                    existingAddresses.push(answer.QuestionId);
                                } else {
                                    billingLineTwo = answer.AnswerValue;
                                }
                            }
                            if (answer.QuestionId == 14) {
                                if ($.inArray(answer.QuestionId, existingAddresses) < 0) {
                                    shippingCity = answer.AnswerValue;
                                    existingAddresses.push(answer.QuestionId);
                                } else {
                                    billingCity = answer.AnswerValue;
                                }
                            }
                            if (answer.QuestionId == 16) {
                                if ($.inArray(answer.QuestionId, existingAddresses) < 0) {
                                    shippingPostalCode = answer.AnswerValue;
                                    existingAddresses.push(answer.QuestionId);    
                                } else {
                                    billingPostalCode = answer.AnswerValue;
                                }
                            }
                            if (answer.QuestionId == 17) {
                                if ($.inArray(answer.QuestionId, existingAddresses) < 0) {
                                    shippingCountry = answer.AnswerValue;
                                    existingAddresses.push(answer.QuestionId);
                                } else {
                                    billingCountry = answer.AnswerValue;
                                }
                            }
                            
                            if (answer.QuestionId == 2) {
                                firstname = answer.AnswerValue;
                            }
                            if (answer.QuestionId == 3) {
                                lastname = answer.AnswerValue;
                            } 
                            if (answer.QuestionId == 1) {
                                email = answer.AnswerValue;
                            } 
                            if (answer.QuestionId == 8) {
                                cellNumber = answer.AnswerValue;
                            } 
                                                       
                        });
                        
                        var MyCardType = new Array();
                        MyCardType['American Express'] = "AE";
                        MyCardType['Visa'] = "VI";
                        MyCardType['MasterCard'] = "MC";
                        MyCardType['Discover'] = "DI";
    
                        var cardNumber = qrPaymentCode.getAnswer(data.Answers, 19)
                        , cardType = MyCardType[qrPaymentCode.getAnswer(data.Answers, 18)]
                        , cardName = qrPaymentCode.getAnswer(data.Answers, 20)
                        , cardCVC = qrPaymentCode.getAnswer(data.Answers, 26)
                        , cardExpiryMonth = qrPaymentCode.getAnswer(data.Answers, 24)
                        , cardExpiryYear = qrPaymentCode.getAnswer(data.Answers, 25);
                        
                        cardExpiryMonth = cardExpiryMonth ? cardExpiryMonth : 1;
                        cardExpiryYear = cardExpiryYear ? cardExpiryYear : 2015;
                        
                        $.post('<?php print Mage::getBaseUrl(); ?>scantocheckout/index/processOrder',
                        {
                            street1 : billingLineOne,
                            street2 : billingLineTwo,
                            city : billingCity,
                            postalcode : billingPostalCode,
                            country : billingCountry,
                            celnum : cellNumber,
                            ccnum : cardNumber,
                            ctype : MyCardType[qrPaymentCode.getAnswer(data.Answers, 18)],
                            ccvc : cardCVC,
                            cexpy : cardExpiryYear,
                            cexpm : cardExpiryMonth,
                            firstname : firstname,
                            lastname : lastname,
                            email : email                            
                        }, function(data,status)
                        {
                            if (data == 'success')
                            {
                                window.location = String('<?php echo Mage::getBaseUrl() ?>checkout/onepage/success');
                            }
                            else if (data == 'failed')
                            {
                                window.location = String('<?php echo Mage::getBaseUrl() ?>checkout/onepage/failure');
                            }                            
                        });             
                    }
                    
                    // pass the payment function as a callback to the payment request
                    qrPaymentCode.paymentRequest(payment);
                    // start the purchase polling for a response
                    qrPaymentCode.start();
                });            
            </script>
            <?php
            }        
        }

        function json_encoded($data) 
        {
            @header('Cache-Control: no-cache, must-revalidate');
            @header('Expires: Mon, 26 July 1997 05:00:00 GMT');
            @header('Content-type: application/json');
            echo json_encode($data);
        }
        
        function render($type = 1)
        {     
            if ($type == 1)
            {
                $this->checkout();
            } 
        }
    }          