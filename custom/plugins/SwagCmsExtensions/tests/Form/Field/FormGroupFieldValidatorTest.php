<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Test\Form\Field;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteException;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\WriteConstraintViolationException;
use Shopware\Core\System\Language\LanguageDefinition;
use Swag\CmsExtensions\Form\Aggregate\FormGroup\FormGroupDefinition;
use Swag\CmsExtensions\Form\Aggregate\FormGroupField\FormGroupFieldDefinition;
use Swag\CmsExtensions\Form\Aggregate\FormGroupField\FormGroupFieldEntity;
use Swag\CmsExtensions\Form\Aggregate\FormGroupFieldTranslation\FormGroupFieldTranslationDefinition;
use Swag\CmsExtensions\Form\FormDefinition;
use Swag\CmsExtensions\Util\Lifecycle\FormDefaults;

class FormGroupFieldValidatorTest extends TestCase
{
    use KernelTestBehaviour;

    private const DEFAULT_FIELD_VALUES = [
        'position' => 0,
        'width' => 12,
        'type' => 'textarea',
        'required' => false,
        'technicalName' => 'textareaField',
        'label' => 'Textfeld',
        'config' => [
            'rows' => 5,
        ],
    ];

    /**
     * @var EntityRepositoryInterface
     */
    private $formRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $formGroupRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $formGroupFieldRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $formGroupFieldTranslationRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $languageRepository;

    /**
     * @var string|null
     */
    private $formId;

    protected function setUp(): void
    {
        $this->formId = null;

        $formRepository = $this->getContainer()->get(\sprintf('%s.repository', FormDefinition::ENTITY_NAME));
        $formGroupRepository = $this->getContainer()->get(\sprintf('%s.repository', FormGroupDefinition::ENTITY_NAME));
        $formGroupFieldRepository = $this->getContainer()->get(\sprintf('%s.repository', FormGroupFieldDefinition::ENTITY_NAME));
        $formGroupFieldTranslationRepository = $this->getContainer()->get(\sprintf('%s.repository', FormGroupFieldTranslationDefinition::ENTITY_NAME));
        $languageRepository = $this->getContainer()->get(\sprintf('%s.repository', LanguageDefinition::ENTITY_NAME));

        static::assertInstanceOf(EntityRepositoryInterface::class, $formRepository);
        static::assertInstanceOf(EntityRepositoryInterface::class, $formGroupRepository);
        static::assertInstanceOf(EntityRepositoryInterface::class, $formGroupFieldRepository);
        static::assertInstanceOf(EntityRepositoryInterface::class, $formGroupFieldTranslationRepository);
        static::assertInstanceOf(EntityRepositoryInterface::class, $languageRepository);

        $this->formRepository = $formRepository;
        $this->formGroupRepository = $formGroupRepository;
        $this->formGroupFieldRepository = $formGroupFieldRepository;
        $this->formGroupFieldTranslationRepository = $formGroupFieldTranslationRepository;
        $this->languageRepository = $languageRepository;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if ($this->formId === null) {
            return;
        }

        $this->formRepository->delete([['id' => $this->formId]], Context::createDefaultContext());
    }

    public function testInsertWithTranslation(): void
    {
        $context = Context::createDefaultContext();
        $groupId = $this->createFormAndGetGroupId($context);

        static::assertNull($this->getFieldIdFromGroupId($groupId, $context));

        $this->formGroupFieldRepository->create([\array_merge(self::DEFAULT_FIELD_VALUES, [
            'groupId' => $groupId,
        ])], $context);

        static::assertNotNull($this->getFieldIdFromGroupId($groupId, $context));
    }

