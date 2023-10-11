import template from './sw-property-detail.html.twig';

const { Criteria } = Shopware.Data;
// Override Property Detail Page
Shopware.Component.override('sw-property-detail', {
    template,

    data() {
        return {
            propertyGroup: null,
            isLoading: false,
            isSaveSuccessful: false,
            customFieldSets: []
        };
    },

    computed: {
        customFieldSetStore() {
            return this.repositoryFactory.create('custom_field_set');
            //return StateDeprecated.getStore('custom_field_set');
        }
    },

    methods: {
        loadEntityData() {
            this.isLoading = true;

            this.propertyRepository.get(this.groupId, Shopware.Context.api, this.defaultCriteria)
                .then((currentGroup) => {
                    this.propertyGroup = currentGroup;
                    this.isLoading = false;
                }).catch(() => {
                this.isLoading = false;
            });

            const criteria = new Criteria();
            criteria.addFilter(Criteria.equals('relations.entityName', 'property_group'));

            this.customFieldSetStore
                .search(criteria, Shopware.Context.api)
                .then((result) => {
                    this.customFieldSets = result.filter(set => set.customFields.length > 0);
                    this.isLoading = false;
                });
        },
    }

});
