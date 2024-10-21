/**
 * Magetop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magetop.com license that is
 * available through the world-wide-web at this URL:
 * https://www.magetop.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to
 * newer version in the future.
 *
 * @category    Magetop
 * @package     Magetop_Osc
 * @copyright   Copyright (c) Magetop (https://www.magetop.com/)
 * @license     https://www.magetop.com/LICENSE.txt
 */

define(
    [
        'jquery',
        'underscore',
        'ko',
        'uiComponent',
        'uiRegistry',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Customer/js/customer-data',
        'Magetop_Osc/js/action/set-checkout-information',
        'Magetop_Osc/js/model/braintree-paypal'
    ],
    function ($,
              _,
              ko,
              Component,
              registry,
              quote,
              additionalValidators,
              customerData,
              setCheckoutInformationAction,
              braintreePaypalModel) {
        "use strict";

        return Component.extend({
            defaults: {
                template: 'Magetop_Osc/container/review/place-order',
                visibleBraintreeButton: false,
            },
            braintreePaypalModel: braintreePaypalModel,
            selectors: {
                default: '#co-payment-form .payment-method._active button.action.primary.checkout'
            },
            isPaypalThroughBraintree: false,
            initialize: function () {
                this._super();
                var self = this;
                quote.paymentMethod.subscribe(function (value) {
                    self.processVisiblePlaceOrderButton();
                });

                registry.async(this.getPaymentPath('braintree_paypal'))
                (this.asyncBraintreePaypal.bind(this));

                return this;
            },
            /**
             * Set list of observable attributes
             * @returns {exports.initObservable}
             */
            initObservable: function () {
                var self = this;

                this._super()
                    .observe(['visibleBraintreeButton']);

                return this;
            },
            asyncBraintreePaypal: function () {
                this.processVisiblePlaceOrderButton();
            },
            isBraintreeNewVersion: function () {
                var component = this.getBraintreePaypalComponent();
                return component
                    && typeof component.isReviewRequired == "function"
                    && typeof component.getButtonTitle == "function";
            },
            processVisiblePlaceOrderButton: function () {
                this.visibleBraintreeButton(this.checkVisiblePlaceOrderButton());
            },
            checkVisiblePlaceOrderButton: function () {
                return this.getBraintreePaypalComponent()
                    && this.isPaymentBraintreePaypal();
            },
            placeOrder: function () {
                var self = this;
                if (additionalValidators.validate()) {
                    this.preparePlaceOrder().done(function () {
                        if ($('body.logged-user').length > 0) {
                            self._placeOrder();
                            return;
                        }
                        var dataToSend = {};
                        dataToSend.shippingCFisc = $('[name="shippingAddress.custom_attributes.c_fiscale"] [name="custom_attributes[c_fiscale]"]').val();
                        dataToSend.shippingSdiPec = $('[name="shippingAddress.custom_attributes.codice_sdi_pec"] [name="custom_attributes[codice_sdi_pec]"]').val();
                        dataToSend.billingCFisc = dataToSend.shippingCFisc;
                        dataToSend.billingSdiPec = dataToSend.shippingSdiPec;
                        var isSameAddress = $('#billing-address-same-as-shipping:checked').length == 1;
                        if (!isSameAddress) {
                            dataToSend.billingCFisc = $('[name="billingAddress.custom_attributes.c_fiscale"] [name="custom_attributes[c_fiscale]"]').val();
                            dataToSend.billingSdiPec = $('[name="billingAddress.custom_attributes.codice_sdi_pec"] [name="custom_attributes[codice_sdi_pec]"]').val();
                        }
                        $.ajax({
                            type: 'post',
                            url: window.checkout.baseUrl + 'datacom/checkout/addressdata',
                            data: dataToSend,
                            dataType: 'json',
                            cache: false,
                            success: function(resp) {
                                self._placeOrder();
                            }
                        });
                    });
                } else {
                    var offsetHeight = $(window).height() / 2,
                        errorMsgSelector = $('#maincontent .mage-error:visible:first').closest('.field');
                    errorMsgSelector = errorMsgSelector.length ? errorMsgSelector : $('#maincontent .field-error:visible:first').closest('.field');
                    if (errorMsgSelector.length) {
                        if (errorMsgSelector.find('select').length) {
                            $('html, body').scrollTop(
                                errorMsgSelector.find('select').offset().top - offsetHeight
                            );
                            errorMsgSelector.find('select').focus();
                        } else if (errorMsgSelector.find('input').length) {
                            $('html, body').scrollTop(
                                errorMsgSelector.find('input').offset().top - offsetHeight
                            );
                            errorMsgSelector.find('input').focus();
                        }
                    } else if ($('.message-error:visible').length) {
                        $('html, body').scrollTop(
                            $('.message-error:visible:first').closest('div').offset().top - offsetHeight
                        );
                    }
                }

                return this;
            },

            brainTreePaypalPlaceOrder: function () {
                var component = this.getBraintreePaypalComponent();
                if (component && additionalValidators.validate()) {
                    component.placeOrder.apply(component, arguments);
                }

                return this;
            },

            brainTreePayWithPayPal: function () {
                var self = this;
                var component = this.getBraintreePaypalComponent();
                self.isPaypalThroughBraintree = true;
                if (component && additionalValidators.validate()) {
                    component.payWithPayPal.apply(component, arguments);
                }

                return this;
            },
            preparePlaceOrder: function (scrollTop) {
                var scrollTop = scrollTop !== undefined ? scrollTop : true;
                var deferer = $.when(setCheckoutInformationAction());

                return scrollTop ? deferer.done(function () {
                    $("body").animate({scrollTop: 0}, "slow");
                }) : deferer;
            },

            getPaymentPath: function (paymentMethodCode) {
                return 'checkout.steps.billing-step.payment.payments-list.' + paymentMethodCode;
            },

            getPaymentMethodComponent: function (paymentMethodCode) {
                return registry.get(this.getPaymentPath(paymentMethodCode));
            },

            isPaymentBraintreePaypal: function () {
                return quote.paymentMethod() && quote.paymentMethod().method === 'braintree_paypal';
            },

            getBraintreePaypalComponent: function () {
                return this.getPaymentMethodComponent('braintree_paypal');
            },

            _placeOrder: function () {
                $(this.selectors.default).trigger('click');
                customerData.invalidate(['customer']);
            },

            isPlaceOrderActionAllowed: function () {
                return true;
            }
        });
    }
);