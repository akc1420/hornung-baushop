import template from './sw-sales-channel-detail-base.html.twig';

const { Component } = Shopware;

Component.override('sw-sales-channel-detail-base', {
    template,

    props: {
        isNewEntity: {
            type: Boolean,
        },

        isSocialShopping: {
            type: Boolean,
        },

        socialShoppingType: {
            type: String,
            default: '',
        },
    },

    watch: {
        socialShoppingType() {
            this.$forceUpdate();
        },
    },
});
