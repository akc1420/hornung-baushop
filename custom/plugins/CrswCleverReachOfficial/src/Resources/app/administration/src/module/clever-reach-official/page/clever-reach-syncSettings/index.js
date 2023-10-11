import template from './clever-reach-syncSettings.html.twig';
import './clever-reach-syncSettings.scss';
import '../../component/clever-reach-settings';
import '../../component/clever-reach-banner';

const {Component} = Shopware;

Component.register('clever-reach-syncSettings', {
    template,

    inject: [
        'cleverreachService'
    ],

    data() {
        return {
            buttonText1: this.$tc('clever-reach.syncSettings.cancel'),
            buttonText2: this.$tc('clever-reach.syncSettings.importBtn')
        };
    },

    mounted: function () {
        this.addEventListeners();
    },

    methods: {
        addEventListeners: function () {
            let cancelButton = document.querySelector('.cr-first-button'),
                importButton = document.querySelector('.cr-second-button');

            if (cancelButton) {
                cancelButton.addEventListener('click', this.cancel);
            }

            if (importButton) {
                importButton.addEventListener('click', this.importReceivers);
            }
        },

        importReceivers: function () {
            let settings = 'subscribers',
               checkedReceivers = this.$children[1].$children[0].$children[1]._data.checkedReceivers;

            if (checkedReceivers.buyers) {
                settings += ', buyers';
            }

            if (checkedReceivers.contacts) {
                settings += ', contacts';
            }

            this.cleverreachService.saveSyncSettings({syncSettings: settings})
                .then((response) => {
                    let syncStatus = Shopware.Classes.ApiService.handleResponse(response);

                    if (syncStatus.success) {
                        let route = {
                            name: 'clever.reach.official.index',
                            params: {
                                page: 'initialSync'
                            }
                        };

                        this.$router.replace(route);
                    }
                }).catch(error => {
            });
        },

        cancel: function () {
            this.cleverreachService.disconnect()
                .then((response) => {
                    let route = {
                        name: 'clever.reach.official.index',
                        params: {
                            page: 'autoConfig'
                        }
                    }

                    this.$router.replace(route);
                }).catch(error => {
            });
        }
    }
});