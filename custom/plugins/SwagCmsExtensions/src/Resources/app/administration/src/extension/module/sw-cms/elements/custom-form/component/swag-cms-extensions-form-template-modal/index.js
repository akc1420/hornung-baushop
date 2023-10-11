import template from './swag-cms-extensions-form-template-modal.html.twig';
import './swag-cms-extensions-form-template-modal.scss';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('swag-cms-extensions-form-template-modal', {
    name: 'swag-cms-extensions-form-template-modal',

    template,

    inject: [
        'repositoryFactory',
    ],

    mixins: [
        'listing',
    ],

    data() {
        return {
            displayModal: true,
            formTemplates: false,
            isLoading: false,
            limit: 5,
            searchTerm: '',
        };
    },

    computed: {
        formTemplateRepository() {
            return this.repositoryFactory.create('swag_cms_extensions_form');
        },

        columns() {
            return [
                {
                    property: 'technicalName',
                    label: 'swag-cms-extensions.sw-cms.elements.custom-form.templateListing.templateNameLabel',
                    inlineEdit: 'string',
                    align: 'left',
                },
            ];
        },
    },

    watch: {
        searchTerm() {
            this.page = 1;
            this.isLoading = true;
            this.getList()
                .then(() => {
                    this.isLoading = false;
                });
        },
    },

    methods: {
        getListCriteria() {
            const params = this.getMainListingParams();
            const criteria = new Criteria(this.page, this.limit);
            criteria.term = this.searchTerm || null;
            criteria.addFilter(Criteria.equals('isTemplate', true));
            criteria.addSorting(Criteria.sort('technicalName', params.sortDirection, params.naturalSorting));

            return criteria;
        },

        getList() {
            this.isLoading = true;

            return this.formTemplateRepository.search(this.getListCriteria(), Shopware.Context.api).then((searchResult) => {
                this.total = searchResult.total;
                this.page = searchResult.criteria.page;
                this.limit = searchResult.criteria.limit;
                this.formTemplates = searchResult;
                this.isLoading = false;
            });
        },

        onModalClose() {
            // This dismounts the sw-modal it self
            this.displayModal = false;

            // The event fired is used to dismount this component
            this.$nextTick(() => {
                this.$emit('modal-close');
            });
        },

        onInlineEditSave(promise, formTemplate) {
            this.$emit('template-updated', formTemplate);
        },

        onRename(item) {
            this.$refs.templateEntityListing.currentInlineEditId = item.id;
            this.$refs.templateEntityListing.isInlineEditActive = true;
        },
    },
});
