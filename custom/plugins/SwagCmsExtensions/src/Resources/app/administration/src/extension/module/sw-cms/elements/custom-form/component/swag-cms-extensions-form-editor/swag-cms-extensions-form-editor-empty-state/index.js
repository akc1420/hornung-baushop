import template from './swag-cms-extensions-form-editor-empty-state.html.twig';
import './swag-cms-extensions-form-editor-empty-state.scss';

const { Component } = Shopware;

Component.register('swag-cms-extensions-form-editor-empty-state', {
    name: 'swag-cms-extensions-form-editor-empty-state',

    template,

    methods: {
        onStartEditor() {
            this.$emit('start-editor');
        },
    },
});
