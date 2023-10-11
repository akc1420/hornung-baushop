import template from '../sw-sales-channel-detail-base/sw-sales-channel-detail-base.html.twig';

const { Component } = Shopware;

Component.override('sw-sales-channel-create-base', {
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
});
