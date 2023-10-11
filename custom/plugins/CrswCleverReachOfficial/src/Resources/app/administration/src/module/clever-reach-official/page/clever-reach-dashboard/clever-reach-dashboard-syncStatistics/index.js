import template from './clever-reach-dashboard-syncStatistics.html.twig';
import './clever-reach-dashboard-syncStatistics.scss';

const {Component} = Shopware;

Component.register('clever-reach-dashboard-syncStatistics', {
    template,

    inject: [
        'cleverreachService'
    ],

    data() {
        return {
            syncedRecipients: 0,
            createdList: '',
            segments: '',
            form: '',
            displayStatistics: false
        }
    },

    mounted() {
        this.getSyncStatisticsData();
        this.$root.$on('initialSyncFinished', this.getSyncStatisticsData);
    },

    updated() {
        this.addEventListeners();
    },

    methods: {
        addEventListeners: function () {
            let closeBtn = document.querySelector('.sw-alert__close');

            if (closeBtn) {
                closeBtn.addEventListener('click', this.closeStatistics);
            }
        },

        closeStatistics: function () {
            let syncStatistics = document.querySelector('.cr-alert');

            if (syncStatistics) {
                syncStatistics.hidden = true;
            }
        },

        getSyncStatisticsData: function () {
            this.cleverreachService.getSyncStatistics()
                .then((syncStatistics) => {
                    if (this.displayStatistics) {
                        return;
                    }

                    if (!syncStatistics.displayStatistics) {
                        this.displayStatistics = false;
                        return;
                    }

                    this.displayStatistics = true;
                    this.syncedRecipients = syncStatistics.syncedRecipients;
                    this.createdList = syncStatistics.createdList;
                    this.segments = syncStatistics.segments;
                    this.form = syncStatistics.form;
                }).catch(error => {
            });
        }
    }
});
