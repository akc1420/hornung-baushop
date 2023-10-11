import template from './sw-product-variants-delivery-display.html.twig';
import './sw-product-variants-delivery-display.scss';

const { Component, StateDeprecated } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('sw-product-variants-delivery-display', {
    template,

    inject: [
        'repositoryFactory'
    ],

    props: {
        product: {
            type: Object,
            required: true
        },

        selectedGroups: {
            type: Array,
            required: true
        }
    },

    data() {
        return {
            activeGroup: {},
            isActiveGroupInListing: false,
            isLoading: false,
            customFields: {},
            isSaveSuccessful: false
        };
    },

    computed: {

        customFieldRepository() {
            return this.repositoryFactory.create('custom_field');
        },

        propertyRepository() {
            return this.repositoryFactory.create('property_group');
        },

        selectedGroupsSorted() {
            // prepare group sorting
            let sortedGroups = [];
            const selectedGroupsCopy = [...this.selectedGroups];

            // check if sorting exists on server
            if (this.product.configuratorGroupConfig && this.product.configuratorGroupConfig.length > 0) {
                // add server sorting to the sortedGroups
                sortedGroups = this.product.configuratorGroupConfig.reduce((acc, configGroup) => {
                    const relatedGroup = selectedGroupsCopy.find(group => group.id === configGroup.id);

                    if (relatedGroup) {
                        acc.push(relatedGroup);

                        // remove from orignal array
                        selectedGroupsCopy.splice(selectedGroupsCopy.indexOf(relatedGroup), 1);
                    }

                    return acc;
                }, []);
            }

            // add non sorted groups at the end of the sorted array
            sortedGroups = [...sortedGroups, ...selectedGroupsCopy];

            return sortedGroups;
        },

        activeOptions() {
            return this.product.configuratorSettings.filter((element) => {
                return !element.isDeleted && element.option.groupId === this.activeGroup.id;
            });
        }
    },

    created() {
        this.createdComponent();
    },

    watch: {
        activeGroup: {
            handler() {

                if(!this.activeGroup.customFields) {
                    this.activeGroup.customFields = {};
                }

                if (!this.product.configuratorGroupConfig) {
                    return;
                }

                const activeGroupConfig = this.product.configuratorGroupConfig.find((group) => {
                    return group.id === this.activeGroup.id;
                });

                this.isActiveGroupInListing = activeGroupConfig ? activeGroupConfig.expressionForListings : false;
            }
        }
    },

    methods: {
        createdComponent() {
            this.loadEntityData();
        },

        loadEntityData() {
            this.isLoading = true;

            if(this.activeGroup.id) {
                this.propertyRepository.get(this.activeGroup.id, Shopware.Context.api).then((response) => {
                    this.activeGroup = response;
                });
            }
            const criteria = new Criteria();
            criteria.addFilter(Criteria.equals('name', 'dev24_variants_property_group_display_type'));
            this.customFieldRepository
                .search(criteria, Shopware.Context.api)
                .then((result) => {
                    this.customFields = result;
                    this.isLoading = false;
                });
        },

        onChange() {
            this.isSaveSuccessful = false;
            this.isLoading = true;

            return this.propertyRepository.save(this.activeGroup, Shopware.Context.api).then(() => {
                this.isLoading = false;
                this.isSaveSuccessful = true;
                this.loadEntityData();
            }).catch((exception) => {
                this.isLoading = false;
                throw exception;
            });
        },

    }
});
