import template from './clever-reach-abandoned-cart-settings-ac.html.twig';
import './clever-reach-abandoned-cart-settings-ac.scss';

const {Component, Mixin} = Shopware;

Component.register('clever-reach-abandoned-cart-settings-ac', {
    template,

    inject: [
        'cleverreachService'
    ],

    mixins: [
        Mixin.getByName('notification')
    ],

    props: {
        shopId: {
            type: String,
            required: true,
            default: ''
        }
    },

    data() {
        return {
            status: false,
            activateLabel: this.$tc('clever-reach.abandonedCart.activate'),
            isLoading: false,
            storeIdLabel: '',
            statusChanged: false
        }
    },

    mounted() {
        let me = this;
        this.getStatus();

        this.$root.$on('AbandonedCartEnabling', () => {
            this.checkStatus();
        });

        this.$root.$on('AbandonedCartDisabled', () => {
            this.$root.$emit('ChangesSaved');
            setTimeout(function () {
                me.isLoading = false;
            }, 1000);
        });

        this.$root.$on('AbandonedCartError', (error) => {
            this.status = !this.status;
            this.createNotificationError({
                title: 'Abandoned cart activation failed.',
                message: error
            });
        });

        this.$root.$on('AbandonedCartStatusSaved', () => {
            this.statusChanged = false;
        });

        this.$root.$on('SavingChanges', () => {
           this.isLoading = true;
        });

        this.$root.$on('ChangesSaved', (value) => {
            if (value === 0) {
                setTimeout(function () {
                    me.isLoading = false;
                }, 1000);
            }
        });
    },

    methods: {
        getStatus: function () {
            this.isLoading = true;

            this.cleverreachService.getAbandonedCartStatus({shopId: this.shopId})
                .then((abandonedCartStatus) => {
                    this.status = abandonedCartStatus.status;

                    if (abandonedCartStatus.status) {
                        this.storeIdLabel = this.$tc('clever-reach.abandonedCart.storeId') + 'Shopware 6 - ' + this.shopId;
                        this.activateLabel = this.$tc('clever-reach.abandonedCart.deactivate');
                        this.$root.$emit('AbandonedCartEnabled');
                    } else {
                        this.storeIdLabel = '';
                        this.activateLabel = this.$tc('clever-reach.abandonedCart.activate');
                        this.$root.$emit('AbandonedCartDisabled');
                    }

                    this.isLoading = false;
                });
        },

        abandonedCartStatusChange: function () {
            if (this.statusChanged) {
                this.statusChanged = false;
                this.$root.$emit('AbandonedCartStatusNotChanged');
            } else {
                this.statusChanged = true;
                this.$root.$emit('AbandonedCartStatusChanged', this.status);
            }

            if (this.status) {
                this.storeIdLabel = this.$tc('clever-reach.abandonedCart.storeId') + 'Shopware 6 - ' + this.shopId;
                this.activateLabel = this.$tc('clever-reach.abandonedCart.deactivate');
            } else {
                this.storeIdLabel = '';
                this.description = this.$tc('clever-reach.abandonedCart.send');
                this.activateLabel = this.$tc('clever-reach.abandonedCart.activate');
            }
        },

        checkStatus: function () {
            this.$root.$emit('SavingChanges');
            this.isLoading = true;

            this.cleverreachService.getAbandonedCartStatus({shopId: this.shopId})
                .then((abandonedCartStatus) => {
                    if (abandonedCartStatus.status) {
                        this.isLoading = false;
                        this.$root.$emit('ChangesSaved');
                        this.$root.$emit('AbandonedCartEnabled');
                    } else {
                        this.isLoading = true;
                        setTimeout(this.checkStatus, 1000);
                    }
                });
        }
    }
});
