import template from './swag-cms-extensions-form-editor.html.twig';
import './swag-cms-extensions-form-editor.scss';
import './swag-cms-extensions-form-editor-empty-state';
import './swag-cms-extensions-form-editor-group';
import './swag-cms-extensions-form-editor-group-field';
import './swag-cms-extensions-form-editor-settings';

const { Component } = Shopware;
const { mapState } = Shopware.Component.getComponentHelper();

Component.register('swag-cms-extensions-form-editor', {
    name: 'swag-cms-extensions-form-editor',

    template,

    mixins: [
        'cms-state',
    ],

    computed: {
        ...mapState('swCmsDetailCurrentCustomForm', {
            groups: state => state.form.groups,
        }),
    },

    methods: {
        onGroupAdd() {
            this.$emit('add-group');
        },
    },
});
