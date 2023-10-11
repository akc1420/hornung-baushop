<?php declare(strict_types=1);

namespace Swag\Security\Subscriber;

use Shopware\Storefront\Event\StorefrontRenderEvent;
use Swag\Security\Components\State;

class TwigStateProvider
{
    /**
     * @var State
     */
    private $state;

    public function __construct(State $state)
    {
        $this->state = $state;
    }

    public function __invoke(StorefrontRenderEvent $event): void
    {
        $event->setParameter('swagSecurity', $this->state);
    }
}
