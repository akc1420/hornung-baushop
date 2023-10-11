<?php declare(strict_types=1);

namespace Ott\IdealoConnector\Service;

use Ott\IdealoConnector\Idealo\Client;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;

class ClientService
{
    private const TOKEN_FILE = __DIR__ . '/../Resources/v2%s';
    private Client $client;
    private ConfigProvider $configProvider;
    private ?array $accessTokens = [];
    private ?array $accessTokenExpires = [];
    private ?array $shopIds = [];
    private LoggerInterface $logger;

    public function __construct(Client $client, ConfigProvider $configProvider, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->configProvider = $configProvider;
        $this->logger = $logger;
    }

    public function getAccessToken(?array $testConfig = null): bool
    {
        $clientId = null === $testConfig
            ? $this->configProvider->getClientId()
            : $testConfig['clientId'];
        $tokenFile = sprintf(self::TOKEN_FILE, $clientId);

        if (file_exists($tokenFile)) {
            $tokenData = explode('#', file_get_contents($tokenFile));
        } else {
            $tokenData = [];
        }

        if (null === $testConfig && !empty($tokenData) && time() <= $tokenData[1]) {
            $this->accessTokens[$clientId] = $tokenData[0];
            $this->accessTokenExpires[$clientId] = (int) $tokenData[1];
            $this->shopIds[$clientId] = (int) $tokenData[2];
        } else {
            $auth = null === $testConfig
                ? base64_encode($this->configProvider->getClientId() . ':' . $this->configProvider->getClientSecret())
                : base64_encode($testConfig['clientId'] . ':' . $testConfig['clientSecret']);
            $response = $this->decodeResponse(
                $this->client->post($this->getRequestUrlScheme(Client::API_ENDPOINT_TOKEN, $testConfig['isSandbox'] ?? null), '', [
                    'Authorization: Basic ' . $auth,
                ])
            );

            if ($response && isset($response['access_token'])) {
                $this->accessTokens[$clientId] = $response['access_token'];
                $this->accessTokenExpires[$clientId] = time() + $response['expires_in'];
                $this->shopIds[$clientId] = (int) $response['shop_id'];

                file_put_contents($tokenFile, sprintf(
                    '%s#%s#%s',
                    $this->accessTokens[$clientId],
                    $this->accessTokenExpires[$clientId],
                    $this->shopIds[$clientId]
                ));
            } else {
                $this->logger->critical('Authentication failed!');

                return false;
            }
        }

        return true;
    }

    public function invalidateAccessToken(): void
    {
        $tokenFile = sprintf(self::TOKEN_FILE, $this->configProvider->getClientId());

        if (file_exists($tokenFile)) {
            unlink($tokenFile);
        }

        unset(
            $this->accessTokens[$this->configProvider->getClientId()],
            $this->accessTokenExpires[$this->configProvider->getClientId()],
            $this->shopIds[$this->configProvider->getClientId()]
        );
    }

    public function getClientAccessToken(): ?string
    {
        return $this->accessTokens[$this->configProvider->getClientId()] ?? null;
    }

    public function getClientShopId(): ?int
    {
        return $this->shopIds[$this->configProvider->getClientId()] ?? null;
    }

    public function getOrders(bool $isProcessing = false, int $page = 0, int $pageSize = 1000): array
    {
        $this->invalidateAccessToken();
        if (!$this->checkAccessToken()) {
            return [];
        }

        try {
            if ($isProcessing) {
                $response = $this->decodeResponse(
                    $this->client->get(sprintf(
                        $this->getRequestUrlScheme(Client::API_ENDPOINT_ORDERS_FILTERED),
                        $this->getClientShopId(),
                        $page,
                        $pageSize
                    ), ['Authorization: Bearer ' . $this->getClientAccessToken()])
                );
            } else {
                $response = $this->decodeResponse(
                    $this->client->get(sprintf(
                        $this->getRequestUrlScheme(Client::API_ENDPOINT_ORDERS_NEW),
                        $this->getClientShopId()
                    ), ['Authorization: Bearer ' . $this->getClientAccessToken()])
                );
            }

            return $response;
        } catch (\Exception $e) {
            $this->logger->critical(sprintf('[%s] %s', $e->getCode(), $e->getMessage()));

            return [];
        }
    }

    public function getRevokedOrders(int $page = 0, int $pageSize = 1000): array
    {
        $this->invalidateAccessToken();
        if (!$this->checkAccessToken()) {
            return [];
        }

        $date = new \DateTime();
        $interval = new \DateInterval('P1D');
        $date->sub($interval);

        try {
            $response = $this->decodeResponse(
                $this->client->get(sprintf(
                    $this->getRequestUrlScheme(Client::API_ENDPOINT_ORDERS_REVOKED),
                    $this->getClientShopId(),
                    $date->format('Y-m-d\TH:i:s\Z'),
                    date('Y-m-d\TH:i:s\Z'),
                    $page,
                    $pageSize
                ), ['Authorization: Bearer ' . $this->getClientAccessToken()])
            );

            if (isset($response['content'])) {
                return $response['content'];
            }
        } catch (\Exception $e) {
            $this->logger->critical(sprintf('[%s] %s', $e->getCode(), $e->getMessage()));
        }

        return [];
    }

