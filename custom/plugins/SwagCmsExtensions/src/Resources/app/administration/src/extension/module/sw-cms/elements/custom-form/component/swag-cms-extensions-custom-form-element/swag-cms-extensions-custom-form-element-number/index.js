import template from './swag-cms-extensions-custom-form-element-number.html.twig';

const { Component } = Shopware;

Component.register('swag-cms-extensions-custom-form-element-number', {
    template,

    props: {
        field: {
            type: Object,
            required: true,
        },
    },

    computed: {
        value() {
            const placeholder = this.field.translated ? this.field.translated.placeholder : this.field.placeholder;
            return Number(placeholder);
        },
    },
});