    public function testInsertWithTranslationFail(): void
    {
        $context = Context::createDefaultContext();
        $groupId = $this->createFormAndGetGroupId($context);

        static::assertNull($this->getFieldIdFromGroupId($groupId, $context));

        $exceptionThrown = false;

        try {
            $this->formGroupFieldRepository->create([\array_merge(self::DEFAULT_FIELD_VALUES, [
                'groupId' => $groupId,
                'type' => 'foo',
                'config' => [
                    'foo' => 'bar',
                ],
            ])], $context);
        } catch (WriteException $exception) {
            $exceptions = $exception->getExceptions();
            static::assertCount(2, $exceptions);

            $firstException = \current($exceptions);
            static::assertInstanceOf(WriteConstraintViolationException::class, $firstException);
            static::assertCount(1, $firstException->getViolations());

            $secondException = \next($exceptions);
            static::assertInstanceOf(WriteConstraintViolationException::class, $secondException);
            static::assertCount(1, $secondException->getViolations());

            $exceptionThrown = true;
        }

        static::assertTrue($exceptionThrown);
        static::assertNull($this->getFieldIdFromGroupId($groupId, $context));
    }

    public function testUpdateWithTranslation(): void
    {
        $context = Context::createDefaultContext();
        $groupId = $this->createFormAndGetGroupId($context);

        $this->formGroupFieldRepository->create([\array_merge(self::DEFAULT_FIELD_VALUES, [
            'groupId' => $groupId,
        ])], $context);

        $fieldId = $this->getFieldIdFromGroupId($groupId, $context);
        static::assertNotNull($fieldId);

        $this->formGroupFieldRepository->update([[
            'id' => $fieldId,
            'config' => [
                'rows' => 6,
            ],
        ]], $context);

        /** @var FormGroupFieldEntity $field */
        $field = $this->formGroupFieldRepository->search(new Criteria([$fieldId]), $context)->first();
        $config = $field->getConfig();
        static::assertNotNull($config);
        static::assertArrayHasKey('rows', $config);
        static::assertSame(6, $config['rows']);
    }

    public function testUpdateWithTranslationFail(): void
    {
        $context = Context::createDefaultContext();
        $groupId = $this->createFormAndGetGroupId($context);

        $this->formGroupFieldRepository->create([\array_merge(self::DEFAULT_FIELD_VALUES, [
            'groupId' => $groupId,
        ])], $context);

        $fieldId = $this->getFieldIdFromGroupId($groupId, $context);
        static::assertNotNull($fieldId);

        $exceptionThrown = false;

        try {
            $this->formGroupFieldRepository->update([[
                'id' => $fieldId,
                'config' => [
                    'foo' => 'bar',
                ],
            ]], $context);
        } catch (WriteException $exception) {
            $exceptions = $exception->getExceptions();
            static::assertCount(1, $exceptions);

            $firstException = \current($exceptions);
            static::assertInstanceOf(WriteConstraintViolationException::class, $firstException);
            static::assertCount(1, $firstException->getViolations());

            $exceptionThrown = true;
        }

        static::assertTrue($exceptionThrown);

        /** @var FormGroupFieldEntity $field */
        $field = $this->formGroupFieldRepository->search(new Criteria([$fieldId]), $context)->first();
        $config = $field->getConfig();
        static::assertNotNull($config);
        static::assertArrayHasKey('rows', $config);
        static::assertSame(5, $config['rows']);
    }

    public function testInsertOnlyTranslation(): void
    {
        $context = Context::createDefaultContext();
        $groupId = $this->createFormAndGetGroupId($context);

        $this->formGroupFieldRepository->create([\array_merge(self::DEFAULT_FIELD_VALUES, [
            'groupId' => $groupId,
        ])], $context);

        $fieldId = $this->getFieldIdFromGroupId($groupId, $context);
        static::assertNotNull($fieldId);

        $this->formGroupFieldTranslationRepository->create([[
            'swagCmsExtensionsFormGroupFieldId' => $fieldId,
            'languageId' => $this->getNonDefaultLanguageId($context),
            'placeholder' => 'foo',
            'label' => 'Label',
            'config' => [
                'rows' => 7,
            ],
        ]], $context);
    }

