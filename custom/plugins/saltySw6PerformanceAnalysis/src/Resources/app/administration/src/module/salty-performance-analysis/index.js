import './page/salty-performance-analysis-overview';
import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

Shopware.Module.register('salty-performance-analysis', {
    type: 'plugin',
    name: 'PerformanceAnalysis',
    title: 'saltyPerformanceAnalysis.title',
    description: '',
    color: '#62ff80',
    icon: 'default-web-bug',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        overview: {
            component: 'salty-performance-analysis-overview',
            path: 'overview'
        }
    },

    navigation: [{
        id: 'salty.performance.analysis',
        label: 'saltyPerformanceAnalysis.title',
        color: '#62ff80',
        path: 'salty.performance.analysis.overview',
        parent: 'sw-settings',
    }]
});
