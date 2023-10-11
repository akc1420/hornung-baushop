const { Component, Mixin } = Shopware;
import template from './ott-idealo-api-test-button.html.twig';

Component.register('ott-idealo-api-test-button', {
    template,

    inject: ['OttIdealoApiClient'],

    mixins: [Mixin.getByName('notification')],

    data() {
        return {
            isLoading: false,
            isSaveSuccessful: false,
            label: this.$tc('ott-idealo-api-test-button.title'),
        };
    },

    computed: {
        pluginConfig() {
            return {
                clientId: document.getElementById(
                    'OttIdealoConnector.config.clientId'
                ).value,
                clientSecret: document.getElementById(
                    'OttIdealoConnector.config.clientSecret'
                ).value,
                isSandbox: document.getElementsByName(
                    'OttIdealoConnector.config.sandbox'
                )[0].checked,
            };
        },
    },

    methods: {
        saveFinish() {
            this.isSaveSuccessful = false;
        },

        check() {
            this.isLoading = true;
            this.OttIdealoApiClient.check(this.pluginConfig).then((res) => {
                if (res.success) {
                    this.isSaveSuccessful = true;
                    this.createNotificationSuccess({
                        title: this.$tc('ott-idealo-api-test-button.title'),
                        message: this.$tc('ott-idealo-api-test-button.success'),
                    });
                } else {
                    this.createNotificationError({
                        title: this.$tc('ott-idealo-api-test-button.title'),
                        message: this.$tc('ott-idealo-api-test-button.error'),
                    });
                }

                this.isLoading = false;
            });
        },
    },
});
