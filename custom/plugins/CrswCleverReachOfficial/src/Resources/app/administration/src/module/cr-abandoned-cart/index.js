
import deDE from './snippet/de-DE.json';
import deCH from './snippet/de-CH.json';
import deAT from './snippet/de-AT.json';
import enGB from './snippet/en-GB.json';
import frFR from './snippet/fr-FR.json';
import esES from './snippet/es-ES.json';
import itIT from './snippet/it-IT.json';

import './page/cr-abandoned-cart-list';

Shopware.Module.register('cr-abandoned-cart', {
    type: 'plugin',
    name: 'cr-abandoned-cart.abandonedCart.title',
    title: 'cr-abandoned-cart.abandonedCart.title',
    description: 'cr-abandoned-cart.basic.description',

    snippets: {
        'de-DE': deDE,
        'de-CH': deCH,
        'de-AT': deAT,
        'en-GB': enGB,
        'fr-FR': frFR,
        'es-ES': esES,
        'it-IT': itIT,
    },

    routes: {
        index: {
            component: 'cr-abandoned-cart-list',
            path: 'index',
        },
    },

    navigation: [{
        label: 'cr-abandoned-cart.abandonedCart.title',
        color: '#EC6702',
        path: 'cr.abandoned.cart.index',
        parent: 'sw-order'
    }]
});
