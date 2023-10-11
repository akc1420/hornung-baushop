import template from './swag-cms-extensions-block-config-rule.html.twig';
import './swag-cms-extensions-block-config-rule.scss';

const { Component, Application: { view: { setReactive } } } = Shopware;

Component.register('swag-cms-extensions-block-config-rule', {
    template,

    inject: [
        'repositoryFactory',
        'acl',
        'feature',
    ],

    model: {
        prop: 'block',
        event: 'block-update',
    },

    props: {
        block: {
            type: Object,
            required: true,
        },
    },

    computed: {
        blockRuleRepository() {
            return this.repositoryFactory.create('swag_cms_extensions_block_rule');
        },

        blockRuleExtensionDefined() {
            return this.block.extensions.swagCmsExtensionsBlockRule !== undefined;
        },

        blockRule() {
            if (this.blockRuleExtensionDefined) {
                return this.block.extensions.swagCmsExtensionsBlockRule;
            }
            const rule = this.blockRuleRepository.create(Shopware.Context.api);
            rule.inverted = false;

            return rule;
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            if (!this.blockRuleExtensionDefined) {
                setReactive(this.block.extensions, 'swagCmsExtensionsBlockRule', this.blockRule);
                setReactive(this.block.extensions.swagCmsExtensionsBlockRule, 'visibilityRuleId', null);
                setReactive(this.block.extensions.swagCmsExtensionsBlockRule, 'inverted', false);
            }
        },

        onInvertedChange(state) {
            setReactive(this.block.extensions, 'swagCmsExtensionsBlockRule', this.blockRule);
            setReactive(this.block.extensions.swagCmsExtensionsBlockRule, 'inverted', state);
        },

        onSaveRule(ruleId) {
            this.blockRule.visibilityRuleId = ruleId;
        },

        onDismissRule() {
            this.blockRule.visibilityRuleId = null;
        },
    },
});
