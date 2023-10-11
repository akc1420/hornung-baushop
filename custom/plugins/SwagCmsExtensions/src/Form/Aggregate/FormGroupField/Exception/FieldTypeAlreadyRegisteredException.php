<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Aggregate\FormGroupField\Exception;

use Shopware\Core\Framework\ShopwareHttpException;

class FieldTypeAlreadyRegisteredException extends ShopwareHttpException
{
    public function __construct(string $fieldType)
    {
        $message = \sprintf('Form field type "%s" already registered.', $fieldType);
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return 'SWAG_CMS_EXTENSIONS__FORM_FIELD_TYPE_ALREADY_REGISTERED';
    }
}
