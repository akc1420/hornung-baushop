import template from './clever-reach-iframe.html.twig';
import './clever-reach-iframe.scss';

const { Component } = Shopware;

Component.register('clever-reach-iframe', {
    template,

    inject: [
        'cleverreachService'
    ],

    props: {
        type: {
            type: String,
            required: true,
            default: 'auth'
        }
    },

    data() {
        return {
            authUrl: '',
            isLoading: true
        };
    },

    created: function () {
        this.fetchAuthUrl();
    },

    methods: {

        fetchAuthUrl: function() {
            return this.cleverreachService.getAuthUrl(this.type)
                .then((response) => {
                    this.isLoading = false;
                    this.authUrl = response.authUrl;
                });
        }
    }
});