<?php

namespace Crsw\CleverReachOfficial\Service\BusinessLogic\SalesChannel;

use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceInterface;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceParameters;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;

class SalesChannelContextService
{
    /**
     * @var SalesChannelContextServiceInterface
     */
    private $contextService;
    /**
     * @var ParameterBagInterface
     */
    private $params;

    /**
     * SalesChannelContextService constructor.
     *
     * @param SalesChannelContextServiceInterface $contextService
     * @param ParameterBagInterface $params
     */
    public function __construct(SalesChannelContextServiceInterface $contextService, ParameterBagInterface $params)
    {
        $this->contextService = $contextService;
        $this->params = $params;
    }

    /**
     * Retrieves sales channel context
     *
     * @param Request $request
     * @return mixed
     */
    public function getSalesChannelContext(Request $request)
    {
        if (version_compare($this->params->get('kernel.shopware_version'), '6.4.0', 'lt')) {
            $context = $this->contextService->get(
                $request->attributes->get('sw-sales-channel-id'),
                $request->headers->get('sw-context-token'),
                $request->headers->get('sw-language-id')
            );
        } else {
            $context = $this->contextService->get(
                new SalesChannelContextServiceParameters(
                    $request->attributes->get('sw-sales-channel-id'),
                    $request->headers->get('sw-context-token') ?? '',
                    $request->headers->get('sw-language-id')
                )
            );
        }

        return $context;
    }
}