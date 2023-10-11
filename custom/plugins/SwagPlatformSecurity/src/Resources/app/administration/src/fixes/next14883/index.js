import template from './sw-integration.html.twig';

if (Shopware.Service('swagSecurityState').isActive('NEXT-14883')) {
    Shopware.Component.override('sw-integration-list', {
        template
    });
}

