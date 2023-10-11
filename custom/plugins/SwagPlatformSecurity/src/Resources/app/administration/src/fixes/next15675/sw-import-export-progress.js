import template from './sw-import-export-progress.twig';

if (Shopware.Service('swagSecurityState').isActive('NEXT-15675')) {
    Shopware.Component.override('sw-import-export-progress', {
        template,
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
