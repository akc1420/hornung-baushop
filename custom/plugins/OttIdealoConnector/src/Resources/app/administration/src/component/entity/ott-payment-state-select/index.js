const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.extend('ott-payment-state-select', 'sw-entity-single-select', {
    props: {
        criteria: {
            type: Object,
            required: false,
            default() {
                const criteria = new Criteria(1, this.resultLimit);
                criteria.addFilter(
                    Criteria.equals(
                        'stateMachine.technicalName',
                        'order_transaction.state'
                    )
                );
                return criteria;
            },
        },
    },
});
