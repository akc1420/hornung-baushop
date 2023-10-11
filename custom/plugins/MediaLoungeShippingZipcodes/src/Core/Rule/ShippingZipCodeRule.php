<?php declare(strict_types=1);

namespace MediaLounge\ShippingZipcodes\Core\Rule;

use Shopware\Core\Checkout\CheckoutRuleScope;
use Shopware\Core\Framework\Rule\Exception\UnsupportedOperatorException;
use Shopware\Core\Framework\Rule\RuleScope;

class ShippingZipCodeRule extends \Shopware\Core\Checkout\Customer\Rule\ShippingZipCodeRule
{
    public function match(RuleScope $scope): bool
    {
        if (!$scope instanceof CheckoutRuleScope) {
            return false;
        }

        /** @var CheckoutRuleScope $scope */
        if (!$location = $scope->getSalesChannelContext()->getShippingLocation()->getAddress()) {
            return false;
        }

        $regexZipCodes = [];
        foreach ($this->zipCodes as $zipCode) {
            array_push($regexZipCodes,
                '/^' .
                (substr($zipCode, -1) == '*' ? substr($zipCode, 0, -1) : $zipCode . '$') .
                '/'
            );
        }
        $cleanZipCode = preg_replace('/\s+/', '', strtoupper($location->getZipcode()));
        switch ($this->operator) {
            case self::OPERATOR_EQ:
                $matched = false;
                foreach ($regexZipCodes as $pattern) {
                    if (preg_match($pattern, $cleanZipCode)) {
                        $matched = true;
                    }
                };
                return $matched;

            case self::OPERATOR_NEQ:
                $matched = true;
                foreach ($regexZipCodes as $pattern) {
                    if (preg_match($pattern, $cleanZipCode)) {
                        $matched = false;
                    }
                };
                return $matched;

            default:
                throw new UnsupportedOperatorException($this->operator, self::class);
        }
    }
}
