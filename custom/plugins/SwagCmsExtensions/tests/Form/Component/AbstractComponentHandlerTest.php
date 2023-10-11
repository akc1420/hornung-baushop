<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Test\Form\Component;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\SalesChannelFunctionalTestBehaviour;
use Swag\CmsExtensions\Form\Aggregate\FormGroupField\FormGroupFieldEntity;
use Swag\CmsExtensions\Form\Component\AbstractComponentHandler;

abstract class AbstractComponentHandlerTest extends TestCase
{
    use SalesChannelFunctionalTestBehaviour;

    /**
     * @var AbstractComponentHandler
     */
    protected $handler;

    public function setUp(): void
    {
        parent::setUp();
        /** @var AbstractComponentHandler $handler */
        $handler = $this->getContainer()->get($this->getHandlerClass());
        $this->handler = $handler;
    }

    public function testGetComponentType(): void
    {
        static::assertSame($this->getType(), $this->handler->getComponentType());
    }

    /**
     * @return array<array<int, bool>>
     */
    public function dataProviderValidation(): array
    {
        return [
            [true],
            [false],
        ];
    }

    abstract protected function getType(): string;

    /**
     * @return class-string<AbstractComponentHandler>
     */
    abstract protected function getHandlerClass(): string;

    /**
     * @param array<string, mixed>|null $config
     */
    protected function createField(string $type, bool $required, ?array $config = null): FormGroupFieldEntity
    {
        $field = new FormGroupFieldEntity();
        $field->assign([
            'position' => 0,
            'width' => 6,
            'type' => $type,
            'required' => $required,
            'technicalName' => 'fieldName',
            'label' => 'Name',
            'config' => $config,
            'translated' => [
                'label' => 'Name',
                'config' => $config,
            ],
        ]);

        return $field;
    }
}
