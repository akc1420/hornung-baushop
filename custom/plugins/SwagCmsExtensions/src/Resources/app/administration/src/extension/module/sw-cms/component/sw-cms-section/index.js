import template from './sw-cms-section.html.twig';

const { Component } = Shopware;

Component.override('sw-cms-section', {
    template,
});
