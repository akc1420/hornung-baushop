import template from './sw-social-shopping-channel-integration.html.twig';
import './sw-social-shopping-channel-integration.scss';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('sw-social-shopping-channel-integration', {
    template,

    inject: [
        'socialShoppingService',
        'repositoryFactory',
        'acl',
    ],

    props: {
        salesChannel: {
            type: Object,
            required: false,
            default: null,
        },
    },

    data() {
        return {
            productComparisonAccessUrl: '',
            salesChannelDomain: null,
            isLoading: true,
            salesChannelName: '',
        };
    },

    computed: {
        stepCount() {
            return parseInt(this.$tc(`${this.snippetPrefix()}.stepByStep.stepCount`), 10);
        },

        salesChannelDomainRepository() {
            return this.repositoryFactory.create('sales_channel_domain');
        },

        productExportRepository() {
            return this.repositoryFactory.create('product_export');
        },

        salesChannelProductRepository() {
            return this.repositoryFactory.create('product');
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.loadDataFeed();
        },

        loadDataFeed() {
            this.socialShoppingService.getNetworks().then((networks) => {
                this.salesChannelName =
                    Object.entries(networks).find(({ 1: network }) => {
                        return network === this.salesChannel.extensions.socialShoppingSalesChannel.network;
                    })[0];

                this.generateAccessUrl();
            });
        },

        generateAccessUrl() {
            const { salesChannelDomainId, salesChannelId } = this.salesChannel.extensions.socialShoppingSalesChannel;

            Promise.all([
                this.loadSalesChannelDomain(salesChannelDomainId),
                this.loadSalesChannelExport(salesChannelId),
            ]).then(({ 0: _salesChannelDomain, 1: _salesChannelExport }) => {
                this.salesChannelDomain = _salesChannelDomain.get(salesChannelDomainId);

                if (this.salesChannelName !== 'pinterest') {
                    const salesChannelExport = _salesChannelExport.first();
                    const salesChannelDomainUrl = this.salesChannelDomain.url.replace(/\/+$/g, '');
                    const { accessKey, fileName } = salesChannelExport;

                    this.productComparisonAccessUrl =
                        `${salesChannelDomainUrl}/store-api/product-export/${accessKey}/${fileName}`;
                }

                this.isLoading = false;
            });
        },

        loadSalesChannelDomain(salesChannelDomainId) {
            const criteria = new Criteria();
            criteria.setIds([salesChannelDomainId]);

            return this.salesChannelDomainRepository.search(criteria, Shopware.Context.api);
        },

        loadSalesChannelExport(salesChannelId) {
            const criteria = new Criteria();
            criteria.addFilter(Criteria.equals('salesChannelId', salesChannelId));

            return this.productExportRepository.search(criteria, Shopware.Context.api);
        },

        snippetPrefix() {
            return `swag-social-shopping.integration.${this.salesChannelName}`;
        },

        openPinterestValidation() {
            const pinterestUrl = 'https://developers.pinterest.com/tools/url-debugger/';
            const salesChannelDomainUrl = this.salesChannelDomain.url;

            const criteria = new Criteria();
            criteria.getAssociation('seoUrls').addFilter(
                Criteria.equals(
                    'salesChannelId',
                    this.salesChannelDomain.salesChannelId,
                ),
            );
            criteria.addFilter(Criteria.range('product.visibilities.visibility', { gte: 30 }));
            criteria.addFilter(
                Criteria.equals(
                    'product.visibilities.salesChannelId',
                    this.salesChannelDomain.salesChannelId,
                ),
            );
            criteria.addFilter(Criteria.equals('product.active', true));
            criteria.setLimit(1);

            this.salesChannelProductRepository.search(criteria, Shopware.Context.api).then((result) => {
                const productPath = result.first().seoUrls.first().pathInfo;

                window.open(
                    `${pinterestUrl}?link=${salesChannelDomainUrl}${productPath}`,
                    '_blank',
                );
            });
        },
    },
});
