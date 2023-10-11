<?php

namespace Swag\Security\Fixes\NEXT17527;

use Shopware\Core\Framework\Routing\RequestTransformerInterface;
use Symfony\Component\HttpFoundation\Request;

class RequestTransformerFixer implements RequestTransformerInterface
{
    public const X_FORWARDED_PREFIX = 'x-forwarded-prefix';

    /**
     * @var RequestTransformerInterface
     */
    private $inner;

    public function __construct(RequestTransformerInterface $inner)
    {
        $this->inner = $inner;
    }

    public function transform(Request $request): Request
    {
        $req = $this->inner->transform($request);

        $trustedHeaderSet = Request::getTrustedHeaderSet();

        if ($req->headers->has(self::X_FORWARDED_PREFIX)) {
            if (($trustedHeaderSet & Request::HEADER_X_FORWARDED_PREFIX) === 0 || !$req->isFromTrustedProxy()) {
                $req->headers->remove(self::X_FORWARDED_PREFIX);

                return $req;
            }
        }

        return $req;
    }

    public function extractInheritableAttributes(Request $sourceRequest): array
    {
        return $this->inner->extractInheritableAttributes($sourceRequest);
    }
}
