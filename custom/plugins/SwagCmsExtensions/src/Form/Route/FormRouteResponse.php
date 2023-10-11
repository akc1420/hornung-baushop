<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Route;

use Shopware\Core\System\SalesChannel\StoreApiResponse;

class FormRouteResponse extends StoreApiResponse
{
    /**
     * internal php type has to match the parent type
     *
     * @var FormRouteResponseStruct
     */
    protected $object;

    public function __construct(FormRouteResponseStruct $object)
    {
        parent::__construct($object);
    }

    public function getResult(): FormRouteResponseStruct
    {
        return $this->object;
    }
}
