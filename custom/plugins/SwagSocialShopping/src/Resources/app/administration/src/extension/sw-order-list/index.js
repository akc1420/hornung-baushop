const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.override('sw-order-list', {
    inject: ['repositoryFactory', 'filterFactory'],

    data() {
        return {
            socialSalesChannelOptions: []
        };
    },

    computed: {
        orderCriteria() {
            const defaultCriteria = this.$super('orderCriteria');

            defaultCriteria.addAssociation('swagSocialShoppingOrder.salesChannel');

            return defaultCriteria;
        },

        listFilters() {
            const filters = this.$super('listFilters');

            const referralFilter = this.filterFactory.create('order', {
                'referral-code-filter': {
                    property: 'swagSocialShoppingOrder.referralCode',
                    label: this.$tc('swag-social-shopping.extension.sw-order.filter.referralCode.label'),
                    placeholder: this.$tc('swag-social-shopping.extension.sw-order.filter.referralCode.placeholder'),
                    type: 'multi-select-filter',
                    valueProperty: 'id',
                    labelProperty: 'name',
                    options: this.socialSalesChannelOptions,
                },
            }).pop();

            filters.push(referralFilter);

            return filters;
        },
    },

    methods: {
        createdComponent() {
            this.defaultFilters.push('referral-code-filter');
            this.getSocialSalesChannelOptions();

            return this.$super('createdComponent');
        },

        getOrderColumns() {
            const columns = this.$super('getOrderColumns');

            columns.push({
                property: 'extensions.swagSocialShoppingOrder.salesChannel.name',
                dataIndex: 'extensions.swagSocialShoppingOrder.salesChannel.name',
                naturalSorting: true,
                label: 'swag-social-shopping.extension.sw-order.list.columnReferral',
                allowResize: true,
                inlineEdit: 'string',
            });

            return columns;
        },

        getSocialSalesChannelOptions() {
            const repo = this.repositoryFactory.create('sales_channel');
            const criteria = new Criteria();

            criteria.addAssociation('type');
            criteria.addFilter(Criteria.equals('type.id', Shopware.Defaults.SalesChannelTypeSocialShopping));

            repo.search(criteria).then((result) => {
                this.socialSalesChannelOptions = result;
            });
        },
    },
});
