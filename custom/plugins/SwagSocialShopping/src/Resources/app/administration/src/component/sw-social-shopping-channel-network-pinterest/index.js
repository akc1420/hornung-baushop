const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.extend('sw-social-shopping-channel-network-pinterest', 'sw-social-shopping-channel-network-base', {
    inject: [
        'repositoryFactory',
    ],

    data() {
        return {
            socialShoppingSalesChannelDomain: null,
            socialShoppingSalesChannelDomainLoading: false,
            socialShoppingSalesChannelProduct: null,
            socialShoppingSalesChannelProductLoading: false,
            requiresProductStream: false,
            requiresCurrency: false,
            showValidationOption: false,
        };
    },

    computed: {
        salesChannelProductRepository() {
            return this.repositoryFactory.create('product');
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.$super('createdComponent');
            this.loadSalesChannelDomain(this.salesChannel.extensions.socialShoppingSalesChannel.salesChannelDomainId);
        },

        loadSalesChannelDomain(salesChannelDomainId) {
            if (this.socialShoppingSalesChannelDomain || this.socialShoppingSalesChannelDomainLoading) {
                return;
            }

            const criteria = new Criteria();
            criteria.setIds([salesChannelDomainId]);

            this.socialShoppingSalesChannelDomainLoading = true;

            this.salesChannelDomainRepository.search(criteria, Shopware.Context.api).then((result) => {
                this.socialShoppingSalesChannelDomain = result.get(salesChannelDomainId);
                this.socialShoppingSalesChannelDomainLoading = false;

                this.loadSalesChannelProduct();
            });
        },

        loadSalesChannelProduct() {
            if (this.socialShoppingSalesChannelProduct
                || this.socialShoppingSalesChannelProductLoading
                || !this.socialShoppingSalesChannelDomain) {
                return;
            }

            const criteria = new Criteria();
            criteria.addAssociation('seoUrls');
            criteria.getAssociation('seoUrls').addFilter(
                Criteria.equals(
                    'salesChannelId',
                    this.socialShoppingSalesChannelDomain.salesChannelId,
                ),
            );
            criteria.addFilter(
                Criteria.multi('and', [
                    Criteria.range('product.visibilities.visibility', { gte: 30 }),
                    Criteria.equals(
                        'product.visibilities.salesChannelId',
                        this.socialShoppingSalesChannelDomain.salesChannelId,
                    ),
                ]),
            );
            criteria.addFilter(Criteria.equals('product.active', true));
            criteria.setLimit(1);

            this.socialShoppingSalesChannelProductLoading = true;

            this.salesChannelProductRepository.search(criteria, Shopware.Context.api).then((result) => {
                this.socialShoppingSalesChannelProductLoading = false;
                this.socialShoppingSalesChannelProduct = result.first();
            });
        },

        openPinterestValidation() {
            const pinterestUrl = 'https://developers.pinterest.com/tools/url-debugger/';
            const salesChannelDomain = this.socialShoppingSalesChannelDomain.url;
            const productPath = this.socialShoppingSalesChannelProduct.seoUrls.first().pathInfo;
            window.open(
                `${pinterestUrl}?link=${salesChannelDomain}${productPath}`,
                '_blank',
            );
        },
    },
});
