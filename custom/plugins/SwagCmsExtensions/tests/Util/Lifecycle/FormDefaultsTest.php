<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Test\Util\Lifecycle;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\MailTemplate\Aggregate\MailTemplateType\MailTemplateTypeDefinition;
use Shopware\Core\Content\MailTemplate\MailTemplateDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Migration\MigrationCollectionLoader;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Swag\CmsExtensions\SwagCmsExtensions;
use Swag\CmsExtensions\Util\Lifecycle\FormDefaults;

class FormDefaultsTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @var FormDefaults
     */
    private $formDefaults;

    /**
     * @var EntityRepository
     */
    private $mailTemplateRepository;

    /**
     * @var EntityRepository
     */
    private $mailTemplateTypeRepository;

    /**
     * @var Criteria
     */
    private $criteria;

    /**
     * @var Context
     */
    private $context;

    protected function setUp(): void
    {
        $mailTemplateRepository = $this->getContainer()->get(\sprintf('%s.repository', MailTemplateDefinition::ENTITY_NAME));
        $mailTemplateTypeRepository = $this->getContainer()->get(\sprintf('%s.repository', MailTemplateTypeDefinition::ENTITY_NAME));

        static::assertInstanceOf(EntityRepository::class, $mailTemplateRepository);
        static::assertInstanceOf(EntityRepository::class, $mailTemplateTypeRepository);

        $this->mailTemplateRepository = $mailTemplateRepository;
        $this->mailTemplateTypeRepository = $mailTemplateTypeRepository;
        $this->formDefaults = new FormDefaults($this->mailTemplateRepository, $this->mailTemplateTypeRepository);
        $this->context = Context::createDefaultContext();

        $this->criteria = new Criteria();
        $this->criteria->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_EXACT);
    }

    public function testLifecycle(): void
    {
        static::assertNotNull($this->mailTemplateTypeRepository->searchIds(new Criteria([FormDefaults::FORM_MAIL_TEMPLATE_TYPE_ID]), $this->context)->firstId());
        $templates = $this->mailTemplateRepository->searchIds($this->criteria, $this->context)->getTotal();

        $this->formDefaults->deactivate($this->context);

        $newTemplates = $this->mailTemplateRepository->searchIds($this->criteria, $this->context)->getTotal();
        static::assertSame(-1, $newTemplates - $templates);
        $templates = $newTemplates;
        static::assertNull($this->mailTemplateTypeRepository->searchIds(new Criteria([FormDefaults::FORM_MAIL_TEMPLATE_TYPE_ID]), $this->context)->firstId());

        $this->formDefaults->activate($this->context);

        $newTemplates = $this->mailTemplateRepository->searchIds($this->criteria, $this->context)->getTotal();
        static::assertSame(1, $newTemplates - $templates);
        static::assertNotNull($this->mailTemplateTypeRepository->searchIds(new Criteria([FormDefaults::FORM_MAIL_TEMPLATE_TYPE_ID]), $this->context)->firstId());
    }

    public function testActivateAlreadyExists(): void
    {
        $templates = $this->mailTemplateRepository->searchIds($this->criteria, $this->context)->getTotal();

        static::assertGreaterThan(0, $templates);
        static::assertNotNull($this->mailTemplateTypeRepository->searchIds(new Criteria([FormDefaults::FORM_MAIL_TEMPLATE_TYPE_ID]), $this->context)->firstId());

        $this->formDefaults->activate($this->context);

        $newTemplates = $this->mailTemplateRepository->searchIds($this->criteria, $this->context)->getTotal();

        static::assertSame(0, $newTemplates - $templates);
        static::assertNotNull($this->mailTemplateTypeRepository->searchIds(new Criteria([FormDefaults::FORM_MAIL_TEMPLATE_TYPE_ID]), $this->context)->firstId());
    }

    public function testUpdate(): void
    {
        $this->formDefaults->deactivate($this->context);
        $templates = $this->mailTemplateRepository->searchIds($this->criteria, $this->context)->getTotal();
        static::assertNull($this->mailTemplateTypeRepository->searchIds(new Criteria([FormDefaults::FORM_MAIL_TEMPLATE_TYPE_ID]), $this->context)->firstId());

        $this->formDefaults->update($this->createUpdateContext('1.7.0', '1.8.0'));

        $newTemplates = $this->mailTemplateRepository->searchIds($this->criteria, $this->context)->getTotal();

        static::assertSame(1, $newTemplates - $templates);
        static::assertNotNull($this->mailTemplateTypeRepository->searchIds(new Criteria([FormDefaults::FORM_MAIL_TEMPLATE_TYPE_ID]), $this->context)->firstId());
    }

    private function createUpdateContext(string $currentPluginVersion, string $nextPluginVersion): UpdateContext
    {
        /** @var MigrationCollectionLoader $migrationLoader */
        $migrationLoader = $this->getContainer()->get(MigrationCollectionLoader::class);

        return new UpdateContext(
            new SwagCmsExtensions(true, ''),
            Context::createDefaultContext(),
            '',
            $currentPluginVersion,
            $migrationLoader->collect('core'),
            $nextPluginVersion
        );
    }
}
