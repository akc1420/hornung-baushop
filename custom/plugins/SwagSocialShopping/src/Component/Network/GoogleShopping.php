<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagSocialShopping\Component\Network;

class GoogleShopping implements NetworkInterface
{
    public function getName(): string
    {
        return 'google-shopping';
    }

    public function getTranslationKey(): string
    {
        return 'swag-social-shopping.networks.google-shopping';
    }

    public function getIconName(): string
    {
        return 'brand-google';
    }
}
