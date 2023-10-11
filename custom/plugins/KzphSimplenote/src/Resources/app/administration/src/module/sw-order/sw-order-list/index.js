import template from './sw-order-list.html.twig';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.override('sw-order-list', {
    template,

    mounted() {
        this.mountedComponent();
    },

    computed: {
        orderCriteria() {
            const criteria = this.$super('orderCriteria');
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
        getOrderColumns() {
            const columns = this.$super('getOrderColumns');

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
