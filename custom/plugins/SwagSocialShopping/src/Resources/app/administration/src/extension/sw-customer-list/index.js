const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.override('sw-customer-list', {
    inject: ['repositoryFactory', 'filterFactory'],

    data() {
        return {
            socialSalesChannelOptions: []
        };
    },

    computed: {
        defaultCriteria() {
            const defaultCriteria = this.$super('defaultCriteria');

            defaultCriteria.addAssociation('swagSocialShoppingCustomer.salesChannel');

            return defaultCriteria;
        },

        listFilters() {
            const filters = this.$super('listFilters');

            const referralFilter = this.filterFactory.create('customer', {
                'referral-code-filter': {
                    property: 'swagSocialShoppingCustomer.referralCode',
                    label: this.$tc('swag-social-shopping.extension.sw-customer.filter.referralCode.label'),
                    placeholder: this.$tc('swag-social-shopping.extension.sw-customer.filter.referralCode.placeholder'),
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

        getCustomerColumns() {
            const columns = this.$super('getCustomerColumns');

            columns.push({
                property: 'extensions.swagSocialShoppingCustomer.salesChannel.name',
                dataIndex: 'extensions.swagSocialShoppingCustomer.salesChannel.name',
                naturalSorting: true,
                label: 'swag-social-shopping.extension.sw-customer.list.columnReferral',
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
