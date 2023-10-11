<?php


namespace Crsw\CleverReachOfficial\Subscriber\Products;

use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\Initializer;
use Shopware\Core\Content\Product\ProductEvents;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Class ProductSubscriber
 *
 * @package Crsw\CleverReachOfficial\Subscriber\Products
 */
class ProductSubscriber implements EventSubscriberInterface
{
    /**
     * @var RequestStack
     */
    private $requestStack;
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * ProductSubscriber constructor.
     *
     * @param RequestStack $requestStack
     * @param SessionInterface $session
     * @param Initializer $initializer
     */
    public function __construct(SessionInterface $session, RequestStack $requestStack, Initializer $initializer)
    {
        Bootstrap::init();
        $initializer->registerServices();
        $this->requestStack = $requestStack;
        $this->session = $session;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ProductEvents::PRODUCT_LOADED_EVENT => 'onProductLoaded'
        ];
    }

    /**
     * @param EntityLoadedEvent $event
     */
    public function onProductLoaded(EntityLoadedEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request) {
            $crMailing = $request->get('crmailing');

            if (!empty($crMailing)) {
                $this->session->set('crMailing', $crMailing);
            }
        }
    }
}