<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\SocialShopping\Test\Api;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Swag\SocialShopping\Test\Helper\ServicesTrait;
use SwagSocialShopping\Api\SocialShoppingController;
use SwagSocialShopping\Component\MessageQueue\SocialShoppingValidation;
use SwagSocialShopping\Component\Network\Facebook;
use SwagSocialShopping\Component\Network\GoogleShopping;
use SwagSocialShopping\Component\Network\Instagram;
use SwagSocialShopping\Component\Network\Pinterest;
use SwagSocialShopping\DataAbstractionLayer\Entity\SocialShoppingSalesChannelDefinition;
use SwagSocialShopping\DataAbstractionLayer\Entity\SocialShoppingSalesChannelEntity;
use SwagSocialShopping\Exception\SocialShoppingSalesChannelNotFoundException;
use Symfony\Component\Messenger\TraceableMessageBus;

class SocialShoppingControllerTest extends TestCase
{
    use ServicesTrait;

    /**
     * @var SocialShoppingController
     */
    private $socialShoppingController;

    protected function setUp(): void
    {
        /** @var SocialShoppingController $controller */
        $controller = $this->getContainer()->get(SocialShoppingController::class);
        $this->socialShoppingController = $controller;
    }

    public function testGetNetworks(): void
    {
        $responseContent = $this->socialShoppingController->getNetworks()->getContent();
        static::assertIsString($responseContent);
        $networks = \json_decode($responseContent, true);
        static::assertCount(4, $networks);
        static::assertArrayHasKey((new Facebook())->getName(), $networks);
        static::assertSame($networks[(new Facebook())->getName()], Facebook::class);
        static::assertArrayHasKey((new Pinterest())->getName(), $networks);
        static::assertSame($networks[(new Pinterest())->getName()], Pinterest::class);
        static::assertArrayHasKey((new Instagram())->getName(), $networks);
        static::assertSame($networks[(new Instagram())->getName()], Instagram::class);
        static::assertArrayHasKey((new GoogleShopping())->getName(), $networks);
        static::assertSame($networks[(new GoogleShopping())->getName()], GoogleShopping::class);
    }

    public function testValidateWithoutSocialShoppingSalesChannelIdThrowsException(): void
    {
        $this->expectException(MissingRequestParameterException::class);
        $this->socialShoppingController->validate(
            new RequestDataBag(),
            Context::createDefaultContext()
        );
    }

    public function testValidateWithNoneExistingSocialShoppingSalesChannelIdThrowsException(): void
    {
        $this->expectException(SocialShoppingSalesChannelNotFoundException::class);
        $this->socialShoppingController->validate(
            new RequestDataBag([
                'social_shopping_sales_channel_id' => Uuid::randomHex(),
            ]),
            Context::createDefaultContext()
        );
    }

    public function testValidateSetsValidateFlagToEntity(): void
    {
        /** @var TraceableMessageBus $shopwareMessageBus */
        $shopwareMessageBus = $this->getContainer()->get('messenger.bus.shopware');
        $shopwareMessageBus->reset();

        $id = Uuid::randomHex();
        $this->createSocialShoppingSalesChannel($id, ['isValidating' => false]);
        $this->socialShoppingController->validate(
            new RequestDataBag([
                'social_shopping_sales_channel_id' => $id,
            ]),
            Context::createDefaultContext()
        );

        /** @var EntityRepositoryInterface $socialShoppingSalesChannelRepository */
        $socialShoppingSalesChannelRepository = $this->getContainer()->get(\sprintf('%s.repository', SocialShoppingSalesChannelDefinition::ENTITY_NAME));

        /** @var SocialShoppingSalesChannelEntity|null $socialShoppingSalesChannel */
        $socialShoppingSalesChannel = $socialShoppingSalesChannelRepository->search(new Criteria([$id]), Context::createDefaultContext())->get($id);
        static::assertNotNull($socialShoppingSalesChannel);
        static::assertTrue($socialShoppingSalesChannel->getIsValidating());

        $dispatchedMessages = $shopwareMessageBus->getDispatchedMessages();
        static::assertCount(1, $dispatchedMessages);
        static::assertIsArray($dispatchedMessages[0]);
        static::assertArrayHasKey('message', $dispatchedMessages[0]);
        static::assertInstanceOf(SocialShoppingValidation::class, $dispatchedMessages[0]['message']);
    }
}
