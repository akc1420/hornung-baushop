<?php declare(strict_types=1);

namespace Ott\IdealoConnector\Test\Event;

use Symfony\Component\EventDispatcher\EventDispatcher;

trait EventTestBehaviour
{
    public function catchEvent($eventName, $func)
    {
        $this->getContainer()->get('event_dispatcher')->addListener($eventName, $func);
    }

    public function fireEvent($eventName, ...$args)
    {
        return $this->getContainer()->get('event_dispatcher')->dispatch(new $eventName(...$args));
    }

    public function clearEvent($eventName)
    {
        /** @var EventDispatcher $eventDispatcher */
        $eventDispatcher = $this->getContainer()->get('event_dispatcher');
        $listeners = $eventDispatcher->getListeners($eventName);

        foreach ($listeners as $listener) {
            $eventDispatcher->removeListener($eventName, $listener);
        }
    }
}
