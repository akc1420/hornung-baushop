import './component';
import './preview';

if (Shopware.Feature.isActive('FEATURE_SWAGCMSEXTENSIONS_63')) {
    Shopware.Service('cmsService').registerCmsBlock({
        name: 'custom-form',
        label: 'swag-cms-extensions.sw-cms.elements.custom-form.label',
        category: 'form',
        component: 'sw-cms-block-custom-form',
        previewComponent: 'swag-cms-extensions-preview-custom-form',
        defaultConfig: {
            marginBottom: '20px',
            marginTop: '20px',
            marginLeft: '20px',
            marginRight: '20px',
            sizingMode: 'boxed',
        },
        slots: {
            content: {
                type: 'custom-form',
            },
        },
    });
}
