import template from './clever-reach-router.html.twig';

const {Component} = Shopware;

Component.register('clever-reach-router', {
    template,

    inject: [
        'context',
        'cleverreachService'
    ],

    data() {
        return {
            isLoading: true
        };
    },

    mounted: function () {
        this.getCurrentRoute({});
        this.loadExternalLink();
    },

    watch: {
        $route(to, from) {
            let route;
            switch (to.fullPath) {
                case '/clever/reach/official/autoConfigError':
                    route = {
                        name: 'clever.reach.official.index',
                        params: {
                            page: 'autoConfigError'
                        },
                    };

                    this.$router.replace(route);
                    return;
                case '/clever/reach/official/autoConfig':
                    route = {
                        name: 'clever.reach.official.index',
                        params: {
                            page: 'autoConfig'
                        },
                    };

                    this.$router.replace(route);
                    return;
                case '/clever/reach/official/dashboard':
                    if (from.params.activeTab === 'abandonedCart') {
                        route = {
                            name: 'clever.reach.official.index',
                            params: {
                                page: 'dashboard',
                                activeTab: 'abandonedCart'
                            }
                        };

                        this.$router.replace(route);
                        return;
                    }
                    if (from.params.activeTab === 'settings') {
                        route = {
                            name: 'clever.reach.official.index',
                            params: {
                                page: 'dashboard',
                                activeTab: 'settings'
                            }
                        };

                        this.$router.replace(route);
                        return;
                    }

            }

            if (to.fullPath.includes('clever/reach/official')) {
                let query = {};

                if (to.hasOwnProperty('query') && Object.keys(to.query).length > 0) {
                    query = to.query;
                } else if (from.hasOwnProperty('query') && Object.keys(from.query).length > 0) {
                    query = from.query;
                }
                this.getCurrentRoute(query);
            }
        }
    },

    methods: {
        getCurrentRoute: function (query) {
            return this.cleverreachService.getCurrentRoute()
                .then((response) => {
                    this.isLoading = false;
                    let routeName = response.routeName;
                    let route = {
                        name: 'clever.reach.official.index',
                        params: {
                            page: routeName
                        },
                        query: query
                    };

                    this.$router.replace(route);
                }).catch(error => {

                });
        },
        loadExternalLink: function () {
            let link = document.createElement('link');
            link.href = 'https://use.fontawesome.com/releases/v5.5.0/css/all.css';
            link.rel = 'stylesheet';
            link.type = 'text/css';

            document.head.appendChild(link);
        }
    }
});
