const { Component } = Shopware;

if (Shopware.Feature.isActive('FEATURE_SWAGCMSEXTENSIONS_1')) {
    Component.override('sw-cms-detail', {

        computed: {
            loadPageCriteria() {
                const criteria = this.$super('loadPageCriteria');

                criteria
                    .getAssociation('sections')
                    .getAssociation('blocks')
                    .addAssociation('swagCmsExtensionsQuickview');

                return criteria;
            },
        },
    });
}
