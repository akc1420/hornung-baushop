import CleverreachApiService from './service/cleverreach-api.service';

const initContainer = Shopware.Application.getContainer('init');

Shopware.Application.addServiceProvider('cleverreachService', (container) => {
    return new CleverreachApiService(initContainer.httpClient, container.loginService);
});