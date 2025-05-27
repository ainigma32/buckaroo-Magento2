/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * It is available through the world-wide-web at this URL:
 * https://tldrlegal.com/license/mit-license
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to support@buckaroo.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact support@buckaroo.nl for more information.
 *
 * @copyright Copyright (c) Buckaroo B.V.
 * @license   https://tldrlegal.com/license/mit-license
 */
/*browser:true*/
/*global define*/

define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Buckaroo_Magento2/js/action/place-order',
        'Magento_Checkout/js/model/quote',
        'ko',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/action/select-payment-method',
        'buckaroo/applepay/pay',
        'buckaroo/checkout/common',
        'BuckarooSDK'
    ],
    function (
        $,
        Component,
        additionalValidators,
        placeOrderAction,
        quote,
        ko,
        checkoutData,
        selectPaymentMethodAction,
        applepayPay,
        checkoutCommon
    ) {
        'use strict';

        return Component.extend(
            {
                defaults: {
                    template: 'Buckaroo_Magento2/payment/buckaroo_magento2_applepay_client'
                },
                currencyCode: window.checkoutConfig.quoteData.quote_currency_code,
                baseCurrencyCode: window.checkoutConfig.quoteData.base_currency_code,
                subtext: window.checkoutConfig.payment.buckaroo.applepay.subtext,
                subTextStyle: checkoutCommon.getSubtextStyle('applepay'),
                submit: false,

                initObservable: function () {
                    this._super().observe([]);
                    applepayPay.canShowApplePay();

                    applepayPay.transactionResult.subscribe(
                        function () {
                            this.submit = true;
                            this.placeOrder(null, null);
                        }.bind(this)
                    );

                    quote.totals.subscribe(
                        function () {
                            if (applepayPay.canShowApplePay()) {
                                applepayPay.updateOptions();
                            }
                        }.bind(this)
                    );

                    $(window).on('hashchange', function () {
                        var hashString = window.location.hash.replace('#', '');

                        if (hashString === 'payment' && applepayPay.canShowApplePay()) {
                            applepayPay.updateOptions();
                        }
                    }.bind(this));

                    return this;
                },

                canShowPaymentMethod: ko.computed(function () {
                    return applepayPay.canShowMethod();
                }),

                /**
                 * Place order.
                 *
                 * placeOrderAction has been changed from Magento_Checkout/js/action/place-order to our own version
                 * (Buckaroo_Magento2/js/action/place-order) to prevent redirect and handle the response.
                 */
                placeOrder: function (data, event) {
                    var self = this,
                        placeOrder;

                    if (applepayPay.isOsc()) {
                        var validationResult = additionalValidators.validate();
                        if (!validationResult) {
                            return false;
                        }
                    }

                    if (!this.submit) {
                        var child = document.querySelector('.apple-pay-button');
                        if (child) {
                            child.click();
                        }
                        return false;
                    }
                    this.submit = false;

                    if (event) {
                        event.preventDefault();
                    }

                    if (this.validate() && additionalValidators.validate()) {
                        this.isPlaceOrderActionAllowed(false);
                        placeOrder = placeOrderAction(this.getData(), this.redirectAfterPlaceOrder, this.messageContainer);

                        $.when(placeOrder).fail(
                            function () {
                                self.isPlaceOrderActionAllowed(true);
                            }
                        ).done(this.afterPlaceOrder.bind(this));

                        return true;
                    }

                    return false;
                },

                afterPlaceOrder: function () {
                    var response = window.checkoutConfig.payment.buckaroo.response;
                    if (response.RequiredAction !== undefined && response.RequiredAction.RedirectURL !== undefined) {
                        window.location.replace(response.RequiredAction.RedirectURL);
                    }
                },

                selectPaymentMethod: function () {
                    selectPaymentMethodAction(this.getData());
                    checkoutData.setSelectedPaymentMethod(this.item.method);

                    return true;
                },

                showPayButton: function () {
                    applepayPay.setIsOnCheckout(true);
                    applepayPay.setQuote(quote);
                    applepayPay.showPayButton();
                },

                payWithBaseCurrency: function () {
                    var allowedCurrencies = window.checkoutConfig.payment.buckaroo.applepay.allowedCurrencies;

                    return allowedCurrencies.indexOf(this.currencyCode) < 0;
                },

                getPayWithBaseCurrencyText: function () {
                    var text = $.mage.__('The transaction will be processed using %s.');

                    return text.replace('%s', this.baseCurrencyCode);
                },

                getData: function () {
                    var transactionResult = applepayPay.transactionResult();
                    var transactionData = this.formatTransactionResponse(transactionResult);

                    return {
                        "method": this.item.method,
                        "po_number": null,
                        "additional_data": {
                            "applepayTransaction": transactionData,
                            "billingContact": transactionResult && transactionResult.billingContact ?
                                JSON.stringify(transactionResult.billingContact) : ''
                        }
                    };
                },

                /**
                 * @param response
                 * @returns {string|null}
                 */
                formatTransactionResponse: function (response) {
                    if (null === response || 'undefined' === typeof response || !response) {
                    if (window.console && window.console.error) {
                        console.error('[Buckaroo Apple Pay Client] formatTransactionResponse: Invalid response data from Apple Pay SDK', response);
                    }
                        return null;
                    }

                    try {
                        var paymentData = response.token.paymentData;

                        if (!paymentData || !paymentData.data || !paymentData.signature) {
                        if (window.console && window.console.error) {
                            console.error('[Buckaroo Apple Pay Client] Missing required payment data fields from Apple Pay SDK', paymentData);
                        }
                            return null;
                        }

                        var formattedData = {
                            "paymentData": {
                                "version": paymentData.version,
                                "data": paymentData.data,
                                "signature": paymentData.signature,
                                "header": {
                                    "ephemeralPublicKey": paymentData.header.ephemeralPublicKey,
                                    "publicKeyHash": paymentData.header.publicKeyHash,
                                    "transactionId": paymentData.header.transactionId,
                                }
                            }
                        };

                        return JSON.stringify(formattedData);
                    } catch (error) {
                        if (window.console && window.console.error) {
                            console.error('[Buckaroo Apple Pay Client] Error formatting transaction response from Apple Pay SDK:', error);
                        }
                        return null;
                    }
                }
            }
        );
    }
);
