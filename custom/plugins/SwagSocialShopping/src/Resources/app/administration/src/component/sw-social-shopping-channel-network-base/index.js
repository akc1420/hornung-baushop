import template from './sw-social-shopping-channel-network-base.html.twig';
import './sw-social-shopping-channel-network-base.scss';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;
const { mapPropertyErrors } = Component.getComponentHelper();

Component.register('sw-social-shopping-channel-network-base', {
    template,

    inject: [
        'salesChannelService',
        'socialShoppingService',
        'repositoryFactory',
        'acl',
    ],

    mixins: [
        Mixin.getByName('placeholder'),
    ],

    props: {
        salesChannel: {
            type: Object,
            required: true,
        },

        isLoading: {
            type: Boolean,
            default: false,
        },

        isNewEntity: {
            type: Boolean,
            default: false,
        },
    },

    data() {
        return {
            socialShoppingStorefrontSalesChannelId: null,
            showDeleteModal: false,
            requiresProductStream: true,
            requiresCurrency: true,
            showValidationOption: true,
            isDisabled: false,
            productStreamsChecking: true,
            disableGenerateByCronjob: false,
        };
    },

    computed: {
        ...mapPropertyErrors('salesChannel', ['name']),

        storefrontSalesChannelCriteria() {
            const criteria = new Criteria();

            return criteria.addFilter(Criteria.equals('typeId', '8a243080f92e4c719546314b577cf82b'));
        },

        storefrontSalesChannelDomainCriteria() {
            const criteria = new Criteria();

            if (!this.socialShoppingStorefrontSalesChannelId) {
                return criteria;
            }

            return criteria.addFilter(Criteria.equals('salesChannelId', this.socialShoppingStorefrontSalesChannelId));
        },

        storefrontSalesChannelDomainCurrencyCriteria() {
            const criteria = new Criteria();

            criteria.addAssociation('salesChannels');

            if (!this.socialShoppingStorefrontSalesChannelId) {
                return criteria;
            }

            return criteria.addFilter(Criteria.equals('salesChannels.id', this.socialShoppingStorefrontSalesChannelId));
        },

        globalDomainRepository() {
            return this.repositoryFactory.create('sales_channel_domain');
        },

        productStreamRepository() {
            return this.repositoryFactory.create('product_stream');
        },

        salesChannelRepository() {
            return this.repositoryFactory.create('sales_channel');
        },

        salesChannelDomainRepository() {
            return this.repositoryFactory.create('sales_channel_domain');
        },

        intervalOptions() {
            const intervals = [
                0, 120, 300, 600,
                900, 1800, 3600, 7200,
                14400, 28800, 43200, 86400,
                172800, 259200, 345600, 432000,
                518400, 604800,
            ];
            return intervals.map((value) => ({
                id: value,
                name: this.$tc(`sw-sales-channel.detail.productComparison.intervalLabels.${value}`),
            }));
        },

        salesChannelFavoritesService() {
            return Shopware.Service('salesChannelFavorites');
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.checkForProductStreams();

            if (this.salesChannel.extensions.socialShoppingSalesChannel.salesChannelDomainId) {
                const criteria = new Criteria();
                criteria.setIds([this.salesChannel.extensions.socialShoppingSalesChannel.salesChannelDomainId]);

                this.globalDomainRepository.search(criteria, Shopware.Context.api).then((result) => {
                    this.socialShoppingStorefrontSalesChannelId = result.first().salesChannelId;
                });
            }
        },

        checkForProductStreams() {
            const criteria = new Criteria();

            this.productStreamRepository.searchIds(criteria, Shopware.Context.api).then((result) => {
                this.isDisabled = result.total === 0;
                this.productStreamsChecking = false;
            });
        },

        generateKey() {
            this.salesChannelService.generateKey().then((response) => {
                this.salesChannel.accessKey = response.accessKey;
            }).catch(() => {
                this.createNotificationError({
                    title: this.$tc('sw-sales-channel.detail.titleAPIError'),
                    message: this.$tc('sw-sales-channel.detail.messageAPIError'),
                });
            });
        },

        onStorefrontSelectionChange(storefrontSalesChannelId) {
            this.salesChannelRepository.get(storefrontSalesChannelId, Shopware.Context.api).then((entity) => {
                this.salesChannel.languageId = entity.languageId;
                this.salesChannel.languages.length = 0;
                this.salesChannel.languages.push({
                    id: entity.languageId,
                });
                this.salesChannel.currencyId = entity.currencyId;
                this.salesChannel.paymentMethodId = entity.paymentMethodId;
                this.salesChannel.shippingMethodId = entity.shippingMethodId;
                this.salesChannel.countryId = entity.countryId;
                this.salesChannel.navigationCategoryId = entity.navigationCategoryId;
                this.salesChannel.navigationCategoryVersionId = entity.navigationCategoryVersionId;
                this.salesChannel.customerGroupId = entity.customerGroupId;

                this.salesChannel.extensions.socialShoppingSalesChannel.salesChannelDomainId = null;

                if (this.requiresCurrency) {
                    this.salesChannel.extensions.socialShoppingSalesChannel.currencyId = entity.currencyId;
                }

                if (!this.salesChannel.accessKey) {
                    this.generateKey();
                }
                this.forceUpdate();
            });
        },

        onCloseDeleteModal() {
            this.showDeleteModal = false;
        },

        onConfirmDelete() {
            this.showDeleteModal = false;

            this.$nextTick(() => {
                this.deleteSalesChannel(this.salesChannel.id);
                this.$router.push({ name: 'sw.dashboard.index' });
            });
        },

        deleteSalesChannel(salesChannelId) {
            this.salesChannelRepository.delete(salesChannelId, Shopware.Context.api).then(() => {
                this.$root.$emit('sales-channel-change');

                if (this.supportsFavorites()) {
                    this.salesChannelFavoritesService.refresh();
                }
            });
        },

        forceUpdate() {
            this.$forceUpdate();
        },

        changeInterval() {
            const socialShoppingSalesChannel = this.salesChannel.extensions.socialShoppingSalesChannel;
            this.disableGenerateByCronjob = socialShoppingSalesChannel.configuration.interval === 0;

            if (this.disableGenerateByCronjob) {
                this.salesChannel.extensions.socialShoppingSalesChannel.configuration.generateByCronjob = false;
            }
        },

        onGoogleProductCategoryChanged(value) {
            let parsedValue = parseInt(value, 10);
            if (parsedValue <= 0 || Number.isNaN(parsedValue)) {
                parsedValue = null;
            }

            this.salesChannel.extensions.socialShoppingSalesChannel.configuration.defaultGoogleProductCategory = parsedValue;
            this.$forceUpdate();
        },

        isFavorite() {
            if (!this.supportsFavorites()) {
                return false;
            }

            return this.salesChannelFavoritesService.isFavorite(this.salesChannel.id);
        },

        supportsFavorites() {
            return this.salesChannelFavoritesService !== undefined;
        },
    },
});
