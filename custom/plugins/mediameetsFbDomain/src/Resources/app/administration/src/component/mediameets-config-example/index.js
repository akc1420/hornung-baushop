import template from './mediameets-config-example.html.twig';
import './mediameets-config-example.scss';

const { Component } = Shopware;

Component.register('mediameets-config-example', {
    template,

    props: {
        label: {
            type: String,
            required: false,
            default: ''
        },

        text: {
            type: String,
            required: true,
            default: ''
        },

        highlight: {
            type: String,
            required: false,
            default: ''
        }
    }
});
