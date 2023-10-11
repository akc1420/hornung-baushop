const {ApiService} = Shopware.Classes;

class SisiElasticSearchIndexService extends ApiService {

    constructor(httpClient, loginService, apiEndpoint = '') {
        super(httpClient, loginService, apiEndpoint);
    }

    deleteIndex(indexName) {
        const apiRoute = '_action/sisi/elasticsearch/deleteIndex';
        return this.httpClient.post(
            apiRoute,
            {
                indexName: indexName
            },
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getEsIndexes(salesChannelId) {
        const apiRoute = '_action/sisi/elasticsearch/getIndexes';
        return this.httpClient.post(
            apiRoute, {salesChannelId}, {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getStatus(salesChannelId) {
        const apiRoute = '_action/sisi/elasticsearch/getStatus';
        return this.httpClient.post(
            apiRoute, {salesChannelId}, {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }
}

export default SisiElasticSearchIndexService;
