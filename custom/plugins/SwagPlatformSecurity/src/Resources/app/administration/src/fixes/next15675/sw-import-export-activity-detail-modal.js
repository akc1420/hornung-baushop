import template from './sw-import-export-activity-detail-modal.twig';

if (Shopware.Service('swagSecurityState').isActive('NEXT-15675')) {
    Shopware.Component.override('sw-import-export-activity-detail-modal', {
        template,
        methods: {
            async openDownload(file) {
                const service = Shopware.Service('next15675Service');
                const res = await service.getAccessToken(file.id);

                const baseUrl = `${Shopware.Context.api.apiResourcePath}`;
                const url = `${baseUrl}/_action/import-export/file/download?fileId=${file.id}&accessToken=${res.token}`;

                window.open(url, '_blank')
            }
        }
    })
}
