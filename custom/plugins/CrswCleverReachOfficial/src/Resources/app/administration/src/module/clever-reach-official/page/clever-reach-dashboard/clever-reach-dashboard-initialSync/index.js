import template from './clever-reach-dashboard-initialSync.html.twig';
import './clever-reach-dashboard-initialSync.scss';

const {Component} = Shopware;

Component.register('clever-reach-dashboard-initialSync', {
    template,

    inject: [
        'cleverreachService'
    ],

    data() {
        return {
            progressValue: 0,
            initialSync: false,
            showError: false,
            errorTitle: this.$tc('clever-reach.initialSync.errorTitle'),
            errorMessage: this.$tc('clever-reach.initialSync.errorMessage'),
            errorDescription: this.$tc('clever-reach.initialSync.errorDescription'),
            buttonText: this.$tc('clever-reach.initialSync.retrySync')
        }
    },

    mounted() {
        this.disableTabs();
        this.checkStatus();
    },

    updated() {
        this.addEventListeners();
    },

    methods: {
        checkStatus: function () {
            this.cleverreachService.getInitialSyncStatus()
                .then((initialSyncData) => {
                    if (initialSyncData.initialSyncError) {
                        this.showError = true;
                        this.errorDescription = this.$tc('clever-reach.initialSync.errorDescription')
                            + initialSyncData.errorDescription;
                        this.initialSync = false;
                        return;
                    }

                    if (!initialSyncData.initialSync) {
                        this.initialSync = false;
                        this.enableTabs();
                        this.$root.$emit('initialSyncFinished');
                        return;
                    }

                    this.initialSync = true;
                    this.progressValue = initialSyncData.progressValue;

                    let recipientList = document.getElementById('cr-recipient-list'),
                        dataFields = document.getElementById('cr-data-fields'),
                        recipientImport = document.getElementById('cr-recipient-import');

                    if (recipientList && dataFields && recipientImport) {
                        switch (initialSyncData.runningSubTask) {
                            case 'GroupSynchronization':
                                recipientList.style.fontWeight = "600";
                                break;
                            case 'FieldsSynchronization':
                                recipientList.style.fontWeight = "400";
                                dataFields.style.fontWeight = "600";
                                break;
                            case 'ReceiverSynchronization':
                                recipientList.style.fontWeight = "400";
                                dataFields.style.fontWeight = "400";
                                recipientImport.style.fontWeight = "600";
                        }
                    }

                    setTimeout(this.checkStatus, 500);
                }).catch(error => {
            });
        },

        disableTabs: function () {
            let tabs = document.querySelector('.cr-tabs-left');

            if (tabs) {
                tabs.style.pointerEvents = "none";
            }
        },

        enableTabs: function () {
            let tabs = document.querySelector('.cr-tabs-left');

            if (tabs) {
                tabs.style.pointerEvents = "initial";
            }
        },

        retryInitialSync: function () {
            this.cleverreachService.retrySync({'taskName': 'InitialSyncTask'})
                .then((retryData) => {
                    if (retryData.success) {
                        this.showError = false;
                        this.initialSync = true;
                        this.checkStatus();
                    }
                }).catch(error => {
            });
        },

        addEventListeners: function () {
            let errorBtn = document.querySelector('.cr-error-btn');

            if (errorBtn) {
                errorBtn.addEventListener('click', this.retryInitialSync);
            }
        }
    }
});
