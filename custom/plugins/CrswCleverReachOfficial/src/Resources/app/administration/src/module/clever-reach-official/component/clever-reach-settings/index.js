import template from './clever-reach-settings.html.twig';
import './clever-reach-settings.scss';

const {Component} = Shopware;

Component.register('clever-reach-settings', {
    template,

    inject: [
        'cleverreachService'
    ],

    props: {
        changesLabel: {
            type: String,
            required: true,
            default: ''
        },
        sendEmails: {
            type: String,
            required: true,
            default: ''
        },
        buttonText: {
            type: String,
            required: true,
            default: ''
        },
        displayNumberOfReceivers: {
            type: Boolean,
            required: true,
            default: true
        }
    },

    data() {
        return {
            checkedReceivers: {
                subscribers: true,
                buyers: false,
                contacts: false,
            },
            url: this.$tc('clever-reach.syncSettings.gdprUrl'),
            subscribers: '',
            buyers: '',
            contacts: '',
            changesLabel: '',
            sendEmails: '',
            buttonText: '',
            displayNumberOfReceivers: true,
            forceSyncLoading: false
        }
    },

    mounted: function () {
        if (this.displayNumberOfReceivers) {
            this.getNumberOfReceivers();
        }

        this.$root.$on('forceSync', () => {
            this.forceSyncLoading = true;
        });

        this.$root.$on('syncCompleted', () => {
           this.forceSyncLoading = false;
        });
    },

    methods: {
        getNumberOfReceivers: function () {
            this.cleverreachService.getNumberOfReceivers()
                .then((receivers) => {
                    this.subscribers = '(' + receivers.subscribers + ')';
                    this.buyers = '(' + receivers.buyers + ')';
                    this.contacts = '(' + receivers.contacts + ')';
                }).catch(error => {
            });
        },

        buyersOnChange(buyers) {
            this.$root.$emit('crBuyers', buyers);
            if (!buyers) {
                this.checkedReceivers.contacts = false;
            }
        },

        contactsOnChange(contacts) {
            this.$root.$emit('crContacts', contacts);
            if (contacts) {
                this.checkedReceivers.buyers = true;
            }
        }
    }
});