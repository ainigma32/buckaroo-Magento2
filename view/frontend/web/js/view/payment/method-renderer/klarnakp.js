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
        'ko',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/action/select-payment-method',
        'buckaroo/checkout/common',
        'Magento_Checkout/js/model/quote'
    ],
    function (
        $,
        Component,
        additionalValidators,
        placeOrderAction,
        ko,
        checkoutData,
        selectPaymentMethodAction,
        checkoutCommon,
        quote
    ) {
        'use strict';

        return Component.extend(
            {
                defaults: {
                    template: 'Buckaroo_Magento2/payment/buckaroo_magento2_klarnakp'
                },
                redirectAfterPlaceOrder: false,
                paymentFeeLabel: window.checkoutConfig.payment.buckaroo.klarnakp.paymentFeeLabel,
                subtext: window.checkoutConfig.payment.buckaroo.klarnakp.subtext,
                subTextStyle: checkoutCommon.getSubtextStyle('klarnakp'),
                currencyCode: window.checkoutConfig.quoteData.quote_currency_code,
                baseCurrencyCode: window.checkoutConfig.quoteData.base_currency_code,
                showFinancialWarning: window.checkoutConfig.payment.buckaroo.klarnakp.showFinancialWarning || true,

                getMessageText: function () {
                    return $.mage
                        .__('Je moet minimaal 18+ zijn om deze dienst te gebruiken. Als je op tijd betaalt, voorkom je extra kosten en zorg je dat je in de toekomst nogmaals gebruik kunt maken van de diensten van Achteraf betalen via ' +
                            this.getTitle() +
                            '. Door verder te gaan, accepteer je de <a target="_blank" href="%s">Algemene&nbsp;Voorwaarden</a> en bevestig je dat je de <a target="_blank" href="%f">Privacyverklaring</a> en <a target="_blank" href="%c">Cookieverklaring</a> hebt gelezen.')
                        .replace('%s', 'https://cdn.klarna.com/1.0/shared/content/legal/terms/EID/nl_nl/invoice')
                        .replace('%f', 'https://cdn.klarna.com/1.0/shared/content/legal/terms/0/nl_nl/privacy')
                        .replace('%c', 'https://cdn.klarna.com/1.0/shared/content/legal/terms/nl-NL/cookie_purchase');
                },

                initObservable: function () {
                    this._super();
                    this.showFinancialWarning = ko.computed(
                        function () {
                            return quote.billingAddress() !== null &&
                                quote.billingAddress().countryId == 'NL' &&
                                window.checkoutConfig.payment.buckaroo.klarnakp.showFinancialWarning
                        },
                        this
                    );

                    return this;
                },

                /**
                 * Place order.
                 *
                 * placeOrderAction has been changed from Magento_Checkout/js/action/place-order to our own version
                 * (Buckaroo_Magento2/js/action/place-order) to prevent redirect and handle the response.
                 */
                placeOrder: function (data, event) {
                    var self = this,
                        placeOrder;

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
                    checkoutCommon.redirectHandle(response);
                },

                selectPaymentMethod: function () {
                    selectPaymentMethodAction(this.getData());
                    checkoutData.setSelectedPaymentMethod(this.item.method);
                    return true;
                },

                payWithBaseCurrency: function () {
                    var allowedCurrencies = window.checkoutConfig.payment.buckaroo.klarnakp.allowedCurrencies;

                    return allowedCurrencies.indexOf(this.currencyCode) < 0;
                },

                getPayWithBaseCurrencyText: function () {
                    var text = $.mage.__('The transaction will be processed using %s.');

                    return text.replace('%s', this.baseCurrencyCode);
                }
            }
        );
    }
);
