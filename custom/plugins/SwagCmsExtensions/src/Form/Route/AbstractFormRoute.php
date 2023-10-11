<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Route;

use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * This route can be used to send a form mail for the authenticated sales channel.
 */
abstract class AbstractFormRoute
{
    abstract public function getDecorated(): AbstractFormRoute;

    abstract public function send(RequestDataBag $formData, SalesChannelContext $context): FormRouteResponse;
}
