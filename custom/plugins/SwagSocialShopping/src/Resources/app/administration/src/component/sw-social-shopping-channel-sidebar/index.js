import template from './sw-social-shopping-channel-sidebar.html.twig';
import './sw-social-shopping-channel-sidebar.scss';

const { Component, Mixin, EntityDefinition, Context, Utils } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('sw-social-shopping-channel-sidebar', {
    template,

    inject: [
        'repositoryFactory',
        'entityMappingService',
        'socialShoppingService',
    ],

    mixins: [
        Mixin.getByName('notification'),
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
            availableVariablesArray: null,
            productExportVariables: null,
            socialShoppingVariables: null,
            productVariables: null,
            availableVariables: {},
            entitySchema: Object.fromEntries(EntityDefinition.getDefinitionRegistry()),
        };
    },

    computed: {
        productExportRepository() {
            return this.repositoryFactory.create('product_export');
        },

        socialShoppingSalesChannelRepository() {
            return this.repositoryFactory.create('swag_social_shopping_sales_channel');
        },

        productRepository() {
            return this.repositoryFactory.create('product');
        },

        socialShoppingTemplateCriteria() {
            const criteria = new Criteria();
            criteria.addFilter(Criteria.equals(
                'salesChannelId', this.salesChannel.id,
            ));
            criteria.addAssociation('salesChannel');
            criteria.addAssociation('salesChannelDomain');

            return criteria;
        },

        socialShoppingCriteria() {
            const criteria = new Criteria();
            criteria.addFilter(Criteria.equals(
                'salesChannelId', this.salesChannel.id,
            ));

            return criteria;
        },

        productVariableCriteria() {
            const criteria = new Criteria();
            criteria.addFilter(Criteria.equals('streamIds', this.productExport.productStreamId));
            criteria.addAssociation('media');
            criteria.addAssociation('options.group');

            return criteria;
        },

        loadedAvailableVariables() {
            if (!this.availableVariablesArray) {
                return [];
            }
            if (Object.values(this.availableVariables).length === 0) {
                this.loadInitialAvailableVariables();
            }
            return Object.values(this.availableVariables);
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.loadEntityData();
        },

        loadEntityData() {
            this.productExportRepository.search(this.socialShoppingTemplateCriteria, Context.api)
                .then((productExportResult) => {
                    this.productExportVariables = productExportResult[0];
                    this.socialShoppingSalesChannelRepository.search(this.socialShoppingCriteria, Context.api)
                        .then((salesChannelResult) => {
                            this.socialShoppingVariables = salesChannelResult[0];
                            this.getProductVariables();
                            this.loadAvailableVariableData();
                        });
                });
        },

        loadAvailableVariableData() {
            this.availableVariablesArray = {
                productExport: this.productExportVariables,
                product: this.productVariables,
                socialShoppingSalesChannel: this.socialShoppingVariables,
                context: this.buildExampleContext(),
            };
        },

        loadAvailableVariables(variable, variableEntitySchema) {
            const variablePath = variable.concat('.');
            const variableEntitySchemaPath = variableEntitySchema.concat('.');

            const foundVariables = Object.keys(Utils.get(this.availableVariablesArray, variable));

            const keys = foundVariables.map((val) => {
                const availableVariable = Utils.get(this.availableVariablesArray, variablePath.concat(val));
                const isObject = typeof availableVariable === 'object' && availableVariable !== null;
                const length = isObject ? Object.values(availableVariable).length : 0;

                // the pattern for schema is `.at(0)` or `.at(1)` instead of `.0` or `.1`
                const schema = this.isToManyAssociationVariable(variable) ?
                    `${variableEntitySchemaPath}at(${parseInt(val, 10)})` :
                    variableEntitySchemaPath + val;

                return {
                    id: variablePath + val,
                    schema,
                    name: val,
                    childCount: length,
                    parentId: variable,
                    afterId: null,
                };
            });


            this.addVariables(keys);

            return true;
        },

        isToManyAssociationVariable(variable) {
            if (!variable) {
                return false;
            }

            const variables = variable.split('.');
            variables.splice(1, 0, 'properties');
            const field = Utils.get(this.entitySchema, `${variables.join('.')}`);

            return field && field.type === 'association' && ['one_to_many', 'many_to_many'].includes(field.relation);
        },

        onGetTreeItems(parent, schema) {
            this.loadAvailableVariables(parent, schema);
        },

        addVariables(variables) {
            variables.forEach((variable) => {
                this.$set(this.availableVariables, variable.id, variable);
            });
        },

        loadInitialAvailableVariables() {
            this.availableVariables = {};

            Object.keys(this.availableVariablesArray).forEach(variable => {
                const availableVariable = Utils.get(this.availableVariablesArray, variable);
                let length = 0;
                if (typeof availableVariable === 'object' && availableVariable !== null) {
                    length = Object.values(availableVariable).length;
                }

                this.addVariables([{
                    id: variable,
                    schema: variable,
                    name: variable,
                    childCount: length,
                    parentId: null,
                    afterId: null,
                }]);
            });
        },

        onCopyVariable(variable) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(variable).catch((error) => {
                    let errormsg = '';
                    if (error.response.data.errors.length > 0) {
                        const errorDetailMsg = error.response.data.errors[0].detail;
                        errormsg = `<br/> ${this.$tc('sw-mail-template.detail.textErrorMessage')}: "${errorDetailMsg}"`;
                    }

                    this.createNotificationError({
                        message: errormsg,
                    });
                });

                return;
            }

            // non-https polyfill
            Utils.dom.copyToClipboard(variable);
        },

        isTemplatesTabOpen() {
            return this.$route.name === 'sw.sales.channel.detail.socialShoppingTemplate';
        },

        buildExampleContext() {
            return {
                currentCustomerGroup: this.getEntityProperties('customer_group'),
                fallbackCustomerGroup: this.getEntityProperties('customer_group'),
                currency: this.getEntityProperties('currency'),
                salesChannel: this.getEntityProperties('sales_channel'),
                taxRules: [this.getEntityProperties('tax_rule')],
                customer: this.getEntityProperties('customer'),
                paymentMethod: this.getEntityProperties('payment_method'),
                shippingMethod: this.getEntityProperties('shipping_method'),
                shippingLocation: {
                    country: this.getEntityProperties('country'),
                    state: this.getEntityProperties('country_state'),
                    address: this.getEntityProperties('customer_address'),
                },
                context: { taxState: true, rounding: true },
                itemRounding: { decimals: true, interval: true, roundForNet: true },
                totalRounding: { decimals: true, interval: true, roundForNet: true },
            };
        },

        getProductVariables() {
            const productProperties = this.getDeepEntityProperties('product');

            productProperties.options[0].group = this.getEntityProperties('property_group');

            this.productVariables = productProperties;
        },

        /**
         * Returns the fields of the given entity.
         *
         * @param { string } entityName
         * @returns { object }
         */
        getEntityProperties(entityName) {
            const properties = {};
            const keys = Object.keys(EntityDefinition.getDefinitionRegistry().get(entityName).properties);

            keys.forEach(item => { properties[item] = true; });

            return properties;
        },

        /**
         * Returns the fields of the given entity, including the first layer of associations.
         *
         * @param { string } entityName
         * @returns { object }
         */
        getDeepEntityProperties(entityName) {
            const properties = {};

            const fields = EntityDefinition.getDefinitionRegistry().get(entityName).properties;

            Object.keys(fields).forEach((fieldName) => {
                const field = fields[fieldName];

                properties[fieldName] = true;

                if (field.type === 'association' && field.entity !== 'product') {
                    if (['one_to_many', 'many_to_many'].includes(field.relation)) {
                        properties[fieldName] = { 0: this.getEntityProperties(field.entity) };
                    } else {
                        properties[fieldName] = this.getEntityProperties(field.entity);
                    }
                }
            });

            return properties;
        },
    },
});
