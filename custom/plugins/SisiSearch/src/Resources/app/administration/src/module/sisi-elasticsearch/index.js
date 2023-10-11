import './components/sisi-elasticsearch-list'
import deDE from './snippet/de-DE.json'
import enGB from './snippet/en-GB.json'

const { Module } = Shopware

Module.register('sisi-elasticsearch', {
    type: 'plugin',
    name: 'sisiElastcsearch',
    title: 'sisi-elasticsearch.title',
    description: 'sisi-elasticsearch.title',
    version: '1.0.0',
    targetVersion: '1.0.0',
    color: '#9aa8b5',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        list: {
            component: 'sisi-elasticsearch-list',
            path: 'list'
        },
    }
})
