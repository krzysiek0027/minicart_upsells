define([
    'jquery',
    'underscore',
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'ko'
], function ($, _, Component, customerData, ko) {
    'use strict';

    return Component.extend({
        upsellProducts: ko.observableArray([]),

        /**
         * Initializes Sticky component.
         *
         * @returns {Object} Chainable.
         */
        initialize: function () {
            this._super();
            this.bindEvents();
            return this;
        },

        /**
         * Bind events
         *
         * @returns {*}
         */
        bindEvents: function () {
            this.subscribeCartUpdate();
            this.collectUpsellProducts(customerData.get('cart')());
            return this;
        },

        /**
         * Subscribe shopping cart change
         */
        subscribeCartUpdate: function () {
            var self = this,
                cart = customerData.get('cart');

            cart.subscribe(function (cartData) {
                self.collectUpsellProducts(cartData);
            });
        },

        /**
         * Collect Upsell Products
         */
        collectUpsellProducts: function (cartData) {
            var products = [],
                self = this;
            cartData.items.forEach(function (item) {
                if (item.upsell_products.length &&
                    !self.isProductInList(cartData.items, item.upsell_products[0].sku) &&
                    !self.isProductInList(products, item.upsell_products[0].sku, 'sku')
                ) {
                    products.push(item.upsell_products[0]);
                }
            });
            this.upsellProducts(products);
        },

        /**
         *
         * @param product
         */
        addToCart: function (product) {
            var productId = product.entity_id,
                formKey = $.cookieStorage.get('form_key'),
                baseUrl = window.location.origin,
                regex = /uenc\/(.*?)\/product/;

            var formData = new FormData();
            formData.append("product", productId);
            formData.append("uenc", regex.exec(product.add_to_cart_url)[1]);
            formData.append("form_key", formKey);


            $.ajax({
                url: product.add_to_cart_url,
                data: formData,
                type: 'post',
                dataType: 'json',
                cache: false,
                contentType: false,
                processData: false,

                beforeSend: function () {
                    $('body').trigger('processStart');
                },

                error: function (res) {
                    console.warn(res);
                },

                complete: function () {
                    $('body').trigger('processStop');
                }
            });
        },

        /**
         *
         * @param items
         * @param sku
         * @returns {boolean}
         */
        isProductInList: function (items, sku, key = 'product_sku') {
            return !!items.find(item => item[key] === sku);
        }
    });
});
