const {ApiService} = Shopware.Classes;

class SisiApiCredentialsService extends ApiService {

    constructor(httpClient, loginService, apiEndpoint = '') {
        super(httpClient, loginService, apiEndpoint);
    }

    testConfig(config) {
        const apiRoute = '_action/sisi/sisisearch';
        return this.httpClient.post(
            apiRoute,
            {
                config
            },
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getStatus(config) {
        const apiRoute = '_action/sisi/sisisearch/status';
        return this.httpClient.post(
            apiRoute,
            {
                config
            },
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });

    }

    delete(config) {
        const apiRoute = '_action/sisi/sisisearch/delete';
        return this.httpClient.post(
            apiRoute,
            {
                config
            },
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    Inaktive(config) {
        const apiRoute = '_action/sisi/sisisearch/inaktive';
        return this.httpClient.post(
            apiRoute,
            {
                config
            },
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    channels() {
        const apiRoute = '_action/sisi/sisisearch/channel';
        return this.httpClient.post(
            apiRoute,
            {},
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    channelsWithlanguage() {
        const apiRoute = '_action/sisi/backend/shop';
        return this.httpClient.post(
            apiRoute,
            {},
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    history(config) {
        const apiRoute = '_action/sisi/sisisearch/history';
        return this.httpClient.post(
            apiRoute,
            {
                config: config
            },
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    sisisearch(config) {
        const apiRoute = '_action/sisi/backend/search';

        return this.httpClient.post(
            apiRoute, {
                config: config
            }, {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return response;
        });
    }
}

export default SisiApiCredentialsService;
