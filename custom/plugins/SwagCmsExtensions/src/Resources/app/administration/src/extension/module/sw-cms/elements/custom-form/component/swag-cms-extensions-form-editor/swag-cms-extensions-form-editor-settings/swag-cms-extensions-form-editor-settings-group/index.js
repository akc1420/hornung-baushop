import template from './swag-cms-extensions-form-editor-settings-group.html.twig';
import './swag-cms-extensions-form-editor-settings-group.scss';

const { Component } = Shopware;
const { mapState, mapPropertyErrors } = Shopware.Component.getComponentHelper();

Component.register('swag-cms-extensions-form-editor-settings-group', {
    name: 'swag-cms-extensions-form-editor-settings-group',

    template,

    computed: {
        ...mapState('swCmsDetailCurrentCustomForm', {
            group: state => state.activeItem,
        }),

        ...mapPropertyErrors('group', [
            'technicalName',
        ]),
    },
});
