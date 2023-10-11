import template from './swag-cms-extensions-custom-form-element-select.html.twig';

const { Component } = Shopware;

Component.register('swag-cms-extensions-custom-form-element-select', {
    template,

    props: {
        field: {
            type: Object,
            required: true,
        },
    },
});
