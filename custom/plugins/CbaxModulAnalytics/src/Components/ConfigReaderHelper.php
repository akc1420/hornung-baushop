<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\RequestStack;
use Shopware\Core\PlatformRequest;

class ConfigReaderHelper
{
    const CONFIG_PATH = 'CbaxModulAnalytics';

	/**
	* @var SystemConfigService
	*/
    private $systemConfigService;

    /**
     * @var RequestStack
     */
    private $requestStack;

	public function __construct(
        SystemConfigService $systemConfigService,
        RequestStack $requestStack
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->requestStack = $requestStack;
    }

	public function getConfig()
    {
        $salesChannelId = null;
        $request = $this->requestStack->getCurrentRequest();

        if (!empty($request))
        {
            $context = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);
            if (!empty($context))
            {
                $salesChannelId = $context->getSalesChannel()->getId();
            }
        }

        $configs = $this->systemConfigService->get(self::CONFIG_PATH . '.config', $salesChannelId) ?? [];

        return $configs;
    }
}
