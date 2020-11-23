define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'mage/url',
        'mage/storage',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function ($, quote, urlBuilder, storage, errorProcessor, customer, fullScreenLoader) {
        'use strict';
        return function (messageContainer) {
            var optionSelected = $('input.radio.ipay-chan:checked').val();
			var url = urlBuilder.build('ipay88/redirect/index?optionSelected='+optionSelected);
            $.mage.redirect(url);  
        };
    }
);
