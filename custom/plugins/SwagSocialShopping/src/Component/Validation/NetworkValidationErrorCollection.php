<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagSocialShopping\Component\Validation;

use Shopware\Core\Framework\Struct\Collection;

/**
 * @method void                               add(NetworkValidationError $element)
 * @method void                               set(string $key, NetworkValidationError $entity)
 * @method \Generator<NetworkValidationError> getIterator()
 * @method NetworkValidationError[]           getElements()
 * @method NetworkValidationError|null        get($key)
 * @method NetworkValidationError|null        first()
 * @method NetworkValidationError|null        last()
 */
class NetworkValidationErrorCollection extends Collection
{
    public function jsonSerialize(): array
    {
        $array = [];

        /** @var NetworkValidationError $element */
        foreach ($this->elements as $element) {
            $array[] = $element->jsonSerialize();
        }

        return $array;
    }

    protected function getExpectedClass(): string
    {
        return NetworkValidationError::class;
    }
}
