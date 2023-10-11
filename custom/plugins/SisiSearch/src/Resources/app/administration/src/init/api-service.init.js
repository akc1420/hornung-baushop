import SisiApiCredentialsService from '../api/sisiApiCredentialsService';
import SisiElasticSearchIndexService from "../api/SisiElasticSearchIndexService";

const {Application} = Shopware;

Application.addServiceProvider('SisiApiCredentialsService', (container) => {
    const initContainer = Application.getContainer('init');
    return new SisiApiCredentialsService(initContainer.httpClient, container.loginService);
});

Application.addServiceProvider('SisiElasticSearchIndexService', (container) => {
    const initContainer = Application.getContainer('init');

    return new SisiElasticSearchIndexService(initContainer.httpClient, container.loginService);
});

