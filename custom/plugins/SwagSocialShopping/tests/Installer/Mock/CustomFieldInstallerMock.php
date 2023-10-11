<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\SocialShopping\Test\Installer\Mock;

use Shopware\Core\System\CustomField\CustomFieldTypes;
use SwagSocialShopping\Installer\CustomFieldInstaller;

class CustomFieldInstallerMock extends CustomFieldInstaller
{
    public const NEW_LABEL = 'Google Product Category Test';
    public const SOCIAL_SHOPPING_CUSTOM_FIELD_GOOGLE_CATEGORY = [
        'name' => self::SOCIAL_SHOPPING_CUSTOM_FIELD_GOOGLE_CATEGORY_NAME,
        'type' => CustomFieldTypes::TEXT,
        'config' => [
            'label' => [
                'en-GB' => self::NEW_LABEL,
                'de-DE' => 'Google Produktkategorie',
            ],
        ],
    ];
}
