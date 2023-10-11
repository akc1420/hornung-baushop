<?php declare(strict_types=1);

namespace TcinnThemeWareModern\Framework\Cookie;

use Shopware\Storefront\Framework\Cookie\CookieProviderInterface;

class CustomCookieProvider implements CookieProviderInterface {

    private CookieProviderInterface $originalService;

    public function __construct(CookieProviderInterface $service)
    {
        $this->originalService = $service;
    }

    private const singleCookie = [
        'snippet_name' => 'twt.cookie.localStorage.name',
        'snippet_description' => 'twt.cookie.localStorage.description',
        'cookie' => 'twt-local-storage',
        'value' => '1',
        'expiration' => '30'
    ];

    public function getCookieGroups(): array
    {
        $cookies = $this->originalService->getCookieGroups();

        foreach ($cookies as &$cookie) {
            if (!\is_array($cookie)) {
                continue;
            }

            if (!$this->isComfortFeaturesGroup($cookie)) {
                continue;
            }

            if (!\array_key_exists('entries', $cookie)) {
                continue;
            }

            $cookie['entries'][] = array_merge (
                self::singleCookie
            );
        }

        return $cookies;
    }

    private function isComfortFeaturesGroup(array $cookie): bool
    {
        return (\array_key_exists('snippet_name', $cookie) && $cookie['snippet_name'] === 'cookie.groupComfortFeatures');
    }

}
