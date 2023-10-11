const { Component } = Shopware;

Component.override('sw-sales-channel-modal-grid', {
    watch: {
        salesChannelTypes() {
            if (!this.isLoading) {
                this.salesChannelTypes.forEach((salesChannelType) => {
                    const customFields = salesChannelType.customFields;
                    if (customFields === null || customFields.isSocialShoppingType !== true) {
                        return;
                    }

                    salesChannelType.translated.name = this.$tc(salesChannelType.translated.name);
                    salesChannelType.translated.manufacturer = this.$tc(salesChannelType.translated.manufacturer);
                    salesChannelType.translated.description = this.$tc(salesChannelType.translated.description);
                    salesChannelType.translated.descriptionLong = this.$tc(salesChannelType.translated.descriptionLong);
                });
            }
        },
    },
});
