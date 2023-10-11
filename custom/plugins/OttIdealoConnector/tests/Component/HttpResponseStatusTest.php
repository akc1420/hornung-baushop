<?php


namespace Ott\IdealoConnector\Test\Component;


use Ott\IdealoConnector\Component\HttpResponseStatus;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;

class HttpResponseStatusTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testStaticCodes()
    {
        $this->assertEquals(200, HttpResponseStatus::STATUS_OK);
        $this->assertEquals(409, HttpResponseStatus::STATUS_CONFLICT);
        $this->assertEquals(400, HttpResponseStatus::STATUS_BAD_REQUEST);
        $this->assertEquals(500, HttpResponseStatus::STATUS_INTERNAL_SERVER_ERROR);
        $this->assertEquals(404, HttpResponseStatus::STATUS_NOT_FOUND);
        $this->assertEquals(401, HttpResponseStatus::STATUS_UNAUTHORIZED);
    }
}