    public function testInsertOnlyTranslationFail(): void
    {
        $context = Context::createDefaultContext();
        $groupId = $this->createFormAndGetGroupId($context);

        $this->formGroupFieldRepository->create([\array_merge(self::DEFAULT_FIELD_VALUES, [
            'groupId' => $groupId,
        ])], $context);

        $fieldId = $this->getFieldIdFromGroupId($groupId, $context);
        static::assertNotNull($fieldId);

        $exceptionThrown = false;

        try {
            $this->formGroupFieldTranslationRepository->create([[
                'swagCmsExtensionsFormGroupFieldId' => $fieldId,
                'languageId' => $this->getNonDefaultLanguageId($context),
                'label' => 'Label',
                'config' => [
                    'foo' => 'bar',
                ],
            ]], $context);
        } catch (WriteException $exception) {
            $exceptions = $exception->getExceptions();
            static::assertCount(1, $exceptions);

            $firstException = \current($exceptions);
            static::assertInstanceOf(WriteConstraintViolationException::class, $firstException);
            static::assertCount(1, $firstException->getViolations());

            $exceptionThrown = true;
        }

        static::assertTrue($exceptionThrown);
    }

    public function testUpdateOnlyTranslation(): void
    {
        $context = Context::createDefaultContext();
        $groupId = $this->createFormAndGetGroupId($context);

        $this->formGroupFieldRepository->create([\array_merge(self::DEFAULT_FIELD_VALUES, [
            'groupId' => $groupId,
        ])], $context);

        $fieldId = $this->getFieldIdFromGroupId($groupId, $context);
        static::assertNotNull($fieldId);

        $this->formGroupFieldTranslationRepository->update([[
            'swagCmsExtensionsFormGroupFieldId' => $fieldId,
            'languageId' => $context->getLanguageId(),
            'placeholder' => 'foo',
            'label' => 'Label',
            'config' => [
                'rows' => 7,
            ],
        ]], $context);
    }

    public function testUpdateOnlyTranslationFail(): void
    {
        $context = Context::createDefaultContext();
        $groupId = $this->createFormAndGetGroupId($context);

        $this->formGroupFieldRepository->create([\array_merge(self::DEFAULT_FIELD_VALUES, [
            'groupId' => $groupId,
        ])], $context);

        $fieldId = $this->getFieldIdFromGroupId($groupId, $context);
        static::assertNotNull($fieldId);

        $exceptionThrown = false;

        try {
            $this->formGroupFieldTranslationRepository->update([[
                'swagCmsExtensionsFormGroupFieldId' => $fieldId,
                'languageId' => $context->getLanguageId(),
                'label' => 'Label',
                'config' => [
                    'foo' => 'bar',
                ],
            ]], $context);
        } catch (WriteException $exception) {
            $exceptions = $exception->getExceptions();
            static::assertCount(1, $exceptions);

            $firstException = \current($exceptions);
            static::assertInstanceOf(WriteConstraintViolationException::class, $firstException);
            static::assertCount(1, $firstException->getViolations());

            $exceptionThrown = true;
        }

        static::assertTrue($exceptionThrown);
    }

    private function createFormAndGetGroupId(Context $context): string
    {
        $this->formId = Uuid::randomHex();
        $this->formRepository->create([
            [
                'id' => $this->formId,
                'title' => 'Form title',
                'technicalName' => 'Technical form name',
                'mailTemplate' => [
                    'mailTemplateTypeId' => FormDefaults::FORM_MAIL_TEMPLATE_TYPE_ID,
                    'subject' => 'Subject',
                    'contentHtml' => '<p>Hello World!</p>',
                    'contentPlain' => 'Hello World!',
                ],
                'groups' => [
                    [
                        'position' => 0,
                        'technicalName' => 'My group',
                        'title' => 'Group title',
                    ],
                ],
            ],
        ], $context);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('formId', $this->formId));
        $criteria->setLimit(1);
        $groupId = $this->formGroupRepository->searchIds($criteria, $context)->firstId();
        static::assertNotNull($groupId);

        return $groupId;
    }

    private function getFieldIdFromGroupId(string $groupId, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('groupId', $groupId));
        $criteria->setLimit(1);

        return $this->formGroupFieldRepository->searchIds($criteria, $context)->firstId();
    }

    private function getNonDefaultLanguageId(Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_OR, [
            new EqualsFilter('id', Defaults::LANGUAGE_SYSTEM),
            new EqualsFilter('id', $context->getLanguageId()),
        ]));
        $criteria->setLimit(1);

        return $this->languageRepository->searchIds($criteria, $context)->firstId();
    }
}
