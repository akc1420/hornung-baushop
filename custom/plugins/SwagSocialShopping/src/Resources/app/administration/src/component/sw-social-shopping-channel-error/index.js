import template from './sw-social-shopping-channel-error.html.twig';
import './sw-social-shopping-channel-error.scss';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;
const { date } = Shopware.Utils.format;

Component.register('sw-social-shopping-channel-error', {
    template,

    inject: [
        'repositoryFactory',
        'socialShoppingService',
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
            socialShoppingErrors: null,
            isLoading: false,
            timeoutId: null,
        };
    },

    computed: {
        isValidating() {
            return this.salesChannel
                && this.salesChannel.extensions
                && this.salesChannel.extensions.socialShoppingSalesChannel
                && this.salesChannel.extensions.socialShoppingSalesChannel.isValidating;
        },

        lastValidation() {
            return date(
                this.salesChannel.extensions.socialShoppingSalesChannel.lastValidation,
                {
                    day: '2-digit',
                    month: '2-digit',
                    year: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                },
            );
        },

        socialShoppingProductErrorRepository() {
            return this.repositoryFactory.create('swag_social_shopping_product_error');
        },

        socialShoppingSalesChannelRepository() {
            return this.repositoryFactory.create('swag_social_shopping_sales_channel');
        },

        socialShoppingErrorColumns() {
            return [
                {
                    property: 'product',
                    label: this.$tc('swag-social-shopping.validation.columns.product'),
                },
                {
                    property: 'errors',
                    label: this.$tc('swag-social-shopping.validation.columns.errors'),
                },
            ];
        },
    },

    watch: {
        'salesChannel.extensions.socialShoppingSalesChannel': {
            deep: true,
            handler() {
                if (!this.salesChannel
                    || !this.salesChannel.extensions
                    || !this.salesChannel.extensions.socialShoppingSalesChannel) {
                    return;
                }

                this.loadEntityData();
            },
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            if (!this.salesChannel ||
                !this.salesChannel.extensions ||
                !this.salesChannel.extensions.socialShoppingSalesChannel) {
                return;
            }

            this.loadEntityData();
        },

        loadEntityData() {
            this.isLoading = true;

            const criteria = new Criteria();

            criteria.addFilter(Criteria.equals('salesChannelId', this.salesChannel.id));
            criteria.addAssociation('product');

            this.socialShoppingProductErrorRepository.search(criteria, Shopware.Context.api).then(result => {
                this.socialShoppingErrors = result;
                this.isLoading = false;
            });

            if (this.timeoutId === null) {
                this.timeoutId = window.setTimeout(this.periodicErrorLogUpdate, 15000, criteria);
            }
        },

        periodicErrorLogUpdate(criteria) {
            if ((!this.socialShoppingErrors || this.socialShoppingErrors.total === 0)
                && !this.isLoading && this.salesChannel.extensions.socialShoppingSalesChannel.isValidating) {
                this.socialShoppingProductErrorRepository.search(criteria, Shopware.Context.api).then(result => {
                    this.socialShoppingErrors = result;
                    this.isLoading = false;
                    this.$forceUpdate();
                });

                window.clearTimeout(this.timeoutId);
            }

            this.socialShoppingSalesChannelRepository.get(
                this.salesChannel.extensions.socialShoppingSalesChannel.id,
                Shopware.Context.api,
            ).then(result => {
                this.salesChannel.extensions.socialShoppingSalesChannel.isValidating = result.isValidating;
                this.salesChannel.extensions.socialShoppingSalesChannel.lastValidation = result.lastValidation;
            });

            this.timeoutId = window.setTimeout(this.periodicErrorLogUpdate, 15000, criteria);
        },

        onValidate() {
            this.salesChannel.extensions.socialShoppingSalesChannel.isValidating = true;
            this.socialShoppingErrors = null;
            this.socialShoppingService.validate(this.salesChannel.extensions.socialShoppingSalesChannel.id);

            this.$forceUpdate();
        },
    },
});
