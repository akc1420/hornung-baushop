import template from './sisi-fields-page.html.twig';

const {Component, Mixin} = Shopware;
const {Criteria} = Shopware.Data;
const httpClient = Shopware.Application.getContainer('init').httpClient;

Component.register('sisi-fields-page', {
    template,
    name: 'SisiPage',

});
