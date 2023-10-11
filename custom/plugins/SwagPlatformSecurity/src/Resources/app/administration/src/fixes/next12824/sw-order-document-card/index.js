if (Shopware.Service('swagSecurityState').isActive('NEXT-12824')) {

    Shopware.Component.override('sw-order-document-card', {

        methods: {

            downloadDocument(documentId, documentDeepLink) {
                this.documentService.getDocument(
                    documentId,
                    documentDeepLink,
                    Shopware.Context.api,
                    true
                ).then((response) => {
                    if (response.data) {
                        let filename = documentId;
                        filename = response.headers['content-disposition'].split('filename=')[1];
                        const link = document.createElement('a');
                        document.getElementById('app').appendChild(link);
                        link.href = URL.createObjectURL(response.data);
                        link.download = filename;
                        link.dispatchEvent(new MouseEvent('click'));
                        link.parentElement.removeChild(link);

                    }
                });
            },

            onCreateDocument(params, additionalAction, referencedDocumentId = null, file = null) {
                this.showModal = false;
                this.$nextTick().then(() => {
                    return this.createDocument(
                        this.order.id,
                        this.currentDocumentType.technicalName,
                        params,
                        referencedDocumentId,
                        file
                    );
                }).then((response) => {
                    this.getList();
                    this.$emit('document-save');

                    if (additionalAction === 'download') {
                        this.downloadDocument(response.data.documentId, response.data.documentDeepLink);
                    }
                });
            },

            onPreview(params) {
                this.documentService.getDocumentPreview(
                    this.order.id,
                    this.order.deepLinkCode,
                    this.currentDocumentType.technicalName,
                    params
                ).then((response) => {
                    if (response.data) {
                        let filename = this.currentDocumentType.technicalName;
                        filename = response.headers['content-disposition'].split('filename=')[1];
                        const link = document.createElement('a');
                        document.getElementById('app').appendChild(link);
                        link.href = URL.createObjectURL(response.data);
                        link.download = filename;
                        link.dispatchEvent(new MouseEvent('click'));
                        link.parentElement.removeChild(link);
                    }
                });
            },

            onDownload(id, deepLink) {
                this.downloadDocument(id, deepLink);
            }
        }
    });
}
