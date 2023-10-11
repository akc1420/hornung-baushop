<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Component\Exception;

use Shopware\Core\Framework\ShopwareHttpException;

class UnsupportedSelectEntityException extends ShopwareHttpException
{
    public function __construct(string $entity)
    {
        $message = \sprintf('Entity "%s" is not supported in form select components and can not be displayed.', $entity);
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return 'SWAG_CMS_EXTENSIONS__FORM_COMPONENT_SELECT_ENTITY_UNSUPPORTED';
    }
}
