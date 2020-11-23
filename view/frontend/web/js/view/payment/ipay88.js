define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'ipay88',
                component: 'Anzmage_Ipay88/js/view/payment/method-renderer/ipay88-method'
            }
        );
        return Component.extend({});
    }
);