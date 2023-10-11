import template from "./sw-order-document-settings-delivery-note-paypal-modal.html.twig";

const { Component } = Shopware;

Component.extend(
  "sw-order-document-settings-delivery-note-paypal-modal",
  "sw-order-document-settings-delivery-note-modal",
  {
    template,

    created() {
      console.log(1234);
      this.createdComponent();
    },

    methods: {
      onCreateDocument(additionalAction = false) {
        console.log("CREATE");
        if (this.documentNumberPreview === this.documentConfig.documentNumber) {
          this.numberRangeService
            .reserve(`document_delivery_note`, this.order.salesChannelId, false)
            .then((response) => {
              this.documentConfig.custom.deliveryNoteNumber = response.number;
              this.callDocumentCreate(additionalAction);
            });
        } else {
          this.documentConfig.custom.deliveryNoteNumber = this.documentConfig.documentNumber;
          this.callDocumentCreate(additionalAction);
        }
      },

      onPreview() {
        console.log("PREVIEW");
        this.documentConfig.custom.deliveryNoteNumber = this.documentConfig.documentNumber;
        this.$super("onPreview");
      },
    },
  }
);
