import template from './swag-cms-extensions-custom-form-element-config.html.twig';
import './swag-cms-extensions-custom-form-element-config.scss';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('swag-cms-extensions-custom-form-element-config', {
    template,

    mixins: [
        'cms-element',
    ],

    data() {
        return {
            templateId: null,
            showFormTemplateModal: false,
        };
    },

    computed: {
        formTemplateCriteria() {
            const criteria = new Criteria();
            criteria.addFilter(
                Criteria.equals('isTemplate', true),
            );

            return criteria;
        },
    },

    watch: {
        templateId() {
            this.$emit('form-template-id-change', this.templateId);
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('custom-form');
        },

        closeFormTemplateModal() {
            this.templateId = null;
            this.showFormTemplateModal = false;
        },

        openFormTemplateModal() {
            this.showFormTemplateModal = true;
        },

        onTemplateUpdated(formTemplate) {
            // If the selected form template got updated reset selection
            if (formTemplate.id === this.templateId) {
                this.templateId = null;
            }
        },
    },
});
