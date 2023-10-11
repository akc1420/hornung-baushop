import template from './clever-reach-abandoned-cart-settings-timing.html.twig';
import './clever-reach-abandoned-cart-settings-timing.scss';

const {Component} = Shopware;

Component.register('clever-reach-abandoned-cart-settings-timing', {
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
            time: '10',
            initialTime: '',
            isLoading: false,
            crRadioDisabled: false
        }
    },

    mounted() {
        let me = this;
        this.getTimeData();

        this.$root.$on('AbandonedCartDisabled', () => {
            this.disableTiming();
        });

        this.$root.$on('TheaDisabled', () => {
            this.disableTiming();
        });

        this.$root.$on('TheaEnabled', () => {
            this.enableTiming();
        });

        this.$root.$on('TimeSaved', (time) => {
            this.updateTime(time);
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
        getTimeData: function () {
            this.isLoading = true;

            this.cleverreachService.getTime({shopId: this.shopId})
                .then((data) => {
                    this.time = data.time;
                    this.initialTime = data.time;
                    this.isLoading = false;
                    this.$root.$emit('timeNotChanged');
                });
        },

        disableTiming: function () {
            this.crRadioDisabled = true;
        },

        enableTiming: function () {
            this.crRadioDisabled = false;
        },

        timeChanged: function (value) {
            this.time = value;

            if (value !== this.initialTime) {
                this.$root.$emit('timeChanged', value);
            } else {
                this.$root.$emit('timeNotChanged');
            }
        },

        updateTime: function (time) {
            this.initialTime = time;
        }
    }
});
