<?php /** @noinspection PhpUndefinedClassInspection */

namespace Crsw\CleverReachOfficial\Subscriber\Extensions;

use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\Initializer;
use Crsw\CleverReachOfficial\Core\Infrastructure\Exceptions\BaseException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Automation\AutomationService;
use Crsw\CleverReachOfficial\Struct\AutomationData;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class RegisterExtension
 *
 * @package Crsw\CleverReachOfficial\Subscriber\Extensions
 */
class RegisterExtension implements EventSubscriberInterface
{
    /**
     * @var AutomationService
     */
    private $automationService;

    /**
     * RegisterExtension constructor.
     *
     * @param Initializer $initializer
     * @param AutomationService $automationService
     */
    public function __construct(Initializer $initializer, AutomationService $automationService)
    {
        Bootstrap::register();
        $initializer->registerServices();
        $this->automationService = $automationService;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutRegisterPageLoadedEvent::class => 'onCheckoutRegisterPageLoaded'
        ];
    }

    /**
     * Extends checkout register page.
     *
     * @param CheckoutRegisterPageLoadedEvent $event
     */
    public function onCheckoutRegisterPageLoaded(CheckoutRegisterPageLoadedEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannel()->getId();

        try {
            $automation = $this->automationService->get($salesChannelId);

            if ($automation && $automation->isActive()) {
                $event->getPage()
                    ->addExtension('cleverreach', new AutomationData(['showCheckbox' => true]));
                return;
            }
        } catch (BaseException $e) {
            Logger::logError('Failed to get automation data because: ' . $e->getMessage(), 'Integration');
        }

        $event->getPage()->addExtension('cleverreach', new AutomationData(['showCheckbox' => false]));
    }
}
