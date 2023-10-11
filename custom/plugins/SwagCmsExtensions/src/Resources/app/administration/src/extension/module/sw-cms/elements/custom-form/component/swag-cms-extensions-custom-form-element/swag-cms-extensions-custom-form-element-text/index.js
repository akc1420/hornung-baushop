import template from './swag-cms-extensions-custom-form-element-text.html.twig';

const { Component } = Shopware;

Component.register('swag-cms-extensions-custom-form-element-text', {
    template,

    props: {
        field: {
            type: Object,
            required: true,
        },
    },
});
