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
                type: 'preventivo',
                component: 'Datacom_Preventivo/js/view/payment/method-renderer/preventivo-method'
            }
        );
        return Component.extend({});
    }
);