const { Application } = Shopware;
import '../core/component/medialounge-shipping-address-empty';

Application.addServiceProviderDecorator('ruleConditionDataProviderService', (ruleConditionService) => {
    ruleConditionService.addCondition('shipping_address_empty', {
        component: 'medialounge-shipping-address-empty',
        label: 'Shipping address empty',
        scopes: ['checkout']
    });

    return ruleConditionService;
});
