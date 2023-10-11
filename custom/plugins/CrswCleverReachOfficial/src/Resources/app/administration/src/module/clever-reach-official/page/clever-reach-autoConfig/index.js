import template from './clever-reach-autoConfig.html.twig';
import './clever-reach-autoConfig.scss'

const {Component} = Shopware;

Component.register('clever-reach-autoConfig', {
    template,

    inject: [
        'cleverreachService'
    ],

    data() {
        return {
            isLoading: true
        };
    },

    mounted: function () {
        this.startAutoConfiguration();
    },

    methods: {
        startAutoConfiguration: function () {
            this.cleverreachService.startAutoConfig()
                .then((autoConfig) => {
                    this.isLoading = false;
                    let route;

                    if (autoConfig.success) {
                        route = {
                            name: 'clever.reach.official.index',
                            params: {
                                page: 'welcome'
                            },
                        };
                    } else {
                        route = {
                            name: 'clever.reach.official.index',
                            params: {
                                page: 'autoConfigError'
                            }
                        };
                    }

                    this.$router.replace(route);
                });
        }
    }
});
