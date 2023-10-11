<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Test\Form\Component;

use PHPUnit\Framework\TestCase;
use Swag\CmsExtensions\Form\Component\ComponentRegistry;
use Swag\CmsExtensions\Form\Component\Exception\ComponentHandlerAlreadyRegisteredException;
use Swag\CmsExtensions\Form\Component\Exception\ComponentHandlerNotRegisteredException;
use Swag\CmsExtensions\Form\Component\Handler\EmailComponentHandler;
use Swag\CmsExtensions\Form\Component\Handler\TextComponentHandler;

class ComponentRegistryTest extends TestCase
{
    public function testRegisterAndGetHandler(): void
    {
        $handler = new TextComponentHandler();
        $handler2 = new EmailComponentHandler();
        $registry = new ComponentRegistry([$handler, $handler2]);

        static::assertSame($handler, $registry->getHandler($handler->getComponentType()));
        static::assertSame($handler2, $registry->getHandler($handler2->getComponentType()));
    }

    public function testRegisterDuplicate(): void
    {
        $handler = new TextComponentHandler();
        $handler2 = new TextComponentHandler();

        $this->expectException(ComponentHandlerAlreadyRegisteredException::class);
        new ComponentRegistry([$handler, $handler2]);
    }

    public function testGetHandlerNotExists(): void
    {
        $registry = new ComponentRegistry([]);

        $this->expectException(ComponentHandlerNotRegisteredException::class);
        $registry->getHandler('foo');
    }
}
