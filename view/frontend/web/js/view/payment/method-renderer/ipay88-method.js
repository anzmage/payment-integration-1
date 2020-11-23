define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Anzmage_Ipay88/js/action/set-payment-method-action',
        'ko',
        'jquery'
    ],
    function (Component,setPaymentMethodAction, ko, $) {
        'use strict';

        $(document).ready(function(){
            $('#checkout-payment-method-load').on('click','.ipay-chan',function(){
                $('.ipay88buttonPlaceOrder').removeAttr('disabled');
            });
        });
        
        return Component.extend({
            defaults: {
                redirectAfterPlaceOrder: false,
                template: 'Anzmage_Ipay88/payment/ipay88'
            },
            getMailingAddress: function () {
                return window.checkoutConfig.payment.checkmo.mailingAddress;
            },
            getInstructions: function () {
                return '';
            },
            afterPlaceOrder: function () {
                setPaymentMethodAction(this.messageContainer);
                return false;
            },
            getPaymentIds: function () {
                //apaan dah
               // ko.applyBindings({ipaychannel:window.checkoutConfig.ipay_channel});
                return window.checkoutConfig.ipay_channel;
            }

        });
    }
);
