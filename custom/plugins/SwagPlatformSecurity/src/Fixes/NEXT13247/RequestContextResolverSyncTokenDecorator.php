<?php


namespace Swag\Security\Fixes\NEXT13247;


use Shopware\Core\Framework\Routing\RequestContextResolverInterface;
use Shopware\Core\PlatformRequest;
use Swag\Security\Components\State;
use Symfony\Component\HttpFoundation\Request;

class RequestContextResolverSyncTokenDecorator implements RequestContextResolverInterface
{
    /**
     * @var RequestContextResolverInterface
     */
    private $inner;

    /**
     * @var State
     */
    private $state;

    public function __construct(State $state, RequestContextResolverInterface $inner)
    {
        $this->inner = $inner;
        $this->state = $state;
    }

    public function resolve(Request $request): void
    {
        $this->inner->resolve($request);

        if (!$this->state->isActive('NEXT-13247')) {
            return;
        }

        $context = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);
        if ($context !== null) {
            $request->headers->set(PlatformRequest::HEADER_CONTEXT_TOKEN, $context->getToken());
        }
    }
}
