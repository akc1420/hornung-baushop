const ApiService = Shopware.Classes.ApiService;

/**
 * Gateway for the API endpoint "swag_cms_extensions_form"
 *
 * @class
 * @extends ApiService
 */
class SwagCmsExtensionsFormValidationService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'swag/cms-extensions/form') {
        super(httpClient, loginService, apiEndpoint);
        this.name = 'SwagCmsExtensionsFormValidationService';
    }

    /**
     * @returns {Promise<T>}
     */
    validateForm(form) {
        const headers = {
            ...this.getBasicHeaders(),
            'sw-language-id': Shopware.Context.api.languageId,
        };

        return this.httpClient.post(
            `/_action/${this.getApiBasePath()}/validate`,
            form,
            { headers },
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    /**
     * @returns {Promise<T>}
     */
    validateAllForms(forms) {
        const headers = {
            ...this.getBasicHeaders(),
            'sw-language-id': Shopware.Context.api.languageId,
        };

        return this.httpClient.post(
            `/_action/${this.getApiBasePath()}/validateAll`,
            forms,
            { headers },
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }
}

export default SwagCmsExtensionsFormValidationService;
