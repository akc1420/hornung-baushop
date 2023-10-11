<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagSocialShopping\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1630669686FixProductExportWithMissingImages extends MigrationStep
{
    public const OLD_TEMPLATE = "
    <item>
    <g:id>{{ product.productNumber }}</g:id>
    <title>{{ product.translated.name|escape }}</title>
    <description>{{ product.translated.description|escape }}</description>
    {%- if product.categories.first.translated.customFields.swag_social_shopping_google_category is defined and product.categories.first.translated.customFields.swag_social_shopping_google_category is not empty -%}
        <g:google_product_category>
            {{- product.categories.first.translated.customFields.swag_social_shopping_google_category -}}
        </g:google_product_category>
    {%- elseif socialShoppingSalesChannel.configuration.defaultGoogleProductCategory is defined and socialShoppingSalesChannel.configuration.defaultGoogleProductCategory is not empty -%}
        <g:google_product_category>
            {{- socialShoppingSalesChannel.configuration.defaultGoogleProductCategory -}}
        </g:google_product_category>
    {%- endif -%}
    {%- if product.categories and product.categories.count > 0 -%}
        <g:product_type>{{ product.categories.first.getBreadCrumb|slice(1)|join(' > ')|raw|escape }}</g:product_type>
    {%- endif -%}
    <link>{{ seoUrl('frontend.detail.page', {'productId': product.id}) }}</link>
    <g:image_link>{{ product.cover.media.url }}</g:image_link>
    <g:condition>new</g:condition>
    <g:availability>
        {%- if product.availableStock >= product.minPurchase and product.deliveryTime -%}
            in_stock{#- -#}
        {%- elseif product.availableStock < product.minPurchase and product.deliveryTime and product.restockTime -%}
            preorder{#- -#}
        {%- else -%}
            out_of_stock{#- -#}
        {%- endif -%}
    </g:availability>
    {%- if product.calculatedPrice.listPrice -%}
        <g:sale_price>
            {%- if product.calculatedPrices is not empty -%}
                {{ product.calculatedPrices.first.unitPrice|number_format(2, '.', '') }}
            {%- else -%}
                {{ product.calculatedPrice.unitPrice|number_format(2, '.', '') }}
            {%- endif -%}
            {{ context.currency.isoCode }}
        </g:sale_price>
        <g:price>
            {{ product.calculatedPrice.listPrice.price|number_format(2, '.', '') }}
            {{ context.currency.isoCode }}
        </g:price>
    {%- else -%}
        <g:price>
            {%- if product.calculatedPrices is not empty -%}
                {{ product.calculatedPrices.first.unitPrice|number_format(2, '.', '') }}
            {%- else -%}
                {{ product.calculatedPrice.unitPrice|number_format(2, '.', '') }}
            {%- endif -%}
            {{ context.currency.isoCode }}
        </g:price>
    {%- endif -%}
    {%- if product.manufacturer -%}
        <g:brand>{{ product.manufacturer.translated.name|escape }}</g:brand>
    {%- endif -%}
    <g:gtin>{{ product.ean }}</g:gtin>
    <g:mpn>{{ product.manufacturerNumber }}</g:mpn>
</item>
    ";

    public const NEW_TEMPLATE = "
    <item>
    <g:id>{{ product.productNumber }}</g:id>
    <title>{{ product.translated.name|escape }}</title>
    <description>{{ product.translated.description|escape }}</description>
    {%- if product.categories.first.translated.customFields.swag_social_shopping_google_category is defined and product.categories.first.translated.customFields.swag_social_shopping_google_category is not empty -%}
        <g:google_product_category>
            {{- product.categories.first.translated.customFields.swag_social_shopping_google_category -}}
        </g:google_product_category>
    {%- elseif socialShoppingSalesChannel.configuration.defaultGoogleProductCategory is defined and socialShoppingSalesChannel.configuration.defaultGoogleProductCategory is not empty -%}
        <g:google_product_category>
            {{- socialShoppingSalesChannel.configuration.defaultGoogleProductCategory -}}
        </g:google_product_category>
    {%- endif -%}
    {%- if product.categories and product.categories.count > 0 -%}
        <g:product_type>{{ product.categories.first.getBreadCrumb|slice(1)|join(' > ')|raw|escape }}</g:product_type>
    {%- endif -%}
    <link>{{ seoUrl('frontend.detail.page', {'productId': product.id}) }}</link>
    <g:image_link>{{ product.cover.media.url }}</g:image_link>
    {%- if product.cover -%}
        <g:image_link>{{ product.cover.media.url }}</g:image_link>
    {%- endif -%}
    <g:condition>new</g:condition>
    <g:availability>
        {%- if product.availableStock >= product.minPurchase and product.deliveryTime -%}
            in_stock{#- -#}
        {%- elseif product.availableStock < product.minPurchase and product.deliveryTime and product.restockTime -%}
            preorder{#- -#}
        {%- else -%}
            out_of_stock{#- -#}
        {%- endif -%}
    </g:availability>
    {%- if product.calculatedPrice.listPrice -%}
        <g:sale_price>
            {%- if product.calculatedPrices is not empty -%}
                {{ product.calculatedPrices.first.unitPrice|number_format(2, '.', '') }}
            {%- else -%}
                {{ product.calculatedPrice.unitPrice|number_format(2, '.', '') }}
            {%- endif -%}
            {{ context.currency.isoCode }}
        </g:sale_price>
        <g:price>
            {{ product.calculatedPrice.listPrice.price|number_format(2, '.', '') }}
            {{ context.currency.isoCode }}
        </g:price>
    {%- else -%}
        <g:price>
            {%- if product.calculatedPrices is not empty -%}
                {{ product.calculatedPrices.first.unitPrice|number_format(2, '.', '') }}
            {%- else -%}
                {{ product.calculatedPrice.unitPrice|number_format(2, '.', '') }}
            {%- endif -%}
            {{ context.currency.isoCode }}
        </g:price>
    {%- endif -%}
    {%- if product.manufacturer -%}
        <g:brand>{{ product.manufacturer.translated.name|escape }}</g:brand>
    {%- endif -%}
    <g:gtin>{{ product.ean }}</g:gtin>
    <g:mpn>{{ product.manufacturerNumber }}</g:mpn>
</item>
    ";

    public function getCreationTimestamp(): int
    {
        return 1630669686;
    }

    public function update(Connection $connection): void
    {
        $getSocialExportsSQL = '
        SELECT HEX(pe.id) AS id, pe.body_template AS body
        FROM
            product_export pe INNER JOIN swag_social_shopping_sales_channel se
        WHERE
            pe.product_stream_id = se.product_stream_id
        AND pe.sales_channel_id = se.sales_channel_id
        AND pe.sales_channel_domain_id = se.sales_channel_domain_id ';

        $updateSQL = '
        UPDATE product_export
        SET body_template = ?
        WHERE id = UNHEX(?)';

        /**
         * @var ResultStatement
         */
        $exports = $connection->fetchAllAssociative($getSocialExportsSQL);

        $old_template = \preg_replace('/\s/', '', self::OLD_TEMPLATE);
        foreach ($exports as $export) {
            $old_body = \preg_replace('/\s/', '', $export['body']);
            //ignore user modified exports
            if ($old_body !== $old_template) {
                continue;
            }
            $executed = $connection->executeStatement($updateSQL, [self::NEW_TEMPLATE, $export['id']]);
            if (!$executed) {
                \trigger_error('Migration failed for ID: ' . $export['id'], \E_USER_WARNING);
            }
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
