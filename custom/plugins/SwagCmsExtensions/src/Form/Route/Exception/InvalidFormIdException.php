<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Route\Exception;

use Shopware\Core\Framework\ShopwareHttpException;

class InvalidFormIdException extends ShopwareHttpException
{
    public function __construct(string $formId)
    {
        $message = \sprintf('Form "%s" could not be found and the content could not be sent.', $formId);
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return 'SWAG_CMS_EXTENSIONS__FORM_ID_INVALID';
    }
}
