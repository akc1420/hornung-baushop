<?php declare(strict_types=1);

namespace Ott\IdealoConnector\Component;

class IdealoClientException extends \Exception
{
    public const ERROR_PATTERN_NO_API_KEY = ' no API KEY (salesChannel %s) ';
    private static array $errorMessageMapping = [
        HttpResponseStatus::STATUS_BAD_REQUEST           => '%s<br>(BAD REQUEST): Formal falsch, z. B. Anfrage kein JSON',
        HttpResponseStatus::STATUS_UNAUTHORIZED          => '%s<br>(UNAUTHORIZED): Authentifizierungs-Token ungültig',
        HttpResponseStatus::STATUS_NOT_FOUND             => '%s<br>(NOT FOUND): Bestellung nicht gefunden',
        HttpResponseStatus::STATUS_CONFLICT              => '%s<br>(CONFLICT): Bestellung ist in einem unzulässigen Status',
        HttpResponseStatus::STATUS_INTERNAL_SERVER_ERROR => '%s<br>(INTERNAL SERVER ERROR): Inhaltlich falsch, Details in Response-Body',
    ];

    public static function getErrorMessage(string $message, int $errorCode): string
    {
        if (isset(self::$errorMessageMapping[$errorCode])) {
            return sprintf(self::$errorMessageMapping[$errorCode], $message);
        }

        return sprintf('[%s] %s', $errorCode, $message);
    }
}
