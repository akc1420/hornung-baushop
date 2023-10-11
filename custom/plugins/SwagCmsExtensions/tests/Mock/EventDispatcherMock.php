<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Test\Mock;

use Shopware\Core\Framework\Event\GenericEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EventDispatcherMock implements EventDispatcherInterface
{
    /**
     * @var array<object>
     */
    private $sentEvents = [];

    /**
     * @return array<object>
     */
    public function getSentEvents(): array
    {
        return $this->sentEvents;
    }

    /**
     * @template TEvent of object
     *
     * @param TEvent $event
     *
     * @return TEvent
     */
    public function dispatch($event, ?string $eventName = null): object
    {
        if ($eventName === null && $event instanceof GenericEvent) {
            $eventName = $event->getName();
        }

        $this->sentEvents[$eventName] = $event;

        return $event;
    }
}
