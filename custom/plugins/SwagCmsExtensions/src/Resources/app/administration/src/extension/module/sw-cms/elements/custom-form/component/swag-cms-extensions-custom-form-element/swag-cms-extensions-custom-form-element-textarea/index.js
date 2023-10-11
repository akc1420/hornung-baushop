import template from './swag-cms-extensions-custom-form-element-textarea.html.twig';

const { Component } = Shopware;

Component.register('swag-cms-extensions-custom-form-element-textarea', {
    template,

    props: {
        field: {
            type: Object,
            required: true,
        },
    },
});
