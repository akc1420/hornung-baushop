import template from './swag-cms-extensions-form-editor-settings.html.twig';
import './swag-cms-extensions-form-editor-settings.scss';
import './field-types';
import './swag-cms-extensions-form-editor-settings-group';
import './swag-cms-extensions-form-editor-settings-group-field';

const { Component } = Shopware;
const { mapState } = Shopware.Component.getComponentHelper();

Component.register('swag-cms-extensions-form-editor-settings', {
    name: 'swag-cms-extensions-form-editor-settings',

    template,

    computed: {
        ...mapState('swCmsDetailCurrentCustomForm', {
            item: state => state.activeItem,
        }),

        showGroupSettings() {
            return this.item.getEntityName() === 'swag_cms_extensions_form_group';
        },

        fieldComponentName() {
            return `swag-cms-extensions-form-editor-settings-field-type-${this.item.type}`;
        },
    },
});
