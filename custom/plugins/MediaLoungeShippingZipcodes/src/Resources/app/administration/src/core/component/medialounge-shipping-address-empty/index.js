const { Component } = Shopware;
import template from './medialounge-shipping-address-empty.html.twig';

Component.extend('medialounge-shipping-address-empty', 'sw-condition-base', {
    template,

    computed: {
        selectValues() {
            return [
                {
                    label: this.$tc('global.sw-condition.condition.yes'),
                    value: true
                },
                {
                    label: this.$tc('global.sw-condition.condition.no'),
                    value: false
                }
            ];
        },

        isShippingAddressEmpty: {
            get() {
                this.ensureValueExist();

                // Define a standard value
                if (this.condition.value.isShippingAddressEmpty == null) {
                    this.condition.value.isShippingAddressEmpty = false;
                }

                return this.condition.value.isShippingAddressEmpty;
            },
            set(isShippingAddressEmpty) {
                this.ensureValueExist();
                this.condition.value = { ...this.condition.value, isShippingAddressEmpty };
            }
        }
    }
});
