import './fields/sisi-fields-list';
import './fields/sisi-fields-detail';
import './fields/sisi-fields-create';
import './fields/sisi-fields-index';
import './fields/sisi-fields-page';
import './fields/sisi-fields-dashboard';
import '../../component/sisi-search-icon';



import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

const {Module} = Shopware;

Module.register('sisi-fields', {
    type: 'plugin',
    name: 'sisi-fields',
    title: 'ssisi-fields.list.modul',
    description: 'search sisi-fields',
    color: '#ff3d58',
    icon: 'default-shopping-paper-bag-product',
    entity: 'sisi_es_fields',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        list: {
            component: 'sisi-fields-list',
            path: 'list'
        },
        detail: {
            component: 'sisi-fields-detail',
            path: 'detail/:id',
            meta: {
                parentPath: 'sisi-fields-list'
            }
        },
        create: {
            component: 'sisi-fields-create',
            path: 'create',
            meta: {
                parentPath: 'sisi.fields.list'
            }
        },
        index: {
            component: 'sisi-fields-index',
            path: 'index'
        },
        page: {
            component: 'sisi-fields-page',
            path: 'page'
        },
        dashboard: {
            component: 'sisi-fields-dashboard',
            path: 'dashboard'
        },
    },
    settingsItem: {
        group: 'plugins',
        to: 'sisi.fields.page',
        iconComponent: 'sisi-search-icon',
        backgroundEnabled: true,
        privilege: 's_plugin_sisi_xml_modul_products_config.viewer'
    },

    navigation: [{
        id: 'sisi-search',
        label: 'ssisi-fields.initialSearchType',
        color: '#57D9A3',
        path: 'sisi.fields.page',
        icon: 'default-symbol-products',
        parent: 'sw-catalogue',
        privilege: 'product.viewer',
        position: 11
    }]

});
