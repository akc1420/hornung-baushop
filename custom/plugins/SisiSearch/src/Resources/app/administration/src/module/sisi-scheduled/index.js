import './scheduled/sisi-scheduled-list';
import './scheduled/sisi-scheduled-detail';
import './scheduled/sisi-scheduled-create';

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

const {Module} = Shopware;

Module.register('sisi-scheduled', {
    type: 'plugin',
    name: 'sisi-scheduled',
    title: 'sisi-scheduled.list.modul',
    description: 'search sisi-scheduled',
    color: '#ff3d58',
    icon: 'default-shopping-paper-bag-product',
    entity: 'sisi_search_es_scheduledtask',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        list: {
            component: 'sisi-scheduled-list',
            path: 'list'
        },
        detail: {
            component: 'sisi-scheduled-detail',
            path: 'detail/:id',
            meta: {
                parentPath: 'sisi-scheduled-list'
            }
        },
        create: {
            component: 'sisi-scheduled-create',
            path: 'create',
            meta: {
                parentPath: 'sisi.scheduled.list'
            }
        }
    },
    navigation: [{
        id: 'sisi-scheduled',
        label: 'sisi-scheduled.initialSearchType',
        color: '#57D9A3',
        path: 'sisi.scheduled.list',
        icon: 'default-symbol-products',
        parent: 'sw-catalogue',
        privilege: 'product.viewer',
        position: 12
    }]

});
