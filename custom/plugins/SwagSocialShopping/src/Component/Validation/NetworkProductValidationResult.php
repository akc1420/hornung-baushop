<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagSocialShopping\Component\Validation;

class NetworkProductValidationResult
{
    private NetworkValidationErrorCollection  $errors;

    private string $validatorName;

    public function __construct(string $validatorName, ?NetworkValidationErrorCollection $errors = null)
    {
        $this->validatorName = $validatorName;

        if ($errors === null) {
            $errors = new NetworkValidationErrorCollection();
        }
        $this->errors = $errors;
    }

    public function getErrors(): NetworkValidationErrorCollection
    {
        return $this->errors;
    }

    public function getValidatorName(): string
    {
        return $this->validatorName;
    }

    public function hasErrors(): bool
    {
        return $this->errors->count() > 0;
    }
}
