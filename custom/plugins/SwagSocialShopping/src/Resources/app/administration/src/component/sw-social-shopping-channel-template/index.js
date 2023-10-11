import template from './sw-social-shopping-channel-template.html.twig';
import './sw-social-shopping-channel-template.scss';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;
const { warn } = Shopware.Utils.debug;

Component.register('sw-social-shopping-channel-template', {
    template,

    inject: [
        'repositoryFactory',
        'entityMappingService',
        'productExportService',
        'socialShoppingService',
        'acl',
    ],

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('placeholder'),
    ],

    props: {
        productExport: {
            type: Object,
            required: true,
        },

        salesChannel: {
            type: Object,
            required: true,
        },

        isLoading: {
            type: Boolean,
            default: false,
        },
    },

    data() {
        return {
            isLoadingPreview: false,
            isPreviewSuccessful: false,
            previewContent: null,
            previewErrors: null,
            isLoadingValidate: false,
            isValidateSuccessful: false,
            socialShoppingErrors: null,
            isLoadingReset: false,
            isResetSuccessful: false,
        };
    },

    computed: {
        editorConfig() {
            return {
                enableBasicAutocompletion: true,
            };
        },

        productExportRepository() {
            return this.repositoryFactory.create('product_export');
        },

        socialShoppingTemplateCriteria() {
            const criteria = new Criteria();
            criteria.addFilter(Criteria.equals(
                'salesChannelId', this.salesChannel.id,
            ));

            return criteria;
        },

        outerCompleterFunctionHeader() {
            return this.outerCompleterFunction({
                productExport: 'product_export',
            });
        },

        outerCompleterFunctionBody() {
            return this.outerCompleterFunction({
                productExport: 'product_export',
                product: 'product',
                socialShoppingSalesChannel: 'swag_social_shopping_sales_channel',
            });
        },

        outerCompleterFunctionFooter() {
            return this.outerCompleterFunction({
                productExport: 'product_export',
            });
        },
    },

    methods: {
        outerCompleterFunction(mapping) {
            const entityMappingService = this.entityMappingService;

            return function completerFunction(prefix) {
                const entityMapping = entityMappingService.getEntityMapping(prefix, mapping);
                return Object.keys(entityMapping).map(val => {
                    return { value: val };
                });
            };
        },

        preview() {
            this.isLoadingPreview = true;

            return this.productExportService
                .previewProductExport(this.productExport)
                .then((data) => {
                    this.isLoadingPreview = false;
                    this.previewContent = data.content;

                    if (data.errors) {
                        this.previewErrors = data.errors;
                        return;
                    }

                    this.isPreviewSuccessful = true;
                }).catch((exception) => {
                    this.createNotificationError({
                        message: exception.response.data.errors[0].detail,
                    });
                    warn(this._name, exception.message, exception.response);

                    this.isLoadingPreview = false;
                    this.isPreviewSuccessful = false;
                });
        },

        validateTemplate() {
            const notificationValidateSuccess = {
                message: this.$tc('sw-sales-channel.detail.productComparison.notificationMessageValidateSuccessful'),
            };

            this.isLoadingValidate = true;

            this.productExportService
                .validateProductExportTemplate(this.productExport)
                .then((data) => {
                    this.isLoadingValidate = false;

                    if (data.errors) {
                        this.previewContent = data.content;
                        this.previewErrors = data.errors;
                        return;
                    }

                    this.createNotificationSuccess(notificationValidateSuccess);

                    this.isValidateSuccessful = true;
                }).catch((exception) => {
                    this.createNotificationError({
                        message: exception.response.data.errors[0].detail,
                    });
                    warn(this._name, exception.message, exception.response);

                    this.isLoadingValidate = false;
                    this.isValidateSuccessful = false;
                });
        },

        resetTemplate() {
            this.isLoadingReset = true;

            return this.socialShoppingService.reset(this.salesChannel.extensions.socialShoppingSalesChannel.id)
                .then(() => {
                    return this.productExportRepository.search(this.socialShoppingTemplateCriteria, Shopware.Context.api)
                        .then((result) => {
                            this.productExport.headerTemplate = result[0].headerTemplate;
                            this.productExport.bodyTemplate = result[0].bodyTemplate;
                            this.productExport.footerTemplate = result[0].footerTemplate;

                            this.isResetSuccessful = true;
                            this.isLoadingReset = false;
                        });
                }).catch((exception) => {
                    this.createNotificationError({
                        message: exception.response.data.errors[0].detail,
                    });
                    warn(this._name, exception.message, exception.response);

                    this.isLoadingReset = false;
                    this.isResetSuccessful = false;
                });
        },

        onPreviewClose() {
            this.previewContent = null;
            this.previewErrors = null;
            this.isPreviewSuccessful = false;
        },

        resetValid() {
            this.isValidateSuccessful = false;
        },

        resetReset() {
            this.isResetSuccessful = false;
        },
    },
});
