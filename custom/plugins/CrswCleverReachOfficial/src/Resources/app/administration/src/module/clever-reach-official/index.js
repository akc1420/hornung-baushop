import './component/clever-reach-container';
import './component/clever-reach-content-window-wrapper';
import './component/clever-reach-iframe';
import './component/clever-reach-banner';
import './component/clever-reach-fonts';
import './component/clever-reach-spinner';
import './component/clever-reach-error';
import './component/clever-reach-settings';
import './page/clever-reach-router';
import './page/clever-reach-welcome';
import './page/clever-reach-refresh';
import './page/clever-reach-dashboard';
import './page/clever-reach-autoConfig';
import './page/clever-reach-autoConfig-error';
import './page/clever-reach-syncSettings';
import './page/clever-reach-forms';
import './page/clever-reach-abandoned-cart';
import './page/clever-reach-abandoned-cart-settings';
import deDE from './snippet/de-DE.json';
import deCH from './snippet/de-CH.json';
import deAT from './snippet/de-AT.json';
import enGB from './snippet/en-GB.json';
import frFR from './snippet/fr-FR.json';
import esES from './snippet/es-ES.json';
import itIT from './snippet/it-IT.json';

Shopware.Module.register('clever-reach-official', {
    type: 'plugin',
    name: 'clever-reach.basic.label',
    title: 'clever-reach.basic.label',
    description: 'clever-reach.basic.description',

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
            component: 'clever-reach-router',
            path: ':page?'
        },
        detail: {
            component: 'clever-reach-abandoned-cart-settings',
            path: 'abandonedcart/:shopName/:shopId',

            props: {
                default(route) {
                    return {
                        shopId: route.params.shopId,
                        shopName: route.params.shopName
                    };
                }
            }
        }
    },

    navigation: [{
        label: 'clever-reach.basic.label',
        color: '#EC6702',
        path: 'clever.reach.official.index',
        parent: 'sw-marketing'
    }]
});
