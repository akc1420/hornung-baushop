const { Component } = Shopware;

Component.override('sw-sales-channel-menu', {
    computed: {
        buildMenuTree() {
            const tree = this.$super('buildMenuTree');
            const networkIconMapping = this.getNetworkIconMapping();
            const iconById = {};
            this.salesChannels.forEach((salesChannel) => {
                if (salesChannel.extensions.socialShoppingSalesChannel !== undefined) {
                    iconById[salesChannel.id] = networkIconMapping[
                        salesChannel.extensions.socialShoppingSalesChannel.network
                    ];
                }
            });

            tree.forEach((menuItem) => {
                if (iconById[menuItem.id] !== undefined) {
                    menuItem.icon = iconById[menuItem.id];
                }
            });

            return tree;
        },
    },

    methods: {
        /**
         * @deprecated tag:v3.2.0 - Method will be removed
         */
        getNetworkIconMapping() {
            return {
                'SwagSocialShopping\\Component\\Network\\Facebook': 'brand-facebook',
                'SwagSocialShopping\\Component\\Network\\GoogleShopping': 'brand-google',
                'SwagSocialShopping\\Component\\Network\\Pinterest': 'brand-pinterest',
                'SwagSocialShopping\\Component\\Network\\Instagram': 'brand-instagram',
            };
        },
    },
});
