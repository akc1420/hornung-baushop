<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Test\Util\Lifecycle;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Swag\CmsExtensions\Util\Lifecycle\Uninstaller;

class UninstallerTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testUninstallAbortsWhenKeepUserDataIsTrue(): void
    {
        $context = $this->getContextMock();
        $connection = $this->getConnectionMock();

        $context->expects(static::once())
            ->method('keepUserData')
            ->willReturn(true);

        $connection->expects(static::never())
            ->method('executeStatement');

        (new Uninstaller($context, $connection))->uninstall();
    }

    public function testUninstallExecutesWhenKeepUserDataIsFalse(): void
    {
        $context = $this->getContextMock();
        $connection = $this->getConnectionMock();

        $context->expects(static::once())
            ->method('keepUserData')
            ->willReturn(false);

        $connection->expects(static::atMost(12))
            ->method('executeStatement');

        (new Uninstaller($context, $connection))->uninstall();
    }

    /**
     * @return UninstallContext|MockObject
     */
    private function getContextMock(): UninstallContext
    {
        return $this->getMockBuilder(UninstallContext::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['keepUserData'])
            ->getMock();
    }

    /**
     * @return Connection|MockObject
     */
    private function getConnectionMock(): Connection
    {
        return $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['executeStatement'])
            ->getMock();
    }
}
