import template from './sw-sales-channel-detail.html.twig';

const { Component } = Shopware;

Component.override('sw-sales-channel-detail', {
    template,

    inject: [
        'socialShoppingService',
        'acl'
    ],

    data() {
        return {
            isNewEntity: false,
            networkClasses: null,
            networkName: 'base',
            salesChannelData: null,
            productExportData: null,
        };
    },

    computed: {
        isSocialShopping() {
            return this.salesChannel && this.salesChannel.typeId.indexOf('9ce0868f406d47d98cfe4b281e62f098') !== -1;
        },

        socialShoppingType() {
            if (!this.salesChannel
                || !this.salesChannel.extensions
                || !this.salesChannel.extensions.socialShoppingSalesChannel
                || !this.networkClasses
            ) {
                return '';
            }

            return `sw-social-shopping-channel-network-${this.getNetworkByFQCN(
                this.salesChannel.extensions.socialShoppingSalesChannel.network,
            )}`;
        },

        shouldShowSidebar() {
            return this.salesChannel?.extensions.socialShoppingSalesChannel
                && this.salesChannelData
                && this.productExportData
                && this.isTemplateEditable(this.socialShoppingType);
        },
    },

    watch: {
        salesChannel() {
            if (this.isSocialShopping && !this.salesChannel.extensions.socialShoppingSalesChannel.configuration) {
                this.salesChannel.extensions.socialShoppingSalesChannel.configuration = {};
                this.setNetworkName();
            }

            this.$forceUpdate();
        },

        networkName() {
            this.$forceUpdate();
        },

        isSocialShopping() {
            this.salesChannelData = this.salesChannel;
            this.productExportData = this.productExport;

            this.$forceUpdate();
        },
    },

    methods: {
        createdComponent() {
            this.$super('createdComponent');

            this.socialShoppingService.getNetworks().then((networks) => {
                this.networkClasses = networks;
                this.setNetworkName();
            });
        },

        getNetworkByFQCN(fqcn) {
            return Object.keys(this.networkClasses).filter((key) => { return this.networkClasses[key] === fqcn; })[0];
        },

        setNetworkName() {
            if (!this.salesChannel
                || !this.salesChannel.extensions
                || !this.salesChannel.extensions.socialShoppingSalesChannel
                || !this.networkClasses
            ) {
                return;
            }

            this.networkName = this.getNetworkByFQCN(this.salesChannel.extensions.socialShoppingSalesChannel.network);
        },

        isTemplateEditable(socialShoppingType) {
            return socialShoppingType !== 'sw-social-shopping-channel-network-pinterest';
        },
    },
});
