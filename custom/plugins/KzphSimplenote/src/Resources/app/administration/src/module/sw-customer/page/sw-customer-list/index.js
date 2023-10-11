import template from './sw-customer-list.html.twig';

const {Component} = Shopware;
const { Criteria } = Shopware.Data;

Component.override('sw-customer-list', {
    template,

    mounted() {
        this.mountedComponent();
    },

    computed: {
        defaultCriteria() {
            const criteria = this.$super('defaultCriteria');
            criteria.addAssociation('kzphSimplenote');

            if(this.sortBy == 'kzphsimplenote'){
                criteria.resetSorting();
                criteria.getAssociation('kzphSimplenote').addSorting(Criteria.sort('createdAt', this.sortDirection));
                criteria.addSorting(Criteria.sort('kzphSimplenote.createdAt', this.sortDirection));  
            }

            return criteria;
        },
        listFilters() {
            const filters = this.$super('listFilters');
            const simplenoteFilter = this.filterFactory.create('order', {
                'kzph-simplenote-filter': {
                    property: 'kzphSimplenote',
                    label: this.$tc('kzph-simplenote-note.general.title'),
                    placeholder: this.$tc('kzph-simplenote-note.general.filter.placeholder'),
                    optionHasCriteria: this.$tc('kzph-simplenote-note.general.filter.has_notes'),
                    optionNoCriteria: this.$tc('kzph-simplenote-note.general.filter.without_notes'),
                }
            });

            filters.push(simplenoteFilter[0])
            return filters;
        }
    },

    methods: {
        getCustomerColumns() {
            const columns = this.$super('getCustomerColumns');

            columns.push({
                property: 'kzphsimplenote',
                label: 'kzph-simplenote-note.general.title',
                allowResize: true,
                primary: false,
            });

            return columns;
        },
        mountedComponent() {
            const defaultFilters = this.defaultFilters;
            defaultFilters.push('kzph-simplenote-filter');
        }
    }
});
