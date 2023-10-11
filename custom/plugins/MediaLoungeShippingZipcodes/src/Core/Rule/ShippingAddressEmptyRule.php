<?php declare(strict_types=1);

namespace MediaLounge\ShippingZipcodes\Core\Rule;

use Shopware\Core\Framework\Rule\Rule;
use Shopware\Core\Framework\Rule\RuleScope;
use Symfony\Component\Validator\Constraints\Type;

class ShippingAddressEmptyRule extends Rule
{
    /**
     * @var bool
     */
    protected $isShippingAddressEmpty;

    public function __construct()
    {
        parent::__construct();

        $this->isShippingAddressEmpty = false;
    }

    public function getName(): string
    {
        return 'shipping_address_empty';
    }

    public function match(RuleScope $scope): bool
    {
        $isCurrentlyShippingAddressEmpty = true;
        if ($scope->getSalesChannelContext()->getShippingLocation()->getAddress()) {
            $isCurrentlyShippingAddressEmpty = false;
        }

        if ($this->isShippingAddressEmpty) {
            return $isCurrentlyShippingAddressEmpty;
        }

        return !$isCurrentlyShippingAddressEmpty;
    }

    public function getConstraints(): array
    {
        return [
            'isShippingAddressEmpty' => [ new Type('bool') ]
        ];
    }
}
