<?php

namespace Swag\Security\Fixes\NEXT14883;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class PermissionDeniedException extends ShopwareHttpException
{
    public function __construct()
    {
        parent::__construct('The user does not have the permission to do this action.');
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__PERMISSION_DENIED';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_FORBIDDEN;
    }
}
