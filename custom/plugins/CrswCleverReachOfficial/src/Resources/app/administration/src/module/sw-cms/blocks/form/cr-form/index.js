import './component';
import './preview';

Shopware.Service('cmsService').registerCmsBlock({
    name: 'cr-form',
    label: 'CleverReach Forms',
    category: 'form',
    component: 'sw-cms-block-cr-form',
    previewComponent: 'sw-cms-preview-cr-form',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: '20px',
        marginRight: '20px',
        sizingMode: 'boxed'
    },
    slots: {
        content: 'cr-form'
    }
});
