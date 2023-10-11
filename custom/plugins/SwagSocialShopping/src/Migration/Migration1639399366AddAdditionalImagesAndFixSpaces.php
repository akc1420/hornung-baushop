<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagSocialShopping\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

class Migration1639399366AddAdditionalImagesAndFixSpaces extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1639399366;
    }

    public function update(Connection $connection): void
    {
        $old_template_facebook = self::getTemplate('old_facebook');
        $oldest_template_facebook = self::getTemplate('oldest_facebook');
        $old_template_google = self::getTemplate('old_google');

        $template_google = self::getTemplate('new_google');
        $template_facebook = self::getTemplate('new_facebook');

        $exports = $this->getSocialExports($connection);

        foreach ($exports as $export) {
            $current_body = $export['body'];

            switch ($current_body) {
                case $old_template_facebook:
                case $oldest_template_facebook:
                    $this->performUpdate($export['id'], $connection, $template_facebook);

                    break;
                case $old_template_google:
                    $this->performUpdate($export['id'], $connection, $template_google);

                    break;
            }
        }
    }

    public static function getTemplate(string $templateName): string
    {
        switch ($templateName) {
            case 'oldest_facebook':
                return (string) \file_get_contents(__DIR__ . '/fixtures/Migration1639399366AddAdditionalImagesAndFixSpaces/body_oldest_facebook.xml');
            case 'old_facebook':
                return (string) \file_get_contents(__DIR__ . '/fixtures/Migration1639399366AddAdditionalImagesAndFixSpaces/body_old_facebook.xml');
            case 'old_google':
                return (string) \file_get_contents(__DIR__ . '/fixtures/Migration1639399366AddAdditionalImagesAndFixSpaces/body_old_google.xml');
            case 'new_facebook':
                return (string) \file_get_contents(__DIR__ . '/../Resources/templates/facebook/body.xml');
            case 'new_google':
                return (string) \file_get_contents(__DIR__ . '/../Resources/templates/google-shopping/body.xml');
        }

        return 'false';
    }

    public function updateDestructive(Connection $connection): void
    {
    }

    private function getSocialExports(Connection $connection): array
    {
        $getSocialExportsSQL = '
        SELECT HEX(pe.id) AS id, pe.body_template AS body
        FROM
            product_export pe
                INNER JOIN swag_social_shopping_sales_channel se
        WHERE
            pe.product_stream_id = se.product_stream_id
        AND pe.sales_channel_id = se.sales_channel_id
        AND pe.sales_channel_domain_id = se.sales_channel_domain_id ';

        return $connection->fetchAllAssociative($getSocialExportsSQL);
    }

    private function performUpdate(string $id, Connection $connection, string $template): void
    {
        $executed = $connection->update('product_export', ['body_template' => $template], ['id' => Uuid::fromHexToBytes($id)]);
        if (!$executed) {
            \trigger_error('Migration failed for ID: ' . $id, \E_USER_WARNING);
        }
    }
}
