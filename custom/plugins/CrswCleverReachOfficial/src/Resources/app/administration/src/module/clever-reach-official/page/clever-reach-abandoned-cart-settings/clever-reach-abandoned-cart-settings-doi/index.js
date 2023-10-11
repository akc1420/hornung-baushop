import template from './clever-reach-abandoned-cart-settings-doi.html.twig';
import './clever-reach-abandoned-cart-settings-doi.scss';

const {Component} = Shopware;

Component.register('clever-reach-abandoned-cart-settings-doi', {
    template,

    inject: [
        'cleverreachService'
    ],

    props: {
        shopId: {
            type: String,
            required: true,
            default: ''
        }
    },

    data() {
        return {
            doiActiveLabel: '',
            doiActivate: '',
            selectedForm: null,
            doiForms: [],
            isLoading: false,
            doiStatus: false,
            doiError: '',
            formsDisabled: false,
            doiSwitch: false,
            chosenForm: '',
            activeLabel: this.$tc('clever-reach.abandonedCart.doiActive'),
            inactiveLabel: this.$tc('clever-reach.abandonedCart.doiInactive'),
            deactivateDoi: this.$tc('clever-reach.abandonedCart.deactivateDoi'),
            activateDoi: this.$tc('clever-reach.abandonedCart.activateDoi'),
            statusChanged: false,
            formChanged: false
        };
    },

    mounted() {
        let me = this;

        this.isLoading = true;
        this.loadForms();
        this.getDoubleOptInStatus();
        this.isLoading = false;

        this.$root.$on('AbandonedCartDisabled', () => {
            this.disableDoi();
        });

        this.$root.$on('TheaDisabled', () => {
            this.disableDoi();
        });

        this.$root.$on('TheaEnabled', () => {
            this.enableDoi();
        });

        this.$root.$on('DoiStatusSaved', (doiStatus) => {
            this.doiStatus = doiStatus;
            this.statusChanged = false;
        });

        this.$root.$on('DoiFormSaved', () => {
            this.formChanged = false;
        });

        this.$root.$on('SavingChanges', () => {
            this.isLoading = true;
        });

        this.$root.$on('ChangesSaved', () => {
            setTimeout(function () {
                me.isLoading = false;
            }, 1000);
        });
    },

    methods: {
        getDoubleOptInStatus: function () {
            this.cleverreachService.getDoiStatus({shopId: this.shopId})
                .then((data) => {
                    let doiErrorLabel = document.querySelector('.cr-doi-error'),
                        doiSwitch = document.querySelector('.cr-doi-switch');

                    if (doiSwitch === null) {
                        return;
                    }

                    if (data.userError) {
                        this.doiError = this.$tc('clever-reach.abandonedCart.doiErrorAccount');
                        this.doiSwitch = true;
                        this.doiDisabled();
                        return;
                    }

                    if (data.shopError) {
                        this.doiError = this.$tc('clever-reach.abandonedCart.doiErrorShop');
                        this.doiSwitch = true;
                        this.doiDisabled();
                        return;
                    }

                    doiErrorLabel.classList.add('cr-no-doi-error');

                    if (data.status) {
                        this.doiEnabled();
                        if (data.selectedForm.id) {
                            this.chosenForm = data.selectedForm.id;
                            this.selectedForm = data.selectedForm.id;
                        }
                    } else {
                        this.doiDisabled();
                    }
                });
        },

        loadForms: function () {
            this.cleverreachService.getDoiForms()
                .then((data) => {
                    this.doiForms = data.forms;
                });
        },

        doiEnabled: function () {
            this.doiSwitch = false;
            this.doiActiveLabel = this.activeLabel;
            this.doiActivate = this.deactivateDoi;
            this.doiStatus = true;
            this.formsDisabled = false;
        },

        doiDisabled: function () {
            this.doiActiveLabel = this.inactiveLabel;
            this.doiActivate = this.activateDoi;
            this.doiStatus = false;
            this.formsDisabled = true;
        },

        doiStatusChange: function (value) {


            if (!this.statusChanged) {
                this.statusChanged = true;
                this.$root.$emit('doiStatusChanged', value);

                if (value) {
                    if (!this.chosenForm) {
                        this.chosenForm = this.doiForms[0].id;
                        this.selectedForm = this.doiForms[0].id;
                    }

                    this.doiEnabled();
                } else {
                    this.doiDisabled();
                }
            } else {
                this.statusChanged = false;

                if (value) {
                    this.$root.$emit('doiStatusNotChanged');
                    this.doiEnabled();
                } else {
                    this.$root.$emit('doiStatusNotChanged');
                    this.doiDisabled();
                }
            }
        },

        doiFormChange: function (value) {
            if (this.formChanged) {
                this.formChanged = false;
                this.$root.$emit('doiFormNotChanged');
            } else {
                this.formChanged = true;
                this.$root.$emit('doiFormChanged', value);
            }
        },

        disableDoi: function () {
            let doiSwitch = document.querySelector('.cr-doi-switch'),
                forms = document.querySelector('.cr-doi-select-form');

            doiSwitch.classList.add('cr-disable-switch');
            forms.classList.add('cr-disable-switch');
            this.doiDisabled();
        },

        enableDoi: function () {
            let doiSwitch = document.querySelector('.cr-doi-switch'),
                forms = document.querySelector('.cr-doi-select-form');

            doiSwitch.classList.remove('cr-disable-switch');
            forms.classList.remove('cr-disable-switch');
            this.getDoubleOptInStatus();
        }
    }
});
