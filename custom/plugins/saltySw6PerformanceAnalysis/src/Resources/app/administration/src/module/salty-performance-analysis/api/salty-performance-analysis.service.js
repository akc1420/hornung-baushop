const { Application } = Shopware;
const ApiService = Shopware.Classes.ApiService;

class SaltyPerformanceAnalysisService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'salty-performance-analysis') {
        super(httpClient, loginService, apiEndpoint);
    }

    /**
     * @return {Promise<AxiosResponse<T>>}
     */
    getShopwareConfigurationInformation() {
        const apiRoute = `_action/${this.getApiBasePath()}/shopware-configuration`;

        return this.httpClient
            .get(
                apiRoute,
                null,
                {
                    headers: this.getBasicHeaders()
                }
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    /**
     * @return {Promise<AxiosResponse<T>>}
     */
    getServerConfigurationInformation() {
        const apiRoute = `_action/${this.getApiBasePath()}/server-configuration`;

        return this.httpClient
            .get(
                apiRoute,
                null,
                {
                    headers: this.getBasicHeaders()
                }
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
}

Application.addServiceProvider('SaltyPerformanceAnalysisService', (container) => {
    const initContainer = Application.getContainer('init');

    return new SaltyPerformanceAnalysisService(initContainer.httpClient, container.loginService);
});
