import template from './sw-search-bar.html.twig';


const { Component, Application } = Shopware;
const utils = Shopware.Utils;


Component.override('sw-search-bar', {
    template,
    methods: {
        resetSearchType() {},
    }
});

