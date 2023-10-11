<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Component;

use Swag\CmsExtensions\Form\Component\Exception\ComponentHandlerAlreadyRegisteredException;
use Swag\CmsExtensions\Form\Component\Exception\ComponentHandlerNotRegisteredException;

class ComponentRegistry
{
    /**
     * @var AbstractComponentHandler[]
     */
    private array $registeredHandlers;

    /**
     * @param iterable<AbstractComponentHandler> $handlers
     */
    public function __construct(iterable $handlers)
    {
        foreach ($handlers as $handler) {
            $this->registerHandler($handler);
        }
    }

    public function getHandler(string $componentType): AbstractComponentHandler
    {
        if (!isset($this->registeredHandlers[$componentType])) {
            throw new ComponentHandlerNotRegisteredException($componentType);
        }

        return $this->registeredHandlers[$componentType];
    }

    /**
     * @throws ComponentHandlerAlreadyRegisteredException
     */
    private function registerHandler(AbstractComponentHandler $handler): void
    {
        $componentType = $handler->getComponentType();
        if (isset($this->registeredHandlers[$componentType])) {
            throw new ComponentHandlerAlreadyRegisteredException($componentType);
        }

        $this->registeredHandlers[$componentType] = $handler;
    }
}
