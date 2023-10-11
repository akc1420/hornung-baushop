import './component';
import './preview';
import './config';

if (Shopware.Feature.isActive('FEATURE_SWAGCMSEXTENSIONS_63')) {
    Shopware.Service('cmsService').registerCmsElement({
        name: 'custom-form',
        category: 'form',
        label: 'swag-cms-extensions.sw-cms.elements.custom-form.label',
        component: 'swag-cms-extensions-custom-form-element',
        configComponent: 'swag-cms-extensions-custom-form-element-config',
        previewComponent: 'swag-cms-extensions-custom-form-element-preview',
        defaultConfig: {
            type: {
                source: 'static',
                value: 'custom-form',
            },
        },
    });
}
