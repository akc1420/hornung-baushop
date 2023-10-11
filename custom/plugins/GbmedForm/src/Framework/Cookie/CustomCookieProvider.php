<?php declare(strict_types=1);
/**
 * gb media
 * All Rights Reserved.
 *
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * The content of this file is proprietary and confidential.
 *
 * @category       Shopware
 * @package        Shopware_Plugins
 * @subpackage     GbmedForm
 * @copyright      Copyright (c) 2020, gb media
 * @license        proprietary
 * @author         Giuseppe Bottino
 * @link           http://www.gb-media.biz
 */

namespace Gbmed\Form\Framework\Cookie;

use Shopware\Storefront\Framework\Cookie\CookieProviderInterface;

class CustomCookieProvider implements CookieProviderInterface
{
    /** @var array */
    private const cookieGroup = [
        'isRequired' => true,
        'snippet_name' => 'cookie.groupRequired',
        'snippet_description' => 'cookie.groupRequiredDescription',
        'entries' => [
            [
                'snippet_name' => 'gbmed-form.cookie.groupRequiredRecaptch',
                'cookie' => 'recaptcha-',
            ]
        ],
    ];

    /** @var CookieProviderInterface */
    private $originalService;

    /**
     * CustomCookieProvider constructor.
     * @param CookieProviderInterface $service
     */
    public function __construct(CookieProviderInterface $service)
    {
        $this->originalService = $service;
    }

    /**
     * @return array
     */
    public function getCookieGroups(): array
    {
        /** @var array $cookieGroups */
        $cookieGroups = $this->originalService->getCookieGroups();
        $groupRequiredKey = array_search(true, array_column($cookieGroups, 'isRequired'));
        $cookieGroups[$groupRequiredKey]['entries'] = array_merge($cookieGroups[$groupRequiredKey]['entries'], self::cookieGroup['entries']);

        return $cookieGroups;
    }
}
