const ApiService = Shopware.Classes.ApiService;
const { Application } = Shopware;

class OttIdealoApiClient extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'ott-idealo-api-test') {
        super(httpClient, loginService, apiEndpoint);
    }

    check(values) {
        const headers = this.getBasicHeaders({});

        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/verify`, values, {
                headers,
            })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
}

Application.addServiceProvider('OttIdealoApiClient', (container) => {
    const initContainer = Application.getContainer('init');
    return new OttIdealoApiClient(
        initContainer.httpClient,
        container.loginService
    );
});
