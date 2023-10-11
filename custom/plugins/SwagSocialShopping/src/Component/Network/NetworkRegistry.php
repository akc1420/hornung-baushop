<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagSocialShopping\Component\Network;

use SwagSocialShopping\Exception\NetworkNotFoundException;

class NetworkRegistry implements NetworkRegistryInterface
{
    private iterable $networks;

    private ?array $networksByName = null;

    public function __construct(iterable $networks)
    {
        $this->networks = $networks;
    }

    public function getNetworkByName(string $networkName): NetworkInterface
    {
        $network = $this->getSortedNetworks()[$networkName] ?? null;

        if (!($network instanceof NetworkInterface)) {
            throw new NetworkNotFoundException($networkName);
        }

        return $network;
    }

    /**
     * @return iterable|NetworkInterface[]
     * @deprecated tag:v3.2.0 - Return type annotation will be changed to iterable&NetworkInterface[]
     */
    public function getNetworks(): iterable
    {
        return $this->networks;
    }

    private function getSortedNetworks(): array
    {
        if (isset($this->networksByName)) {
            return $this->networksByName;
        }

        $this->networksByName = [];
        foreach ($this->networks as $network) {
            $this->networksByName[\get_class($network)] = $network;
        }

        return $this->networksByName;
    }
}
