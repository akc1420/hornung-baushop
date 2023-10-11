<?php

namespace Recommendy\Services;

use Shopware\Storefront\Framework\Cookie\CookieProviderInterface;

class RecCookieProviderService implements CookieProviderInterface
{
    public const REC_COOKIE_NAME = 'RecommendyTracking';

    private const REC_COOKIE = [
        'snippet_name' => 'Recommendy',
        'snippet_description' => 'Recommendy tracker',
        'cookie' => self::REC_COOKIE_NAME,
        'expiration' => '30',
        'value' => '1',
    ];

    private $originalService;

    public function __construct(CookieProviderInterface $service)
    {
        $this->originalService = $service;
    }

    public function getCookieGroups(): array
    {
        return array_merge(
            $this->originalService->getCookieGroups(),
            [
                self::REC_COOKIE
            ]
        );
    }
}
