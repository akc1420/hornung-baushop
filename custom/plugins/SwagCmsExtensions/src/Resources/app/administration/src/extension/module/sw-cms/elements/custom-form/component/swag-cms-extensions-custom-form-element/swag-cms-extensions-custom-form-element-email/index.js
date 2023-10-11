import template from './swag-cms-extensions-custom-form-element-email.html.twig';

const { Component } = Shopware;

Component.register('swag-cms-extensions-custom-form-element-email', {
    template,

    props: {
        field: {
            type: Object,
            required: true,
        },
    },
});
