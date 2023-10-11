import template from './clever-reach-dashboard-syncStatus.html.twig';
import './clever-reach-dashboard-syncStatus.scss';

const {Component} = Shopware;

Component.register('clever-reach-dashboard-syncStatus', {
    template,

    inject: [
        'cleverreachService'
    ],

    data() {
        return {
            syncStatus: false,
            totalRecipients: 0,
            lastSync: '',
            currentPlan: '',
            newRecipients: 0,
            unsubscribed: 0,
            syncStatusLoading: false
        }
    },

    mounted() {
        this.getSyncStatusData();
        this.$root.$on('initialSyncFinished', this.showSyncStatus);
        this.$root.$on('initialSyncFinished', this.getSyncStatusData);
    },

    methods: {
        getSyncStatusData: function () {
            this.syncStatusLoading = true;

            this.cleverreachService.getDashboardSyncStatus()
                .then((syncStatus) => {
                    this.totalRecipients = syncStatus.totalRecipients;
                    this.lastSync = syncStatus.lastSync;
                    this.currentPlan = syncStatus.currentPlan;
                    this.newRecipients = syncStatus.newRecipients;
                    this.unsubscribed = syncStatus.unsubscribed;
                    this.syncStatusLoading = false;
                }).catch(error => {
            });
        },

        openUrl: function (url) {
            this.cleverreachService.getRedirectUrl({url: url})
                .then((urlData) => {
                    window.open(urlData.url);
                }).catch(error => {
            });
        },

        showSyncStatus: function () {
            this.syncStatus = true;
        }
    }
});
