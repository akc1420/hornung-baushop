<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\SocialShopping\Test\Installer;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Migration\MigrationCollection;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetCollection;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetEntity;
use Shopware\Core\System\CustomField\CustomFieldTypes;
use Swag\SocialShopping\Test\Installer\Mock\CustomFieldInstallerMock;
use SwagSocialShopping\Installer\CustomFieldInstaller;
use SwagSocialShopping\SwagSocialShopping;

class CustomFieldInstallerTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    /**
     * @var EntityRepositoryInterface
     */
    private $customFieldSetRepo;

    /**
     * @var CustomFieldInstaller
     */
    private $installer;

    /**
     * @var Context
     */
    private $context;

    protected function setUp(): void
    {
        /** @var EntityRepositoryInterface $customFieldSetRepo */
        $customFieldSetRepo = $this->getContainer()->get('custom_field_set.repository');
        $this->customFieldSetRepo = $customFieldSetRepo;

        $this->context = Context::createDefaultContext();
    }

    public function testInstall(): void
    {
        $this->installer = new CustomFieldInstaller($this->customFieldSetRepo);
        $this->install();
    }

    public function testUpdate(): void
    {
        $this->installer = new CustomFieldInstaller($this->customFieldSetRepo);
        $this->install();

        $this->installer = new CustomFieldInstallerMock($this->customFieldSetRepo);
        $this->installer->update(new UpdateContext(
            new SwagSocialShopping(true, ''),
            $this->context,
            '',
            '',
            $this->createMock(MigrationCollection::class),
            ''
        ));

        $customFieldSets = $this->getCustomFields();
        static::assertCount(1, $customFieldSets);
        $customFieldSet = $customFieldSets->first();
        static::assertNotNull($customFieldSet);
        $customFieldConfig = $this->getCustomFieldConfig($customFieldSet);
        static::assertSame(CustomFieldInstallerMock::NEW_LABEL, $customFieldConfig['label']['en-GB']);
    }

    public function testUninstall(): void
    {
        $this->installer = new CustomFieldInstaller($this->customFieldSetRepo);
        $this->install();

        $this->installer->uninstall(new UninstallContext(
            new SwagSocialShopping(true, ''),
            $this->context,
            '',
            '',
            $this->createMock(MigrationCollection::class),
            false
        ));

        $customFieldSets = $this->getCustomFields();
        static::assertCount(0, $customFieldSets);
    }

    public function testUninstallCustomFieldsAlreadyDeleted(): void
    {
        $this->installer = new CustomFieldInstaller($this->customFieldSetRepo);

        $this->installer->uninstall(new UninstallContext(
            new SwagSocialShopping(true, ''),
            $this->context,
            '',
            '',
            $this->createMock(MigrationCollection::class),
            false
        ));

        $customFieldSets = $this->getCustomFields();
        static::assertCount(0, $customFieldSets);
    }

    public function testActivate(): void
    {
        $this->installer = new CustomFieldInstaller($this->customFieldSetRepo);
        $this->installer->activate(new ActivateContext(
            new SwagSocialShopping(true, ''),
            $this->context,
            '',
            '',
            $this->createMock(MigrationCollection::class)
        ));
    }

    public function testDeactivate(): void
    {
        $this->installer = new CustomFieldInstaller($this->customFieldSetRepo);
        $this->installer->deactivate(new DeactivateContext(
            new SwagSocialShopping(true, ''),
            $this->context,
            '',
            '',
            $this->createMock(MigrationCollection::class)
        ));
    }

    private function getCustomFields(): CustomFieldSetCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', CustomFieldInstaller::SOCIAL_SHOPPING_CUSTOM_FIELD_SET_NAME));
        $criteria->addAssociation('customFields');
        $criteria->addAssociation('relations');

        /** @var CustomFieldSetCollection $customFieldSets */
        $customFieldSets = $this->customFieldSetRepo->search($criteria, $this->context)->getEntities();

        return $customFieldSets;
    }

    private function install(): void
    {
        $this->installer->install(new InstallContext(
            new SwagSocialShopping(true, ''),
            $this->context,
            '',
            '',
            $this->createMock(MigrationCollection::class)
        ));

        $customFieldSets = $this->getCustomFields();
        static::assertCount(1, $customFieldSets);
        $customFieldSet = $customFieldSets->first();
        static::assertNotNull($customFieldSet);
        static::assertSame(CustomFieldInstaller::SOCIAL_SHOPPING_CUSTOM_FIELD_SET_NAME, $customFieldSet->getName());
        $customFieldSetConfig = $customFieldSet->getConfig();
        static::assertNotNull($customFieldSetConfig);
        static::assertSame('Social Shopping', $customFieldSetConfig['label']['en-GB']);

        $customFieldConfig = $this->getCustomFieldConfig($customFieldSet);
        static::assertSame('Google Product Category', $customFieldConfig['label']['en-GB']);
    }

    private function getCustomFieldConfig(CustomFieldSetEntity $customFieldSet): array
    {
        $customFields = $customFieldSet->getCustomFields();
        static::assertNotNull($customFields);
        static::assertCount(1, $customFields);
        $customField = $customFields->first();
        static::assertNotNull($customField);
        static::assertSame(CustomFieldInstaller::SOCIAL_SHOPPING_CUSTOM_FIELD_GOOGLE_CATEGORY_NAME, $customField->getName());
        static::assertSame(CustomFieldTypes::TEXT, $customField->getType());
        $customFieldConfig = $customField->getConfig();
        static::assertNotNull($customFieldConfig);

        return $customFieldConfig;
    }
}
