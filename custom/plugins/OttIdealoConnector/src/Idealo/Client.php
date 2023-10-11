<?php declare(strict_types=1);

namespace Ott\IdealoConnector\Idealo;

use Composer\InstalledVersions;
use Ott\IdealoConnector\Dbal\DataProvider;
use PackageVersions\Versions;

class Client
{
    private const GET = 'GET';
    private const PUT = 'PUT';
    private const POST = 'POST';
    public const API_URL_LIVE = 'https://orders.idealo.com';
    public const API_URL_SANDBOX = 'https://orders-sandbox.idealo.com';
    public const API_ENDPOINT_TOKEN = '/api/v2/oauth/token';
    public const API_ENDPOINT_ORDERS_FILTERED = '/api/v2/shops/%s/orders?status=PROCESSING&acknowledged=false&pageNumber=%s&pageSize=%s';
    public const API_ENDPOINT_ORDERS_NEW = '/api/v2/shops/%s/new-orders';
    public const API_ENDPOINT_ORDERS_REVOKED = '/api/v2/shops/%s/orders?status=REVOKING,REVOKED&from=%s&to=%s&pageNumber=%s&pageSize=%s';
    public const API_ENDPOINT_ORDER_FULFILLMENT = '/api/v2/shops/%s/orders/%s/fulfillment';
    public const API_ENDPOINT_ORDER_CANCEL = '/api/v2/shops/%s/orders/%s/revocations';
    public const API_ENDPOINT_ORDER_REFUND = '/api/v2/shops/%s/orders/%s/refunds';
    public const API_ENDPOINT_ORDER_NUMBER = '/api/v2/shops/%s/orders/%s/merchant-order-number';
    private DataProvider $dataProvider;

    public function __construct(DataProvider $dataProvider)
    {
        $this->dataProvider = $dataProvider;
    }

    public function get(string $url, array $additionalHeaders = []): array
    {
        return $this->_exec(self::GET, $url, null, $additionalHeaders);
    }

    public function put(string $url, ?string $data = null, array $additionalHeaders = []): array
    {
        return $this->_exec(self::PUT, $url, $data, $additionalHeaders);
    }

    public function post(string $url, ?string $data = null, array $additionalHeaders = []): array
    {
        return $this->_exec(self::POST, $url, $data, $additionalHeaders);
    }

    protected function _exec(string $type, string $url, ?string $data, array $additionalHeaders): array
    {
        $ch = curl_init();

        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, \CURLOPT_HEADER, true);
        curl_setopt($ch, \CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, \CURLOPT_URL, $url);

        $header = $this->getHeader($type, $data, $additionalHeaders);

        curl_setopt($ch, \CURLOPT_HTTPHEADER, $header);

        switch ($type) {
            case self::GET:
                curl_setopt($ch, \CURLOPT_CUSTOMREQUEST, self::GET);
                break;
            case self::POST:
                curl_setopt($ch, \CURLOPT_POST, true);
                curl_setopt($ch, \CURLOPT_CUSTOMREQUEST, self::POST);
                curl_setopt($ch, \CURLOPT_POSTFIELDS, $data);
                break;
            case self::PUT:
                curl_setopt($ch, \CURLOPT_POST, true);
                curl_setopt($ch, \CURLOPT_CUSTOMREQUEST, self::PUT);
                curl_setopt($ch, \CURLOPT_POSTFIELDS, $data);
                break;
        }

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, \CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, \CURLINFO_HEADER_SIZE);
        $content_type = curl_getinfo($ch, \CURLINFO_CONTENT_TYPE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        curl_close($ch);

        return [
            'status'       => $status,
            'header_size'  => $header_size,
            'content_type' => $content_type,
            'header'       => $header,
            'body'         => $body,
        ];
    }

    private function getHeader(string $type, ?string $data, array $additionalHeaders): array
    {
        $shopwareVersion = class_exists(InstalledVersions::class)
            ? InstalledVersions::getVersion('shopware/core')
            : Versions::getVersion('shopware/core');
        $shopwareVersion = explode('@', $shopwareVersion ?? '')[0];
        $interfaceVersion = $this->dataProvider->getPluginVersion();

        $header = [
            'ERP-Shop-System: Shopware',
            'ERP-Shop-System-Version: ' . $shopwareVersion,
            'Integration-Partner: Ottscho IT',
            'Interface-Version: ' . $interfaceVersion,
        ];
        $postHeader = [
            'Content-Type: application/json',
            'Content-Length: ' . (null === $data ? 0 : \strlen($data)),
        ];

        if (\in_array($type, [self::POST, self::PUT])) {
            $header = array_merge($header, $postHeader);
        }

        return array_merge($header, $additionalHeaders);
    }
}
