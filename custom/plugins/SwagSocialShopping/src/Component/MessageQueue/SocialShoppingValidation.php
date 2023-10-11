<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagSocialShopping\Component\MessageQueue;

class SocialShoppingValidation
{
    protected string $socialShoppingSalesChannelId;

    protected int $offset;

    public function __construct(string $socialShoppingSalesChannelId, int $offset = 0)
    {
        $this->socialShoppingSalesChannelId = $socialShoppingSalesChannelId;
        $this->offset = $offset;
    }

    public function getSocialShoppingSalesChannelId(): string
    {
        return $this->socialShoppingSalesChannelId;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }
}
