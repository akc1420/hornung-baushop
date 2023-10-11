<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Route;

use Shopware\Core\Framework\Struct\Struct;

class FormRouteResponseStruct extends Struct
{
    protected string $successMessage;

    public function getApiAlias(): string
    {
        return 'custom_form_result';
    }

    public function getSuccessMessage(): string
    {
        return $this->successMessage;
    }
}
