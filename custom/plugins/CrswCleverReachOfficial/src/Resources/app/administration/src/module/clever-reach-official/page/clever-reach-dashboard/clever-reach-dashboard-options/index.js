import template from './clever-reach-dashboard-options.html.twig';
import './clever-reach-dashboard-options.scss';

const {Component} = Shopware;

Component.register('clever-reach-dashboard-options', {
    template,

    inject: [
        'cleverreachService'
    ],

    methods: {
        openUrl: function (url) {
            this.cleverreachService.getRedirectUrl({url: url})
                .then((urlData) => {
                    window.open(urlData.url);
                }).catch(error => {
            });
        },

        openBlog: function () {
            window.open(this.$tc('clever-reach.dashboard.blogUrl'));
        }
    }
});