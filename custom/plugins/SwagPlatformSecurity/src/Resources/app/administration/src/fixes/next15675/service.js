const ApiService = Shopware.Classes.ApiService;

export default class ApiClient extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'swag-security') {
        super(httpClient, loginService, apiEndpoint);
    }

    getAccessToken(id) {
        const headers = this.getBasicHeaders({});

        return this.httpClient
            .get(`${this.getApiBasePath()}/_action/regenerate-import-file-key/${id}`, {
                headers
            })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
}
