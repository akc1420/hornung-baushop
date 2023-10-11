import './extension/sw-settings-index';
import './components/sisi-backend-search-list';
import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

const {Module} = Shopware;

Module.register('sisi-backend-search', {
    type: 'plugin',
    name: 'sisiBackendSearch',
    title: 'sisi-backend-search.title',
    description: 'sisi-backend-search.title',
    version: '1.0.0',
    targetVersion: '1.0.0',
    color: '#9AA8B5',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {

        list: {
            component: 'sisi-backend-search-list',
            path: 'list'
        },
    },
    navigation: [ {
        id: 'sisi-backend-search',
        label: 'sisi-backend-search.initialSearchType',
        color: '#57D9A3',
        path: 'sisi.backend.search.list',
        icon: 'default-symbol-products',
        parent: 'sw-catalogue',
        privilege: 'product.viewer',
        position: 11
    }]

});
