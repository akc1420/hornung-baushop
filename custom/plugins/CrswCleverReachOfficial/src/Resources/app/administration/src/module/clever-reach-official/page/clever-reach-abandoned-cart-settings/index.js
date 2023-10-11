import template from './clever-reach-abandoned-cart-settings.html.twig';
import './clever-reach-abandoned-cart-settings.scss';
import './clever-reach-abandoned-cart-settings-ac';
import './clever-reach-abandoned-cart-settings-send';
import './clever-reach-abandoned-cart-settings-thea';
import './clever-reach-abandoned-cart-settings-timing';
import './clever-reach-abandoned-cart-settings-doi';

const {Component} = Shopware;

Component.register('clever-reach-abandoned-cart-settings', {
    template,

    inject: [
        'cleverreachService'
    ],

    props: {
        shopId: {
            type: String,
            required: true,
            default: ''
        },
        shopName: {
            type: String,
            required: true,
            default: 'Shop'
        }
    },

    data() {
        return {
            id: '',
            time: 10,
            abandonedCartStatus: false,
            doiStatus: true,
            formId: 0,
            events: [],
            isLoading: false,
            numberOfRequests: 0,
            finishedRequests: 0
        }
    },

    created() {
        this.id = this.shopId;
    },

    mounted() {
        this.disableSaveBtn();

        this.$root.$on('timeChanged', (time) => {
            this.events.push('timeChanged');
            this.time = time;
            this.enableSaveBtn();
        });

        this.$root.$on('timeNotChanged', () => {
            var index = this.events.indexOf('timeChanged');
            this.events.splice(index, 1);

            if (this.events === []) {
                this.disableSaveBtn();
            }
        });

        this.$root.$on('doiStatusChanged', (doiStatus) => {
            this.events.push('doiStatusChanged');
            this.doiStatus = doiStatus;
            this.enableSaveBtn();
        });

        this.$root.$on('doiStatusNotChanged', () => {
            var index = this.events.indexOf('doiStatusChanged');
            this.events.splice(index, 1);

            if (this.events === []) {
                this.disableSaveBtn();
            }
        });

        this.$root.$on('doiFormChanged', (formId) => {
            this.events.push('doiFormChanged');
            this.formId = formId;
            this.enableSaveBtn();
        });

        this.$root.$on('doiFormNotChanged', () => {
            var index = this.events.indexOf('doiFormChanged');
            this.events.splice(index, 1);

            if (this.events === []) {
                this.disableSaveBtn();
            }
        });

        this.$root.$on('AbandonedCartStatusChanged', (abandonedCartStatus) => {
            this.events.push('AbandonedCartStatusChanged');
            this.abandonedCartStatus = abandonedCartStatus;
            this.enableSaveBtn();
        });

        this.$root.$on('AbandonedCartStatusNotChanged', () => {
            var index = this.events.indexOf('AbandonedCartStatusChanged');
            this.events.splice(index, 1);

            if (this.events === []) {
                this.disableSaveBtn();
            }
        });
    },

    methods: {
        enableSaveBtn: function () {
            let saveBtn = document.querySelector('.cr-save-timing');

            saveBtn.disabled = false;
        },

        disableSaveBtn: function () {
            let saveBtn = document.querySelector('.cr-save-timing');

            saveBtn.disabled = true;
        },

        saveChanges: function () {
            let me = this;
            this.$root.$emit('SavingChanges');
            this.disableSaveBtn();

            this.numberOfRequests = this.events.length;

            this.events.forEach(function (item) {
                switch (item) {
                    case 'timeChanged':
                        me.saveTime();
                        break;
                    case 'doiStatusChanged':
                        me.saveDoiStatus();
                        break;
                    case 'doiFormChanged':
                        me.saveDoiForm();
                        break;
                    case 'AbandonedCartStatusChanged':
                        me.saveAcStatus();
                }
            });

            this.areRequestsFinished(this.events.includes('AbandonedCartStatusChanged') ? 1 : 0);
            this.events = [];
        },

        areRequestsFinished: function (value) {
            let me = this;

            if (this.numberOfRequests <= this.finishedRequests) {
                this.numberOfRequests = 0;
                this.finishedRequests = 0;
                if (!value) {
                    this.$root.$emit('ChangesSaved', value);
                }
            } else {
                setTimeout(function () {
                    me.areRequestsFinished(value)
                }, 500);
            }
        },

        saveTime: function () {
            this.cleverreachService.saveTime({shopId: this.shopId, time: this.time})
                .then((response) => {
                    this.finishedRequests += 1;
                    this.$root.$emit('TimeSaved', this.time);
                });
        },

        saveDoiStatus: function () {
            let status = '';

            if (this.doiStatus) {
                status = 'enable';
            } else {
                status = 'disable';
            }

            this.cleverreachService.changeDoiStatus(status, {shopId: this.shopId})
                .then((response) => {
                    this.finishedRequests += 1;
                    this.$root.$emit('DoiStatusSaved', this.doiStatus);
                });
        },

        saveDoiForm: function () {
            this.cleverreachService.chooseDoiForm({shopId: this.shopId, formId: this.formId})
                .then((response) => {
                    this.finishedRequests += 1;
                    this.$root.$emit('DoiFormSaved');
                });
        },

        saveAcStatus: function () {
            let status = '';

            if (this.abandonedCartStatus) {
                status = 'enable';
            } else {
                status = 'disable';
            }

            this.cleverreachService.changeAbandonedCartStatus(status, {shopId: this.shopId})
                .then((data) => {
                    this.finishedRequests += 1;
                    this.$root.$emit('AbandonedCartStatusSaved');

                    if (data.error) {
                        this.abandonedCartStatus = !this.abandonedCartStatus;
                        this.$root.$emit('AbandonedCartError');

                        return;
                    }

                    if (data.status === 'disabled') {
                        this.$root.$emit('AbandonedCartDisabled');
                    } else {
                        this.$root.$emit('AbandonedCartEnabling');
                    }
                });
        }
    }
});
