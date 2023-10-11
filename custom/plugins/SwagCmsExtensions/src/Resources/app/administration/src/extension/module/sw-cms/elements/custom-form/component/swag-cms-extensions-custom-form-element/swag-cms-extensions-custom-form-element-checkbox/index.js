import template from './swag-cms-extensions-custom-form-element-checkbox.html.twig';

const { Component } = Shopware;

Component.register('swag-cms-extensions-custom-form-element-checkbox', {
    template,

    props: {
        field: {
            type: Object,
            required: true,
        },
    },

    computed: {
        value() {
            if (this.field.translated && this.field.translated.config) {
                return this.field.translated.config.default;
            }

            if (this.field.config) {
                return this.field.config.default;
            }

            return false;
        },
    },
});
