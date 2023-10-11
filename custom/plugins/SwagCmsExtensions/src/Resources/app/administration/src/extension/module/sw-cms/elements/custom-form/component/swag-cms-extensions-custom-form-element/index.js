import template from './swag-cms-extensions-custom-form-element.html.twig';
import './swag-cms-extensions-custom-form-element.scss';
import './swag-cms-extensions-custom-form-element-checkbox';
import './swag-cms-extensions-custom-form-element-email';
import './swag-cms-extensions-custom-form-element-number';
import './swag-cms-extensions-custom-form-element-select';
import './swag-cms-extensions-custom-form-element-text';
import './swag-cms-extensions-custom-form-element-textarea';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('swag-cms-extensions-custom-form-element', {
    template,

    inject: [
        'repositoryFactory',
    ],

    mixins: [
        'cms-element',
    ],

    data() {
        return {
            loadedGroups: [],
        };
    },

    computed: {
        groupRepository() {
            return this.repositoryFactory.create('swag_cms_extensions_form_group');
        },

        showPreview() {
            return this.groups.length !== 0;
        },

        slotHasFormExtension() {
            return Shopware.Utils.object.hasOwnProperty(this.element, 'extensions') &&
                Shopware.Utils.object.hasOwnProperty(this.element.extensions, 'swagCmsExtensionsForm');
        },

        slotHasGroups() {
            return this.slotHasFormExtension &&
                Shopware.Utils.object.hasOwnProperty(this.element.extensions.swagCmsExtensionsForm, 'groups') &&
                this.element.extensions.swagCmsExtensionsForm.groups.length > 0;
        },

        groups() {
            if (this.slotHasGroups) {
                return this.element.extensions.swagCmsExtensionsForm.groups;
            }

            return this.loadedGroups;
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.loadForm();
        },

        loadForm() {
            this.loadedGroups = [];

            if (this.slotHasFormExtension && this.element.extensions.swagCmsExtensionsForm.id !== null) {
                const criteria = new Criteria();
                criteria.addFilter(
                    Criteria.equals('formId', this.element.extensions.swagCmsExtensionsForm.id),
                );
                criteria.addAssociation('fields');
                criteria.addSorting(Criteria.sort('position'));
                criteria.getAssociation('fields').addSorting(Criteria.sort('position'));

                this.groupRepository.search(criteria, Shopware.Context.api).then((result) => {
                    this.loadedGroups = result;
                });
            }
        },

        getFieldClass(field) {
            return [`swag-cms-extensions-custom-form-element__field-col-${field.width}`];
        },

        getFieldComponent(field) {
            const componentName = `swag-cms-extensions-custom-form-element-${field.type}`;

            if (!Component.getComponentRegistry().has(componentName)) {
                return null;
            }

            return componentName;
        },
    },
});
