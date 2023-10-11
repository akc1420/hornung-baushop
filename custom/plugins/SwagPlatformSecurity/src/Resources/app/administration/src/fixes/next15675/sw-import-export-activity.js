import templateUpper62 from './sw-import-export-activity.twig';
import template61 from './sw-import-export-activity-old.twig';

if (Shopware.Service('swagSecurityState').isActive('NEXT-15675')) {
    Shopware.Component.override('sw-import-export-activity', {
        template: Shopware.Context.app.config.version.indexOf('6.1') === 0 ? template61 : templateUpper62,
        methods: {
            async openDownload(id) {
                const service = Shopware.Service('next15675Service');
                const res = await service.getAccessToken(id);

                const baseUrl = `${Shopware.Context.api.apiResourcePath}`;
                const url = `${baseUrl}/_action/import-export/file/download?fileId=${id}&accessToken=${res.token}`;

                window.open(url, '_blank')
            }
        }
    });
}
