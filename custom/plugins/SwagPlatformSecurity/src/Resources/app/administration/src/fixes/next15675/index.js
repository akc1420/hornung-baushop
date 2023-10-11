import Next15675Service from './service';

const { Application } = Shopware;

if (Shopware.Service('swagSecurityState').isActive('NEXT-15675')) {
    Application.addServiceProvider('next15675Service', (container) => {
        const initContainer = Application.getContainer('init');
        return new Next15675Service(initContainer.httpClient, container.loginService);
    });
}

import './sw-import-export-activity';
import './sw-import-export-activity-detail-modal';
import './sw-import-export-progress';
