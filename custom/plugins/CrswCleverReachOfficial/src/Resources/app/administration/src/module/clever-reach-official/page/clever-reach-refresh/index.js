import template from './clever-reach-refresh.html.twig';
import './clever-reach-refresh.scss';

const {Component} = Shopware;

Component.register('clever-reach-refresh', {
    template,

    inject: [
        'cleverreachService'
    ],

    data() {
        return {
            userId: null,
            errorTitle: this.$tc('clever-reach.refresh.title'),
            errorMessage: this.$tc('clever-reach.refresh.description'),
            type: 'refresh',
            isLoading: true,
            isClosed: false
        };
    },

    mounted: function () {
        let banner = document.querySelector('.sw-page__head-area');

        if (banner) {
            banner.classList.add('yellow-banner');
        }
    }
});
