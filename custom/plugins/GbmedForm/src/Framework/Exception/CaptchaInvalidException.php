<?php declare(strict_types=1);

namespace Gbmed\Form\Framework\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class CaptchaInvalidException extends ShopwareHttpException
{
    public function getStatusCode(): int
    {
        return Response::HTTP_FORBIDDEN;
    }

    public function getErrorCode(): string
    {
        return 'FRAMEWORK__INVALID_CAPTCHA_VALUE';
    }
}
