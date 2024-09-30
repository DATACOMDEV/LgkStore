define([
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'Magento_Customer/js/section-config'
], function (Component, customerData, config) {
    'use strict';

    return Component.extend({
        /** @inheritdoc */
        initialize: function () {
            this._super();
            // this.customsection = customerData.get('customsection'); //pass your custom section name

            customerData.reload(['logged_section'], true);
            this.logged_section = customerData.get('logged_section');

            if (this.logged_section().logged == 1) {
                document.body.classList.add('logged-user');
                document.body.classList.add('customer-group-' + this.logged_section().customer_group_id);
            } else {
                document.body.classList.add('guest-user');
            }
        }
    });
});