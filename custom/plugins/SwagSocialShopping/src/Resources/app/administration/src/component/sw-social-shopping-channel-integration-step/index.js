const { Component } = Shopware;

Component.register('sw-social-shopping-channel-integration-step', {

    render(h) {
        return h(
            'ol',
            {
                class: 'sw-sales-channel-integration-card-step-by-step-list',
                domProps: {
                    innerHTML: this.steps,
                },
            },
        );
    },

    props: {
        steps: {
            type: String,
            require: true,
            default: '',
        },
    },

});
