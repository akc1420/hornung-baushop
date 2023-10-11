const ApiService = Shopware.Classes.ApiService;

class CleverreachApiService extends ApiService {
    constructor(httpClient, loginService,) {
        super(httpClient, loginService, 'sendcloud');
        this.name = 'sendcloudService';
    }

    getServices() {
        return this.httpClient.get(`/cleverreach/syncsettings/getservices`,
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    saveSyncSettings(body) {
        return this.httpClient.post(`/cleverreach/syncsettings/save`,
            body,
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getTime(body) {
        return this.httpClient.post(`/cleverreach/actime/get`,
            body,
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    saveTime(body) {
        return this.httpClient.post(`/cleverreach/actime/save`,
            body,
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getAccountData() {
        return this.httpClient.get(`/cleverreach/dashboard/getAccountData`,
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    disconnect() {
        return this.httpClient.get(`/cleverreach/disconnect`,
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getInitialSyncStatus() {
        return this.httpClient.get(`/cleverreach/initialSync`,
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getDashboardSyncStatus() {
        return this.httpClient.get(`/cleverreach/dashboard/getSyncStatus`,
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getSettingsSyncStatus() {
        return this.httpClient.get(`/cleverreach/syncsettings/getSyncStatus`,
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getForms() {
        return this.httpClient.get(`/cleverreach/forms/get`,
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getRedirectUrl(body) {
        return this.httpClient.post(`/cleverreach/singleSignOn`,
            body,
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getCurrentRoute() {
        return this.httpClient.get(`/cleverreach/router`,
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getDefaultForm() {
        return this.httpClient.get(`/cleverreach/forms/getDefault`,
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    forceSync() {
        return this.httpClient.get(`/cleverreach/syncsettings/force`,
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    retrySync(body) {
        return this.httpClient.post(`/cleverreach/retry`,
            body,
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getOrdersData() {
        return this.httpClient.get(`/cleverreach/orderSync/get`,
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getOrderProgress() {
        return this.httpClient.get(`/cleverreach/orderSync/progress`,
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    orderSync() {
        return this.httpClient.get(`/cleverreach/orderSync`,
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getAbandonedCartStatus(body) {
        return this.httpClient.post(`/cleverreach/abandonedcart/status/index`,
            body,
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getAbandonedCartUrl(body) {
        return this.httpClient.post(`/cleverreach/abandonedcart/theastatus/url`,
            body,
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getAbandonedCartTheaStatus(body) {
        return this.httpClient.post(`/cleverreach/abandonedcart/theastatus/index`,
            body,
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    changeAbandonedCartStatus(status, body) {
        return this.httpClient.post(`/cleverreach/abandonedcart/status/${status}`,
            body,
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    changeDoiStatus(status, body) {
        return this.httpClient.post(`/cleverreach/doi/${status}`,
            body,
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    chooseDoiForm(body) {
        return this.httpClient.post(`/cleverreach/doi/chooseform`,
            body,
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getDoiStatus(body) {
        return this.httpClient.post(`/cleverreach/doi/status`,
            body,
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getSyncStatistics() {
        return this.httpClient.get(`/cleverreach/dashboard/getSyncStatistics`,
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getAuthUrl(type) {
        return this.httpClient.get(`/cleverreach/iframe/url/${type}`,
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getDoiForms() {
        return this.httpClient.get(`/cleverreach/doi/forms`,
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    startAutoConfig() {
        return this.httpClient.get(`/cleverreach/autoconfig`,
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getShops() {
        return this.httpClient.get(`/cleverreach/shops/get`,
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getNumberOfReceivers() {
        return this.httpClient.get(`/cleverreach/syncsettings/getNumberOfReceivers`,
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getAbandonedCartRecords(body) {
        return this.httpClient.post(`/cleverreach/abandonedcart/records`,
            body,
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    deleteAbandonedCartRecord(id) {
        return this.httpClient.delete(`/cleverreach/abandonedcart/remove/` + id,
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    triggerAutomation(id) {
        let body = {'recordId': id};
        return this.httpClient.post(`/cleverreach/abandonedcart/trigger`,
            body,
            {headers: this.getBasicHeaders()}
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }
}

export default CleverreachApiService;