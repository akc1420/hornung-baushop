import SwagCmsExtensionsFormValidationService from '../core/service/api/form-validation.service';

const initContainer = Shopware.Application.getContainer('init');

if (Shopware.Feature.isActive('FEATURE_SWAGCMSEXTENSIONS_63')) {
    Shopware.Application.addServiceProvider('SwagCmsExtensionsFormValidationService', (container) => {
        return new SwagCmsExtensionsFormValidationService(initContainer.httpClient, container.loginService);
    });
}
