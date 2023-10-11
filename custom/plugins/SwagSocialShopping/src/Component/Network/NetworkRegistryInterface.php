<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagSocialShopping\Component\Network;

interface NetworkRegistryInterface
{
    public function getNetworkByName(string $networkName): NetworkInterface;

    public function getNetworks(): iterable;
}
