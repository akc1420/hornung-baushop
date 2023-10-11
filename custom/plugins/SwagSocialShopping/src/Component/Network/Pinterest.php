<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagSocialShopping\Component\Network;

class Pinterest implements NetworkInterface
{
    private string $name = 'pinterest';

    public function getName(): string
    {
        return $this->name;
    }

    public function getTranslationKey(): string
    {
        return 'swag-social-shopping.networks.' . $this->getName();
    }

    public function getIconName(): string
    {
        return 'brand-pinterest';
    }
}
