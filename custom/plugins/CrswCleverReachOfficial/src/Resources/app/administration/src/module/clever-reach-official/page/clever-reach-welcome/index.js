import template from './clever-reach-welcome.html.twig';
import './clever-reach-welcome.scss';

const { Component } = Shopware;

Component.register('clever-reach-welcome', {
    template,

    data() {
        return {
            type: 'auth'
        };
    },
});
