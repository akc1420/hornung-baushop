import template from '../sw-sales-channel-detail/sw-sales-channel-detail.html.twig';

const { Component } = Shopware;

Component.override('sw-sales-channel-create', {
    template,

    inject: [
        'repositoryFactory',
    ],

    data() {
        return {
            isNewEntity: true,
        };
    },

    computed: {
        socialShoppingNetworkName() {
            return this.salesChannel.typeId.substr(this.salesChannel.typeId.indexOf('-') + 1);
        },

        socialShoppingType() {
            return `sw-social-shopping-channel-network-${this.socialShoppingNetworkName}`;
        },

        socialShoppingSalesChannelRepository() {
            return this.repositoryFactory.create('swag_social_shopping_sales_channel');
        },
    },

    watch: {
        networkClasses() {
            if (this.isSocialShopping) {
                this.salesChannel.extensions.socialShoppingSalesChannel.network
                    = this.networkClasses[this.socialShoppingNetworkName];
            }
        },
    },

    methods: {
        createdComponent() {
            this.$super('createdComponent');

            if (this.isSocialShopping) {
                this.salesChannel.extensions.socialShoppingSalesChannel
                    = this.socialShoppingSalesChannelRepository.create(Shopware.Context.api);
                this.salesChannel.extensions.socialShoppingSalesChannel.configuration = {};
                this.salesChannel.extensions.socialShoppingSalesChannel.isValidating = false;
            }
        },

        onSave() {
            this.$super('onSave');
        },
    },
});
