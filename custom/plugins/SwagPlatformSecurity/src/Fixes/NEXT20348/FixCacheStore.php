<?php

namespace Swag\Security\Fixes\NEXT20348;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\StoreInterface;

class FixCacheStore implements StoreInterface
{
    /**
     * @var StoreInterface
     */
    protected $decorated;

    public function __construct(StoreInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    public function lookup(Request $request)
    {
        return $this->decorated->lookup($request);
    }

    public function write(Request $request, Response $response)
    {
        $newResponse = clone $response;
        $newResponse->headers = clone $response->headers;

        foreach ($newResponse->headers->getCookies() as $cookie) {
            if ($cookie->getName() === 'session-') {
                $newResponse->headers->removeCookie($cookie->getName(), $cookie->getPath(), $cookie->getDomain());
            }
        }

        return $this->decorated->write($request, $newResponse);
    }

    public function invalidate(Request $request)
    {
        return $this->decorated->invalidate($request);
    }

    public function lock(Request $request)
    {
        return $this->decorated->lock($request);
    }

    public function unlock(Request $request)
    {
        return $this->decorated->unlock($request);
    }

    public function isLocked(Request $request)
    {
        return $this->decorated->isLocked($request);
    }

    public function purge(string $url)
    {
        return $this->decorated->purge($url);
    }

    public function cleanup()
    {
        return $this->decorated->cleanup();
    }
}
