<?php

declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT22891;

use Shopware\Core\Content\Newsletter\SalesChannel\AbstractNewsletterSubscribeRoute;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\NoContentResponse;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class NewsletterSubscribeRouteDecorator extends AbstractNewsletterSubscribeRoute
{
    private const REQUEST_OPTION = 'option';
    private const OPTION_DOI_ENABLED = 'subscribe';
    private const OPTION_DOI_DISABLED = 'direct';

    /**
     * @var AbstractNewsletterSubscribeRoute
     */
    private $decorated;

    /**
     * @var SystemConfigService
     */
    private $systemConfig;

    public function __construct(AbstractNewsletterSubscribeRoute $decorated, SystemConfigService $systemConfig)
    {
        $this->decorated = $decorated;
        $this->systemConfig = $systemConfig;
    }

    /**
     * @RouteScope(scopes={"store-api"});
    */
    public function subscribe(RequestDataBag $dataBag, SalesChannelContext $context, bool $validateStorefrontUrl = true): NoContentResponse
    {
        $option = $dataBag->get(self::REQUEST_OPTION);

        $subscribeActions = [
            self::OPTION_DOI_DISABLED,
            self::OPTION_DOI_ENABLED,
        ];

        if (\in_array($option, $subscribeActions)) {
            $dataBag->set(
                self::REQUEST_OPTION,
                ((bool) $this->systemConfig->get('core.newsletter.doubleOptIn'))
                    ? self::OPTION_DOI_ENABLED
                    : self::OPTION_DOI_DISABLED
            );
        }

        return $this->decorated->subscribe($dataBag, $context, $validateStorefrontUrl);
    }

    public function getDecorated(): AbstractNewsletterSubscribeRoute
    {
        return $this->decorated;
    }
}
