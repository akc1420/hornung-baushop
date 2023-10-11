const { Component, Context } = Shopware;
const { Criteria } = Shopware.Data;

Component.override('sw-settings-rule-detail-assignments', {
    computed: {
        associationEntitiesConfig() {
            const associationEntitiesConfig = this.$super('associationEntitiesConfig');

            if (!this.getRuleAssignmentConfiguration) {
                return associationEntitiesConfig;
            }

            associationEntitiesConfig.push(this.swagCmsExtensionsRuleEntityConfig);
            return associationEntitiesConfig;
        },

        swagCmsExtensionsRuleEntityConfig() {
            return {
                id: 'swagCmsExtensionsBlockRule',
                notAssignedDataTotal: 0,
                allowAdd: false,
                entityName: 'cms_block',
                label: 'swag-cms-extensions.sw-settings-rule.detail.associations.blockVisibility',
                criteria: () => {
                    const criteria = new Criteria();
                    criteria.setLimit(this.associationLimit);
                    criteria.addFilter(Criteria.equals('swagCmsExtensionsBlockRule.visibilityRuleId', this.rule.id));
                    criteria.addAssociation('section.page');
                    criteria.addAssociation('swagCmsExtensionsBlockRule');

                    return criteria;
                },
                api: () => {
                    return Object.assign({}, Context.api);
                },
                detailRoute: 'sw.cms.detail',
                gridColumns: [
                    {
                        property: 'section.page.name',
                        label: 'Page name',
                        rawData: true,
                        sortable: true,
                        allowEdit: false,
                        routerLink: 'sw.cms.detail',
                    },
                    {
                        property: 'name',
                        label: 'Block name',
                        rawData: true,
                        sortable: true,
                        allowEdit: false,
                    },
                ],
                deleteContext: {
                    type: 'one-to-many',
                    entity: 'cms_block',
                    column: 'extensions.swagCmsExtensionsBlockRule.visibilityRuleId',
                },
            };
        },
    },

    methods: {
        getRouterLink(entity, item) {
            if (entity.id !== 'swagCmsExtensionsBlockRule') {
                return this.$super('getRouterLink', entity, item);
            }

            return { name: entity.detailRoute, params: { id: item.section.page.id } };
        },
    },
});
