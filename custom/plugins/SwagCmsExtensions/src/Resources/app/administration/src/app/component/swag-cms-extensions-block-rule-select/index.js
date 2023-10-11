import template from './swag-cms-extensions-block-rule-select.html.twig';

const { Component } = Shopware;

Component.extend('swag-cms-extensions-block-rule-select', 'sw-select-rule-create', {
    template,

    methods: {
        dismissSelection() {
            this.$emit('dismiss-rule');
        },
    },
});
