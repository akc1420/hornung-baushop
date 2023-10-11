import template from './clever-reach-abandoned-cart-settings-thea.html.twig';
import './clever-reach-abandoned-cart-settings-thea.scss';

const {Component} = Shopware;

Component.register('clever-reach-abandoned-cart-settings-thea', {
    template,

    inject: [
        'cleverreachService'
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
            theaStatus: true,
            theaLabel: this.$tc('clever-reach.abandonedCart.deactivate'),
            isLoading: false,
            activate: this.$tc('clever-reach.abandonedCart.activate'),
            deactivate: this.$tc('clever-reach.abandonedCart.deactivate')
        }
    },

    mounted() {
        let me = this;

        this.isLoading = true;
        this.getTheaStatus();

        this.$root.$on('AbandonedCartEnabled', () => {
            this.enableThea();
        });

        this.$root.$on('AbandonedCartDisabled', () => {
            this.disableThea();
        });

        this.$root.$on('TheaEnabled', () => {
            this.enableButton();
        });

        this.$root.$on('TheaDisabled', () => {
            this.disableButton();
        });

        this.isLoading = false;

        this.$root.$on('SavingChanges', () => {
            this.isLoading = true;
        });

        this.$root.$on('ChangesSaved', () => {
            setTimeout(function () {
                me.isLoading = false;
            }, 1000);
        });
    },

    methods: {
        getTheaStatus: function () {
           this.cleverreachService.getAbandonedCartTheaStatus({shopId: this.shopId})
                .then((theaStatus) => {
                    let theaLabel = document.querySelector('.cr-ac-thea-desc');

                    if (theaLabel === null) {
                        return;
                    }

                    if (this.theaStatus !== theaStatus.status) {
                        this.theaStatus = theaStatus.status;
                        if (theaStatus.status) {
                            this.theaLabel = this.deactivate;
                            this.$root.$emit('TheaEnabled');
                        } else {
                            this.theaLabel = this.activate;
                            this.$root.$emit('TheaDisabled');
                        }
                    }
                    setTimeout(this.getTheaStatus, 2000);
                });
        },

        disableThea: function () {
            let theaSwitch = document.querySelector('.cr-ac-settings-activate-thea'),
                editEmailButton = document.querySelector('.cr-ac-thea-btn');

            if (theaSwitch === null) {
                return;
            }

            theaSwitch.classList.add('cr-disable-switch');
            editEmailButton.disabled = true;
        },

        enableThea: function () {
            let theaSwitch = document.querySelector('.cr-ac-settings-activate-thea');

            if (theaSwitch === null) {
                return;
            }

            theaSwitch.classList.remove('cr-disable-switch');
        },

        enableButton: function () {
            let editEmailButton = document.querySelector('.cr-ac-thea-btn');

            editEmailButton.disabled = false;
        },

        disableButton: function () {
            let editEmailButton = document.querySelector('.cr-ac-thea-btn');

            editEmailButton.disabled = true;
        },

        editEmail: function () {
            this.cleverreachService.getAbandonedCartUrl({shopId: this.shopId})
                .then((data) => {
                    window.open(data.url);
                });
        },

        editTheaStatus: function () {
            this.editEmail();
        }
    }
});
