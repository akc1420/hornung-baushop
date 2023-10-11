import template from './clever-reach-abandoned-cart-settings-send.html.twig';
import './clever-reach-abandoned-cart-settings-send.scss';

const {Component} = Shopware;

Component.register('clever-reach-abandoned-cart-settings-send', {
    template,

    inject: [
        'cleverreachService'
    ],

    data() {
        return {
            checkedReceivers: {
                subscribers: true,
                buyers: false,
                contacts: false,
            },
            isLoading: false
        }
    },

    mounted() {
        let me = this;
        this.getSyncData();

        this.$root.$on('AbandonedCartDisabled', () => {
            this.disableSettings();
        });

        this.$root.$on('TheaDisabled', () => {
           this.disableSettings();
        });

        this.$root.$on('TheaEnabled', () => {
           this.enableSettings();
        });

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
        getSyncData: function () {
            this.isLoading = true;

            this.cleverreachService.getServices()
                .then((enabledServices) => {
                    let checkedReceivers = this._data.checkedReceivers;

                    if (enabledServices.buyers) {
                        checkedReceivers.buyers = true;
                    }

                    if (enabledServices.contacts) {
                        checkedReceivers.contacts = true;
                    }

                    this.isLoading = false;
                }).catch(error => {
                });
        },

        openSyncSettings: function () {
            let route = {
                name: 'clever.reach.official.index',
                params: {
                    page: 'dashboard',
                    activeTab: 'settings'
                }
            };

            this.$router.replace(route);
        },

        disableSettings: function () {
            let settingsBtn = document.querySelector('.cr-ac-settings-btn');

            settingsBtn.disabled = true;
        },

        enableSettings: function () {
            let settingsBtn = document.querySelector('.cr-ac-settings-btn');

            settingsBtn.disabled = false;
        }
    }
});
