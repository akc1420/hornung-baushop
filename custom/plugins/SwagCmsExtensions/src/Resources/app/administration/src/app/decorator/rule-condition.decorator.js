const { Application } = Shopware;

Application.addServiceProviderDecorator('ruleConditionDataProviderService', (ruleConditionService) => {
    ruleConditionService.addAwarenessConfiguration(
        'swagCmsExtensionsBlockRules',
        {
            notEquals: [
                'timeRange',
            ],
            snippet: 'sw-restricted-rules.restrictedAssignment.swagCmsExtensionsBlockRules',
        },
    );

    return ruleConditionService;
});
