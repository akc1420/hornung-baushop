import template from './clever-reach-autoConfig-error.html.twig';
import '../../component/clever-reach-error';

const {Component} = Shopware;

Component.register('clever-reach-autoConfig-error', {
    template,

    inject: [
        'cleverreachService'
    ],

    data() {
        return {
            errorTitle: this.$tc('clever-reach.autoConfiguration.fail'),
            errorMessage: this.$tc('clever-reach.autoConfiguration.error'),
            errorDescription: '',
            buttonText: this.$tc('clever-reach.basic.retry')
        }
    },

    mounted: function () {
        this.addEventListeners();
    },

    methods: {
        addEventListeners: function () {
            let retryButton = document.querySelector('.cr-second-button');
            if (retryButton) {
                retryButton.addEventListener('click', this.retryAutoConfig);
            }
        },

        retryAutoConfig: function () {
            let route = {
                name: 'clever.reach.official.index',
                params: {
                    page: 'autoConfig'
                }
            };

            this.$router.replace(route);
        }
    }
});
