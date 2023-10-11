import template from './clever-reach-error.html.twig';
import './clever-reach-error.scss';

const {Component} = Shopware;

Component.register('clever-reach-error', {
    template,

    props: {
        errorTitle: {
            type: String,
            required: true,
            default: ''
        },
        errorMessage: {
            type: String,
            required: true,
            default: ''
        },
        errorDescription: {
            type: String,
            required: true,
            default: ''
        },
        buttonText: {
            type: String,
            required: true,
            default: ''
        }
    },

    data() {
        return {
            isClosed: false
        };
    },

    mounted() {
        this.$root.$emit('errorMounted');
    }
});