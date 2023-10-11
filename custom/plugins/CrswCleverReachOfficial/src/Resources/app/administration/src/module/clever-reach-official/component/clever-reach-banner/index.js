import template from './clever-reach-banner.html.twig';
import './clever-reach-banner.scss';

const {Component} = Shopware;

Component.register('clever-reach-banner', {
    template,

    props: {
        buttonText1: {
            type: String,
            required: true,
            default: ''
        },
        buttonText2: {
            type: String,
            required: true,
            default: ''
        }
    },

    data() {
        return {
            secondBtnLoading: false
        }
    },

    mounted() {
      this.$root.$on('secondarySync', () => {
         this.secondBtnLoading = true;
      });

      this.$root.$on('syncCompleted', () => {
         this.secondBtnLoading = false;
      });
    },

    methods: {
        getClass() {
            return this.buttonText2 ? "cr-second-button" : "cr-second-button cr-hide";
        }
    }
});
