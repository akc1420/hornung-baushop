import template from './clever-reach-dashboard.html.twig';
import './clever-reach-dashboard.scss';
import './clever-reach-dashboard-syncStatistics';
import './clever-reach-dashboard-options';
import './clever-reach-dashboard-account';
import './clever-reach-dashboard-syncStatus';
import './clever-reach-dashboard-initialSync';
import '../clever-reach-afterInitial-syncSettings';
import '../clever-reach-forms';
import '../clever-reach-abandoned-cart';

const {Component} = Shopware;

Component.register('clever-reach-dashboard', {
    template,

    inject: [
        'cleverreachService'
    ],

    props: {
        activeTab: {
            type: String,
            required: true,
            default: 'dashboard'
        }
    },

    data() {
        return {
            bannerButton: this.$tc('clever-reach.dashboard.createNewsletter')
        }
    },

    mounted() {
        let banner = document.querySelector('.sw-page__head-area');

        if (banner) {
            banner.classList.add('yellow-banner');
        }

        if (this.$route.params.activeTab === 'abandonedCart') {
            let tabs = this.$children[1].$children[0].$children[0].$children[0],
                abandonedCartTab = this.$children[1].$children[0].$children[0].$children[0].$children[2];
            tabs.setActiveItem(abandonedCartTab);
            this.setActiveTab('abandonedCart');
            return;
        }

        if (this.$route.params.activeTab === 'settings') {
            let tabs = this.$children[1].$children[0].$children[0].$children[0],
                settingsTab = this.$children[1].$children[0].$children[0].$children[0].$children[3];
            tabs.setActiveItem(settingsTab);
            this.setActiveTab('settings');
            return;
        }

        this.setActiveTab('dashboard');
    },

    methods: {
        setActiveTab: function (tab) {
            let bannerButton = document.querySelector('.cr-second-button');

            this.activeTab = tab;

            if (bannerButton) {
                switch (tab) {
                    case 'dashboard':
                        this.bannerButton = this.$tc('clever-reach.dashboard.createNewsletter');
                        bannerButton.disabled = false;
                        bannerButton.onclick = this.openUrl.bind(this, '/admin/mailing_create_new.php');
                        break;
                    case 'settings':
                        this.bannerButton = this.$tc('clever-reach.syncSettings.save');
                        break;
                    case 'forms':
                        this.bannerButton = '';
                        break;
                    case 'abandonedCart':
                        this.bannerButton = '';
                }
            }
        },

        openUrl: function (url) {
            this.cleverreachService.getRedirectUrl({url: url})
                .then((urlData) => {
                    window.open(urlData.url);
                }).catch(error => {
            });
        }
    }
});
