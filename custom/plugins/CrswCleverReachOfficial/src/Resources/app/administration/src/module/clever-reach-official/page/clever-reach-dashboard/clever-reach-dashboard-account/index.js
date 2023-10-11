import template from './clever-reach-dashboard-account.html.twig';
import './clever-reach-dashboard-account.scss';

const {Component} = Shopware;

Component.register('clever-reach-dashboard-account', {
    template,

    inject: [
        'cleverreachService'
    ],

    data() {
        return {
            email: '',
            accountId: '',
            showPopup: false,
            accountLoading: false
        }
    },

    mounted() {
        this.getCrAccountData();
    },

    methods: {
        openPopup: function () {
            this.showPopup = true;
        },

        getCrAccountData: function () {
            this.accountLoading = true;

            this.cleverreachService.getAccountData()
                .then((accountData) => {
                    this.email = accountData.email;
                    this.accountId = accountData.accountId;
                    this.accountLoading = false;
                }).catch(error => {
            });
        },

        disconnect: function () {
            this.closeModal();
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
        },

        closeModal: function () {
            this.showPopup = false;
        }
    }
});