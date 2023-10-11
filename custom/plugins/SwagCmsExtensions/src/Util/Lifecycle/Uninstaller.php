<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Util\Lifecycle;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Swag\CmsExtensions\BlockRule\BlockRuleDefinition;
use Swag\CmsExtensions\Form\Aggregate\FormGroup\FormGroupDefinition;
use Swag\CmsExtensions\Form\Aggregate\FormGroupField\FormGroupFieldDefinition;
use Swag\CmsExtensions\Form\Aggregate\FormGroupFieldTranslation\FormGroupFieldTranslationDefinition;
use Swag\CmsExtensions\Form\Aggregate\FormGroupTranslation\FormGroupTranslationDefinition;
use Swag\CmsExtensions\Form\Aggregate\FormTranslation\FormTranslationDefinition;
use Swag\CmsExtensions\Form\FormDefinition;
use Swag\CmsExtensions\Quickview\QuickviewDefinition;
use Swag\CmsExtensions\ScrollNavigation\Aggregate\ScrollNavigationPageSettings\ScrollNavigationPageSettingsDefinition;
use Swag\CmsExtensions\ScrollNavigation\Aggregate\ScrollNavigationTranslation\ScrollNavigationTranslationDefinition;
use Swag\CmsExtensions\ScrollNavigation\ScrollNavigationDefinition;

class Uninstaller
{
    private UninstallContext $context;

    private Connection $connection;

    public function __construct(UninstallContext $context, Connection $connection)
    {
        $this->context = $context;
        $this->connection = $connection;
    }

    public function uninstall(): void
    {
        if ($this->context->keepUserData()) {
            return;
        }

        $this->dropCmsExtensionTables();
    }

    private function dropCmsExtensionTables(): void
    {
        $classNames = [
            QuickviewDefinition::ENTITY_NAME,
            ScrollNavigationPageSettingsDefinition::ENTITY_NAME,
            ScrollNavigationTranslationDefinition::ENTITY_NAME,
            ScrollNavigationDefinition::ENTITY_NAME,
            BlockRuleDefinition::ENTITY_NAME,
            FormGroupFieldTranslationDefinition::ENTITY_NAME,
            FormGroupFieldDefinition::ENTITY_NAME,
            FormGroupTranslationDefinition::ENTITY_NAME,
            FormGroupDefinition::ENTITY_NAME,
            FormTranslationDefinition::ENTITY_NAME,
            FormDefinition::ENTITY_NAME,
        ];

        foreach ($classNames as $className) {
            $this->connection->executeStatement(\sprintf('DROP TABLE IF EXISTS `%s`', $className));
        }
    }
}
