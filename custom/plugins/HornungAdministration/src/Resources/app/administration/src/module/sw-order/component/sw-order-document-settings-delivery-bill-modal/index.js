import template from "./sw-order-document-settings-delivery-bill-modal.html.twig";

const { Component } = Shopware;

Component.extend(
  "sw-order-document-settings-delivery-bill-modal",
  "sw-order-document-settings-modal",
  {
    template,

    data() {
      return {
        documentConfig: {
          custom: {
            deliveryDate: new Date().toISOString(),
            deliveryNoteDate: new Date().toISOString(),
          },
          documentNumber: 0,
          documentInvoiceNumber: 0,
          documentComment: "",
          documentDate: "",
          displayPrices: true,
        },
      };
    },

    created() {
      this.createdComponent();
    },

    methods: {
      async createdComponent() {
        const inoviceDoc = this.order.documents.find(
          (doc) => doc.config.name == "Rechnung"
        );
        if (inoviceDoc !== undefined) {
          this.documentNumberInvoicePreview = inoviceDoc.config.documentNumber;
          this.documentConfig.documentInvoiceNumber = this.documentNumberInvoicePreview;
        } else {
          const res = await this.numberRangeService.reserve(
            `document_invoice`,
            this.order.salesChannelId,
            false
          );

          this.documentNumberInvoicePreview = res.number;
          this.documentConfig.documentInvoiceNumber = this.documentNumberInvoicePreview;
        }

        const deliveryDoc = this.order.documents.find(
          (doc) => doc.config.name == "Lieferschein"
        );
        if (deliveryDoc !== undefined) {
          this.documentNumberPreview = deliveryDoc.config.documentNumber;
          this.documentConfig.documentNumber =
            deliveryDoc.config.documentNumber;
          this.documentConfig.documentDate = new Date().toISOString();
        } else {
          const res = await this.numberRangeService.reserve(
            `document_delivery_note`,
            this.order.salesChannelId,
            true
          );
          this.documentConfig.documentNumber = res.number;
          this.documentNumberPreview = this.documentConfig.documentNumber;
          this.documentConfig.documentDate = new Date().toISOString();
        }
      },

      async onCreateDocument(additionalAction = false) {
        if (
          this.documentNumberInvoicePreview ===
          this.documentConfig.documentInvoiceNumber
        ) {
          const inoviceDoc = this.order.documents.find(
            (doc) => doc.config.name == "Rechnung"
          );
          if (inoviceDoc !== undefined) {
            this.documentConfig.custom.documentInvoiceNumber =
              inoviceDoc.config.documentNumber;
          } else {
            const res = await this.numberRangeService.reserve(
              `document_invoice`,
              this.order.salesChannelId,
              false
            );
            this.documentConfig.custom.documentInvoiceNumber = res.number;
          }
        } else {
          this.documentConfig.custom.documentInvoiceNumber = this.documentConfig.documentInvoiceNumber;
        }

        if (this.documentNumberPreview === this.documentConfig.documentNumber) {
          const deliveryDoc = this.order.documents.find(
            (doc) => doc.config.name == "Lieferschein"
          );
          if (deliveryDoc !== undefined) {
            this.documentConfig.custom.deliveryNoteNumber =
              deliveryDoc.config.documentNumber;
          } else {
            const res = await this.numberRangeService.reserve(
              `document_delivery_note`,
              this.order.salesChannelId,
              false
            );
            this.documentConfig.custom.deliveryNoteNumber = res.number;
          }
        } else {
          this.documentConfig.custom.deliveryNoteNumber = this.documentConfig.documentNumber;
        }

        this.callDocumentCreate(additionalAction);
      },

      onPreview() {
        this.documentConfig.custom.deliveryNoteNumber = this.documentConfig.documentNumber;
        this.documentConfig.custom.documentInvoiceNumber = this.documentConfig.documentInvoiceNumber;
        this.$super("onPreview");
      },
    },
  }
);