    public function sendOrderNumber(string $idealoOrderId, string $orderNumber): bool
    {
        if (!$this->checkAccessToken()) {
            return false;
        }

        try {
            $this->client->post(
                sprintf(
                    $this->getRequestUrlScheme(Client::API_ENDPOINT_ORDER_NUMBER),
                    $this->getClientShopId(),
                    $idealoOrderId
                ),
                json_encode([
                    'merchantOrderNumber' => $orderNumber,
                ]),
                [
                    'Authorization: Bearer ' . $this->getClientAccessToken(),
                    'Content-Type: application/json;charset=UTF-8',
                ]
            );
        } catch (\Exception $e) {
            $this->logger->critical(sprintf('[%s] %s', $e->getCode(), $e->getMessage()));
        }

        return true;
    }

    public function cancelOrder(string $idealoOrderId, OrderLineItemCollection $items): bool
    {
        if (!$this->checkAccessToken()) {
            return false;
        }

        foreach ($items as $item) {
            try {
                $this->client->post(
                    sprintf(
                        $this->getRequestUrlScheme(Client::API_ENDPOINT_ORDER_CANCEL),
                        $this->getClientShopId(),
                        $idealoOrderId
                    ),
                    json_encode([
                        'sku'     => $item->getProduct()->getProductNumber(),
                        'reason'  => 'CUSTOMER_REVOKE',
                        'comment' => '',
                    ]),
                    [
                        'Authorization: Bearer ' . $this->getClientAccessToken(),
                        'Content-Type: application/json;charset=UTF-8',
                    ]
                );
            } catch (\Exception $e) {
                $this->logger->critical(sprintf('[%s][%s] %s', $idealoOrderId, $e->getCode(), $e->getMessage()));
            }
        }

        return true;
    }

    public function refundOrder(string $idealoOrderId, float $refundAmount): bool
    {
        if (!$this->checkAccessToken()) {
            return false;
        }

        try {
            $response = $this->client->post(
                sprintf(
                    $this->getRequestUrlScheme(Client::API_ENDPOINT_ORDER_REFUND),
                    $this->getClientShopId(),
                    $idealoOrderId
                ),
                json_encode([
                    'refundAmount' => $refundAmount,
                    'currency'     => 'EUR',
                ]),
                [
                    'Authorization: Bearer ' . $this->getClientAccessToken(),
                    'Content-Type: application/json;charset=UTF-8',
                ]
            );

            if (202 === $response['status']) {
                return true;
            }
        } catch (\Exception $e) {
            $this->logger->critical(sprintf('[%s] %s', $e->getCode(), $e->getMessage()));
        }

        return false;
    }

    public function fulfillOrder(string $idealoOrderId, string $carrier, string $trackingCode): bool
    {
        if (!$this->checkAccessToken()) {
            return false;
        }

        try {
            $response = $this->client->post(
                sprintf(
                    $this->getRequestUrlScheme(Client::API_ENDPOINT_ORDER_FULFILLMENT),
                    $this->getClientShopId(),
                    $idealoOrderId
                ),
                json_encode([
                    'carrier'      => $carrier && !empty($trackingCode) ? $carrier : null,
                    'trackingCode' => $carrier && !empty($trackingCode) ? [$trackingCode] : null,
                ]),
                [
                    'Authorization: Bearer ' . $this->getClientAccessToken(),
                    'Content-Type: application/json;charset=UTF-8',
                ]
            );

            if (201 === $response['status']) {
                return true;
            }
        } catch (\Exception $e) {
            $this->logger->critical(sprintf('[%s] %s', $e->getCode(), $e->getMessage()));
        }

        return false;
    }

    private function checkAccessToken(): bool
    {
        if (
            null === $this->getClientAccessToken()
            || time() > ($this->accessTokenExpires[$this->configProvider->getClientId()] ?? 0)
        ) {
            $this->getAccessToken();

            if (null === $this->getClientAccessToken()) {
                return false;
            }
        }

        return true;
    }

    private function decodeResponse(array $response): array
    {
        $response = json_decode($response['body'], true);

        if (isset($response['error']) && !empty($response['error'])) {
            $this->logger->critical(sprintf('[%s] %s', 500, $response['error_description']));
        }

        if (!\is_array($response)) {
            return [];
        }

        return $response;
    }

    private function getRequestUrlScheme(string $endpoint, ?bool $forceSandbox = null): string
    {
        return null === $forceSandbox && (bool) $this->configProvider->isSandboxMode() || true === $forceSandbox
            ? Client::API_URL_SANDBOX . $endpoint
            : Client::API_URL_LIVE . $endpoint;
    }
}
