<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagSocialShopping\Component\Validation;

use Shopware\Core\Framework\Struct\Struct;

class NetworkValidationError extends Struct
{
    private string $errorKey;

    private array $params;

    public function __construct(string $errorKey, array $params = [])
    {
        $this->errorKey = $errorKey;
        $this->params = $params;
    }

    public function getErrorKey(): string
    {
        return $this->errorKey;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function jsonSerialize(): array
    {
        return [
            'error' => $this->getErrorKey(),
            'params' => $this->getParams(),
        ];
    }
}
