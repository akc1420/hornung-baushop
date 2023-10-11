<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\SocialShopping\Test\EventListener;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\ProductStream\ProductStreamDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\DeleteCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Validation\PreWriteValidationEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\SocialShopping\Test\Helper\ServicesTrait;
use SwagSocialShopping\EventListener\ProductStreamEventListener;

class ProductStreamEventListenerTest extends TestCase
{
    use ServicesTrait;

    /**
     * @var ProductStreamEventListener
     */
    private $productSteamEventListener;

    protected function setUp(): void
    {
        /** @var ProductStreamEventListener $eventListener */
        $eventListener = $this->getContainer()->get(ProductStreamEventListener::class);
        $this->productSteamEventListener = $eventListener;
    }

    public function testGetSubscribedEvents(): void
    {
        static::assertSame(
            [
                PreWriteValidationEvent::class => 'preValidate',
            ],
            ProductStreamEventListener::getSubscribedEvents()
        );
    }

    public function testPrevalidateWithoutCommandsDoesNotAddError(): void
    {
        $event = new PreWriteValidationEvent(
            WriteContext::createFromContext(Context::createDefaultContext()),
            []
        );
        $this->productSteamEventListener->preValidate($event);
        static::assertCount(0, $event->getExceptions()->getErrors());
    }

    public function testPrevalidateWithProductStreamNotUsedBySocialShoppingDoesntAddError(): void
    {
        $event = new PreWriteValidationEvent(
            WriteContext::createFromContext(Context::createDefaultContext()),
            [
                new DeleteCommand(
                    new ProductStreamDefinition(),
                    [
                        'id' => Uuid::randomBytes(),
                    ],
                    new EntityExistence(
                        ProductStreamDefinition::ENTITY_NAME,
                        [],
                        true,
                        false,
                        false,
                        []
                    )
                ),
            ]
        );
        $this->productSteamEventListener->preValidate($event);
        static::assertCount(0, $event->getExceptions()->getErrors());
    }

    public function testPrevalidateWithProductStreamUsedBySocialShoppingDoesAddError(): void
    {
        $productStreamId = Uuid::randomHex();
        $this->createSocialShoppingSalesChannel(
            Uuid::randomHex(),
            [
                'productStream' => [
                    'id' => $productStreamId,
                    'name' => 'example-product-stream',
                ],
            ]
        );

        $event = new PreWriteValidationEvent(
            WriteContext::createFromContext(Context::createDefaultContext()),
            [
                new DeleteCommand(
                    new ProductStreamDefinition(),
                    [
                        'id' => Uuid::fromHexToBytes($productStreamId),
                    ],
                    new EntityExistence(
                        ProductStreamDefinition::ENTITY_NAME,
                        [],
                        true,
                        false,
                        false,
                        []
                    )
                ),
            ]
        );
        $this->productSteamEventListener->preValidate($event);
        static::assertCount(1, $event->getExceptions()->getErrors());
    }
}
