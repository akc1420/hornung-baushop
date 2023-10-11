import template from './sw-sales-channel-list.html.twig';

const { Component } = Shopware;

Component.override('sw-sales-channel-list', {
    template,

    methods: {
        getIconName(salesChannel) {
            const socialShoppingSalesChannel = salesChannel.extensions.socialShoppingSalesChannel;

            if (!socialShoppingSalesChannel) {
                return salesChannel.type.iconName;
            }

            return this.getNetworkIconMapping()[socialShoppingSalesChannel.network];
        },
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
