<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\SocialShopping\Test\Helper;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\TestDefaults;

trait MigrationTemplateTestHelper
{
    public function createProductExport(array $data): array
    {
        $connection = $this->getConnection();
        $id = Uuid::randomHex();

        $values = [
            'id' => $id,
            'is_socialExport' => false,
            'file_name' => 'ExportTest',
            'accessKey' => Uuid::randomHex(),
            'encoding' => 'UTF-8',
            'file_format' => 'xml',
            'interval' => 0,
            'currencyId' => Defaults::CURRENCY,
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
            'product_stream_id' => $this->createProductStream(Uuid::randomHex()),
            'sales_channel_domain_id' => $this->getSalesChannelDomainId(TestDefaults::SALES_CHANNEL),
            'body_template' => 'Example Body',
            'created_at' => new \DateTime(),
        ];

        $values = \array_merge($values, $data);

        $connection->insert(
            'product_export',
            [
                'id' => Uuid::fromHexToBytes($values['id']),
                'product_stream_id' => Uuid::fromHexToBytes($values['product_stream_id']),
                'sales_channel_id' => Uuid::fromHexToBytes($values['salesChannelId']),
                'sales_channel_domain_id' => Uuid::fromHexToBytes($values['sales_channel_domain_id']),
                'body_template' => $values['body_template'],
                'file_name' => $values['file_name'],
                'access_key' => $values['accessKey'],
                'encoding' => $values['encoding'],
                'file_format' => $values['file_format'],
                '`interval`' => $values['interval'],
                'created_at' => $values['created_at'],
                'currency_id' => Uuid::fromHexToBytes($values['currencyId']),
            ],
            [
                'id' => 'binary',
                'product_stream_id' => 'binary',
                'sales_channel_id' => 'binary',
                'sales_channel_domain_id' => 'binary',
                'body_template' => 'string',
                'file_name' => 'string',
                'access_key' => 'string',
                'encoding' => 'string',
                'file_format' => 'string',
                'interval' => 'integer',
                'created_at' => 'datetime',
                'currency_id' => 'binary',
            ]
        );

        return $values;
    }

    public function createSocialSalesChannel(?array $data = null): string
    {
        $connection = $this->getConnection();
        $network = 'SwagSocialShopping\Component\Network\Instagram';

        if (empty($data['id'])) {
            $data['id'] = Uuid::randomHex();
        }

        $salesChannelId = TestDefaults::SALES_CHANNEL;

        $connection->insert(
            'swag_social_shopping_sales_channel',
            [
                'id' => Uuid::fromHexToBytes($data['id']),
                'sales_channel_id' => Uuid::fromHexToBytes($data['salesChannelId'] ?? $salesChannelId),
                'product_stream_id' => Uuid::fromHexToBytes($data['product_stream_id'] ?? $this->createProductStream(Uuid::randomHex())),
                'currency_id' => Uuid::fromHexToBytes($data['currency_id'] ?? Defaults::CURRENCY),
                'sales_channel_domain_id' => Uuid::fromHexToBytes($data['sales_channel_domain_id'] ?? $this->getSalesChannelDomainId(TestDefaults::SALES_CHANNEL)),
                'network' => $data['network'] ?? $network,
                'created_at' => $data['created_at'] ?? $this->getTime(),
            ]
        );

        return $data['id'];
    }

    public function createProductStream(string $id): string
    {
        $connection = $this->getConnection();

        $connection->insert(
            'product_stream',
            [
                'id' => Uuid::fromHexToBytes($id),
                'invalid' => 0,
                'created_at' => $this->getTime(),
            ]
        );

        return $id;
    }

    public function getSalesChannelDomainId(string $salesChannelId): string
    {
        $connection = $this->getConnection();

        $getSalesChannelDomainIdsSQL = '
        SELECT HEX(id) AS id
        FROM sales_channel_domain
        WHERE HEX(sales_channel_id) = :id';

        return $connection->fetchOne($getSalesChannelDomainIdsSQL, ['id' => $salesChannelId]);
    }

    public function getExportBody(string $productExportId): string
    {
        $connection = $this->getConnection();
        $getExportBodySQL = '
            SELECT body_template AS body FROM product_export WHERE HEX(id) = :id
        ';

        return $connection->fetchOne($getExportBodySQL, ['id' => $productExportId]);
    }

    public function getTime(): string
    {
        return (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT);
    }

    private function getConnection(): Connection
    {
        /**
         * @var Connection $connection
         */
        $connection = $this->getContainer()->get(Connection::class);

        return $connection;
    }
}
