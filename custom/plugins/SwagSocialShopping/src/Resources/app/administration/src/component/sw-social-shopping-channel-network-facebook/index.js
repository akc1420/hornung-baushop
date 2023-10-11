import template from './sw-social-shopping-channel-network-facebook.html.twig';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.extend('sw-social-shopping-channel-network-facebook', 'sw-social-shopping-channel-network-base', {
    template,

    inject: [
        'repositoryFactory',
        'acl',
    ],

    data() {
        return {
            productComparisonAccessUrl: '',
            hasProductComparisonAccessUrl: false,
            socialShoppingSalesChannelDomain: null,
            socialShoppingSalesChannelDomainLoading: false,
            socialShoppingSalesChannelExport: null,
            socialShoppingSalesChannelExportLoading: false,
        };
    },

    computed: {
        productExportRepository() {
            return this.repositoryFactory.create('product_export');
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.$super('createdComponent');

            if (this.isNewEntity) {
                this.salesChannel.extensions.socialShoppingSalesChannel.configuration.interval = 86400;
                this.salesChannel.extensions.socialShoppingSalesChannel.configuration.generateByCronjob = true;
                this.salesChannel.extensions.socialShoppingSalesChannel.configuration.includeVariants = true;

                return;
            }

            this.generateAccessUrl();
        },

        generateAccessUrl() {
            if (!this.socialShoppingSalesChannelDomain && !this.socialShoppingSalesChannelDomainLoading) {
                this.loadSalesChannelDomain(this.salesChannel.extensions.socialShoppingSalesChannel.salesChannelDomainId);
            }

            if (!this.socialShoppingSalesChannelExport && !this.socialShoppingSalesChannelExportLoading) {
                this.loadSalesChannelExport(this.salesChannel.extensions.socialShoppingSalesChannel.salesChannelId);
            }

            if (!this.socialShoppingSalesChannelDomain || !this.socialShoppingSalesChannelExport) {
                return;
            }

            const salesChannelDomainUrl = this.socialShoppingSalesChannelDomain.url.replace(/\/+$/g, '');
            const accessKey = this.socialShoppingSalesChannelExport.accessKey;
            const fileName = this.socialShoppingSalesChannelExport.fileName;
            this.hasProductComparisonAccessUrl = true;
            this.productComparisonAccessUrl =
                `${salesChannelDomainUrl}/export/${accessKey}/${fileName}`;
        },

        loadSalesChannelDomain(salesChannelDomainId) {
            const criteria = new Criteria();
            criteria.setIds([salesChannelDomainId]);

            this.socialShoppingSalesChannelDomainLoading = true;

            this.salesChannelDomainRepository.search(criteria, Shopware.Context.api).then((result) => {
                this.socialShoppingSalesChannelDomain = result.get(salesChannelDomainId);
                this.socialShoppingSalesChannelDomainLoading = false;

                this.generateAccessUrl();
            });
        },

        loadSalesChannelExport(salesChannelId) {
            const criteria = new Criteria();
            criteria.addFilter(Criteria.equals('salesChannelId', salesChannelId));

            this.socialShoppingSalesChannelExportLoading = true;

            this.productExportRepository.search(criteria, Shopware.Context.api).then((result) => {
                this.socialShoppingSalesChannelExport = result.first();
                this.socialShoppingSalesChannelExportLoading = false;

                this.generateAccessUrl();
            });
        },
    },
});
