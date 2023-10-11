import template from './swag-cms-extensions-form-editor-settings-field-type-base.html.twig';

const { Component } = Shopware;
const { mapState } = Shopware.Component.getComponentHelper();

Component.register('swag-cms-extensions-form-editor-settings-field-type-base', {
    name: 'swag-cms-extensions-form-editor-settings-field-type-base',

    template,

    computed: {
        ...mapState('swCmsDetailCurrentCustomForm', {
            field: state => state.activeItem,
        }),
    },
});
