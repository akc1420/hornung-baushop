<?php


namespace Ott\IdealoConnector\Test\Component;

use Ott\IdealoConnector\Component\IdealoClientException;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;

class IdealoClientExceptionTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testGetErrorMessage()
    {
        $this->assertEquals('[100] test123', IdealoClientException::getErrorMessage('test123', 100));
        $this->assertEquals('test123<br>(NOT FOUND): Bestellung nicht gefunden', IdealoClientException::getErrorMessage('test123', 404));
        $this->assertEquals('test123<br>(INTERNAL SERVER ERROR): Inhaltlich falsch, Details in Response-Body', IdealoClientException::getErrorMessage('test123', 500));
        $this->assertEquals('test123<br>(UNAUTHORIZED): Authentifizierungs-Token ungültig', IdealoClientException::getErrorMessage('test123', 401));
        $this->assertEquals('test123<br>(CONFLICT): Bestellung ist in einem unzulässigen Status', IdealoClientException::getErrorMessage('test123', 409));
        $this->assertEquals('test123<br>(BAD REQUEST): Formal falsch, z. B. Anfrage kein JSON', IdealoClientException::getErrorMessage('test123', 400));
    }
}
