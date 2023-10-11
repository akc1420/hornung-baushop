const { Component } = Shopware;

if (Shopware.Feature.isActive('FEATURE_SWAGCMSEXTENSIONS_8')) {
    Component.override('sw-cms-detail', {
        inject: [
            'repositoryFactory',
        ],

        computed: {
            loadPageCriteria() {
                const criteria = this.$super('loadPageCriteria');

                criteria
                    .getAssociation('sections')
                    .getAssociation('blocks')
                    .addAssociation('swagCmsExtensionsBlockRule');

                return criteria;
            },
        },

        methods: {
            cloneSlotsInBlock(block, newBlock) {
                const blockRule = block.extensions.swagCmsExtensionsBlockRule;
                if (blockRule === undefined) {
                    this.$super('cloneSlotsInBlock', block, newBlock);
                    return;
                }

                const blockRuleRepository = this.repositoryFactory.create('swag_cms_extensions_block_rule');
                const newBlockRule = blockRuleRepository.create();

                newBlockRule.cmsBlockId = blockRule.cmsBlockId;
                newBlockRule.inverted = blockRule.inverted;
                newBlockRule.visibilityRuleId = blockRule.visibilityRuleId;

                newBlock.extensions.swagCmsExtensionsBlockRule = newBlockRule;

                this.$super('cloneSlotsInBlock', block, newBlock);
            },
        },
    });
}
