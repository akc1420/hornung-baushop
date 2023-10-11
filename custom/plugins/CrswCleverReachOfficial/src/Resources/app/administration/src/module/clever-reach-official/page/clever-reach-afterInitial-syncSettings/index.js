import template from './clever-reach-afterInitial-syncSettings.html.twig';
import './clever-reach-afterInitial-syncSettings.scss';

const {Component} = Shopware;

Component.register('clever-reach-afterInitial-syncSettings', {
    template,

    inject: [
        'cleverreachService'
    ],

    data() {
        return {
            taskInProgress: false,
            progressValue: 0,
            lastSyncTime: '',
            orderSyncRunning: false,
            errorOccurred: false,
            errorDescription: ''
        }
    },

    mounted() {
        this.getOrderData();
        this.getSyncData();
        this.addEventListeners();
        this.$root.$on('crBuyers', (buyers) => {
            this.buyerListener(buyers)
        });
        this.$root.$on('crContacts', (contacts) => {
            this.contactListener(contacts)
        });
    },

    methods: {
        addEventListeners: function () {
            let bannerButton = document.querySelector('.cr-second-button'),
                forceSync = document.querySelector('.cr-reforce-btn');

            if (bannerButton) {
                bannerButton.onclick = this.saveSettings;
            }

            if (forceSync) {
                forceSync.onclick = this.forceSync;
            }
        },

        getOrderData: function () {

            this.cleverreachService.getOrdersData()
                .then((orderData) => {
                    let importOrder = document.querySelector('.cr-import-order-btn');

                    importOrder.disabled = !orderData.enableOrderSync;
                    this.lastSyncTime = orderData.lastSyncTime;

                    if (orderData.taskStatus === 'in_progress') {
                        this.orderSyncRunning = true;
                        this.getOrderProgress();
                    }
                }).catch(error => {
                });
        },

        getOrderProgress: function () {
            this.cleverreachService.getOrderProgress()
                .then((progress) => {
                    let saveBtn = document.querySelector('.cr-second-button'),
                        forceBtn = document.querySelector('.cr-reforce-btn');

                    this.progressValue = progress.progressValue;

                    if (progress.errorMessage) {
                        this.errorOccurred = true;
                        this.errorDescription = this.$tc('clever-reach.initialSync.errorDescription') + progress.errorMessage;
                        this.taskInProgress = false;
                        this.orderSyncRunning = false;
                        forceBtn.disabled = false;
                        this.getSyncData();

                        this.$root.$on('errorMounted', () => {
                            let errorBtn = document.querySelector('.cr-error-btn');

                            errorBtn.onclick = this.retrySync.bind(this, 'OrdersOlderThanOneYearSyncTask', 'orderSync');
                        });

                        return;
                    }

                    if (progress.progressValue === 100) {
                        this.taskInProgress = false;
                        this.orderSyncRunning = false;
                        forceBtn.disabled = false;
                        this.getOrderData();
                        this.getSyncData();
                        return;
                    }

                    setTimeout(this.getOrderProgress, 500);
                }).catch(error => {
                });
        },

        includeOrders: function () {
            let saveBtn = document.querySelector('.cr-second-button'),
                forceBtn = document.querySelector('.cr-reforce-btn');

            this.taskInProgress = true;
            this.orderSyncRunning = true;
            saveBtn.disabled = true;
            forceBtn.disabled = true;

            this.cleverreachService.orderSync()
                .then((response) => {
                    if (response.success) {
                        this.getOrderProgress();
                    }
                }).catch(error => {
                });
        },

        saveSettings: function () {
            let settings = 'subscribers',
                checkedReceivers = this.$children[1]._data.checkedReceivers,
                orderImport = document.querySelector('.cr-import-order-btn'),
                forceBtn = document.querySelector('.cr-reforce-btn');

            if (checkedReceivers.buyers) {
                settings += ', buyers';
            }

            if (checkedReceivers.contacts) {
                settings += ', contacts';
            }

            orderImport.disabled = true;
            forceBtn.disabled = true;
            this.$root.$emit('secondarySync');

            this.cleverreachService.saveSyncSettings({syncSettings: settings})
                .then((syncStatus) => {
                    if (syncStatus.success) {
                        this.syncStatus('saveSync');
                    }
                }).catch(error => {
                });
        },

        forceSync: function () {
            const saveBtn = document.querySelector('.cr-second-button'),
                orderImport = document.querySelector('.cr-import-order-btn');

            orderImport.disabled = true;
            saveBtn.disabled = true;
            this.$root.$emit('forceSync');

            this.cleverreachService.forceSync()
                .then((status) => {
                    if (status.success) {
                        this.syncStatus('forceSync');
                    }
                }).catch(error => {
                });
        },

        syncStatus: function (syncType) {
            this.cleverreachService.getSettingsSyncStatus()
                .then((syncStatus) => {
                    let forceBtn = document.querySelector('.cr-reforce-btn');

                    if (syncStatus.errorMessage) {
                        this.errorOccurred = true;
                        this.errorDescription = this.$tc('clever-reach.initialSync.errorDescription') + syncStatus.errorMessage;
                        forceBtn.disabled = false;
                        this.$root.$emit('syncCompleted');

                        this.$root.$on('errorMounted', () => {
                            let errorBtn = document.querySelector('.cr-error-btn');

                            errorBtn.onclick = this.retrySync.bind(this, 'SecondarySyncTask', syncType);
                        });
                    }

                    if (['created', 'queued', 'in_progress'].includes(syncStatus.status)) {
                        setTimeout(this.syncStatus.bind(this, syncType), 500);
                    } else {
                        forceBtn.disabled = false;
                        this.$root.$emit('syncCompleted');
                        this.getSyncData();
                        this.getOrderData();
                    }
                }).catch(error => {
            });
        },

        getSyncData: function () {
            this.cleverreachService.getServices()
                .then((enabledServices) => {
                    let checkedReceivers = this.$children[1]._data.checkedReceivers,
                        enabledGroups = document.getElementById('cr-enabled-groups'),
                        saveBtn = document.querySelector('.cr-second-button'),
                        orderImport = document.querySelector('.cr-import-order-btn');

                    enabledGroups.value = 'subscribers';

                    if (enabledServices.buyers) {
                        enabledGroups.value += ', buyers';
                        checkedReceivers.buyers = true;
                    }

                    if (enabledServices.contacts) {
                        enabledGroups.value += ', contacts';
                        checkedReceivers.contacts = true;
                    }

                    saveBtn.disabled = true;
                }).catch(error => {
            });
        },

        buyerListener: function (buyers) {
            let enabledGroups = document.getElementById('cr-enabled-groups'),
                saveBtn = document.querySelector('.cr-second-button'),
                orderImport = document.querySelector('.cr-import-order-btn');

            if (buyers) {
                saveBtn.disabled = enabledGroups.value.includes('buyers');
                orderImport.disabled = document.querySelector('.cr-order-time') !== null;
            } else {
                saveBtn.disabled = !enabledGroups.value.includes('buyers');
                orderImport.disabled = true;
            }
        },

        contactListener: function (contacts) {
            let enabledGroups = document.getElementById('cr-enabled-groups'),
                saveBtn = document.querySelector('.cr-second-button'),
                orderImport = document.querySelector('.cr-import-order-btn');

            if (contacts) {
                saveBtn.disabled = enabledGroups.value.includes('contacts');
                orderImport.disabled = document.querySelector('.cr-order-time') !== null;
            } else {
                saveBtn.disabled = !enabledGroups.value.includes('contacts') &&
                    enabledGroups.value.includes('buyers');
            }
        },

        retrySync: function (taskName, syncType) {
            let saveBtn = document.querySelector('.cr-second-button'),
                orderImport = document.querySelector('.cr-import-order-btn'),
                forceBtn = document.querySelector('.cr-reforce-btn');

            switch (syncType) {
                case 'forceSync':
                    orderImport.disabled = true;
                    saveBtn.disabled = true;
                    this.$root.$emit('forceSync');
                    break;
                case 'saveSync':
                    orderImport.disabled = true;
                    forceBtn.disabled = true;
                    this.$root.$emit('secondarySync');
                    break;
                case 'orderSync':
                    this.taskInProgress = true;
                    this.orderSyncRunning = true;
                    forceBtn.disabled = true;
                    saveBtn.disabled = true;
            }

            this.cleverreachService.retrySync({'taskName': taskName})
                .then((retryData) => {
                    if (retryData.success) {
                        this.errorOccurred = false;
                    }

                    if (syncType === 'orderSync') {
                        this.getOrderProgress();
                    } else {
                        this.syncStatus(syncType);
                    }
                }).catch(error => {
            });
        }
    }
});