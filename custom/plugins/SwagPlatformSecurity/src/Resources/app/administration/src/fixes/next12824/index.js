import './sw-order-document-card';

if (Shopware.Service('swagSecurityState').isActive('NEXT-12824')) {
    Shopware.Service('documentService').getDocumentPreview = (orderId, orderDeepLink, documentTypeName, params) => {
        const config = JSON.stringify(params);

        return Shopware.Service('documentService').httpClient
            .get(
                `/_action/order/${orderId}/${orderDeepLink}/document/${documentTypeName}/preview?config=${config}`,
                {
                    responseType: 'blob',
                    headers: Shopware.Service('documentService').getBasicHeaders()
                }
            );
    }

    Shopware.Service('documentService').getDocument = (documentId, documentDeepLink, context, download = false) => {
        return Shopware.Service('documentService').httpClient
            .get(
                `/_action/document/${documentId}/${documentDeepLink}${download ? '?download=1' : ''}`,
                {
                    responseType: 'blob',
                    headers: Shopware.Service('documentService').getBasicHeaders()
                }
            );
    }
}
