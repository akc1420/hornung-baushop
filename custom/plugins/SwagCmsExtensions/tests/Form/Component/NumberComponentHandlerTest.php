<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Test\Form\Component;

use Shopware\Core\Framework\Test\TestCaseBase\SalesChannelFunctionalTestBehaviour;
use Swag\CmsExtensions\Form\Component\Handler\NumberComponentHandler;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Type;

class NumberComponentHandlerTest extends AbstractComponentHandlerTest
{
    use SalesChannelFunctionalTestBehaviour;

    /**
     * @return array<int, array{0: bool, 1: int|null, 2: int|null}>
     */
    public function dataProviderValidation(): array
    {
        return [
            [true, 1, 5],
            [true, null, 5],
            [true, 1, null],
            [true, null, null],
            [false, 1, 5],
            [false, null, 5],
            [false, 1, null],
            [false, null, null],
        ];
    }

    /**
     * @dataProvider dataProviderValidation
     */
    public function testGetValidation(bool $required, ?int $min, ?int $max): void
    {
        $definition = $this->handler->getValidationDefinition(
            $this->createField($this->getType(), $required, ['min' => $min, 'max' => $max]),
            $this->createSalesChannelContext()
        );

        static::assertCount(($required ? 2 : 1) + ($min === null && $max === null ? 0 : 1), $definition);
        if ($required) {
            static::assertInstanceOf(NotBlank::class, $definition[0]);
        }
        static::assertInstanceOf(Type::class, $definition[$required ? 1 : 0]);
        static::assertSame('numeric', $definition[$required ? 1 : 0]->type);

        if ($min === null && $max === null) {
            return;
        }

        $mainDefinition = $definition[$required ? 2 : 1];
        if ($min !== null && $max !== null) {
            static::assertInstanceOf(Range::class, $mainDefinition);
            static::assertSame($min, $mainDefinition->min);
            static::assertSame($max, $mainDefinition->max);
        } elseif ($min !== null) {
            static::assertInstanceOf(GreaterThanOrEqual::class, $mainDefinition);
            static::assertSame($min, $mainDefinition->value);
        } elseif ($max !== null) {
            static::assertInstanceOf(LessThanOrEqual::class, $mainDefinition);
            static::assertSame($max, $mainDefinition->value);
        }
    }

    protected function getType(): string
    {
        return 'number';
    }

    protected function getHandlerClass(): string
    {
        return NumberComponentHandler::class;
    }
}
