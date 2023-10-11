<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Test\Form\Component;

use Shopware\Core\Framework\Test\TestCaseBase\SalesChannelFunctionalTestBehaviour;
use Swag\CmsExtensions\Form\Component\Handler\TextComponentHandler;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

class TextComponentHandlerTest extends AbstractComponentHandlerTest
{
    use SalesChannelFunctionalTestBehaviour;

    /**
     * @dataProvider dataProviderValidation
     */
    public function testGetValidation(bool $required): void
    {
        $definition = $this->handler->getValidationDefinition(
            $this->createField($this->getType(), $required),
            $this->createSalesChannelContext()
        );

        static::assertCount($required ? 2 : 1, $definition);
        if ($required) {
            static::assertInstanceOf(NotBlank::class, $definition[0]);
        }
        static::assertInstanceOf(Type::class, $definition[$required ? 1 : 0]);
        static::assertSame('string', $definition[$required ? 1 : 0]->type);
    }

    protected function getType(): string
    {
        return 'text';
    }

    protected function getHandlerClass(): string
    {
        return TextComponentHandler::class;
    }
}
