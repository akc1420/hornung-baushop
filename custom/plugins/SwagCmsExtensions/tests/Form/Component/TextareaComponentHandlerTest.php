<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Test\Form\Component;

use Shopware\Core\Framework\Test\TestCaseBase\SalesChannelFunctionalTestBehaviour;
use Swag\CmsExtensions\Form\Component\Handler\TextareaComponentHandler;

class TextareaComponentHandlerTest extends TextComponentHandlerTest
{
    use SalesChannelFunctionalTestBehaviour;

    protected function getType(): string
    {
        return 'textarea';
    }

    protected function getHandlerClass(): string
    {
        return TextareaComponentHandler::class;
    }
}
