<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Test\Form\Field;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteException;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\WriteConstraintViolationException;
use Swag\CmsExtensions\Form\Aggregate\FormGroup\FormGroupDefinition;
use Swag\CmsExtensions\Form\Aggregate\FormGroupField\FormGroupFieldDefinition;
use Swag\CmsExtensions\Form\FormDefinition;
use Swag\CmsExtensions\Util\Lifecycle\FormDefaults;

class TechnicalNameValidatorTest extends TestCase
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
     * @var string|null
     */
    private $formId;

    protected function setUp(): void
    {
        $this->formId = null;

        $formRepository = $this->getContainer()->get(\sprintf('%s.repository', FormDefinition::ENTITY_NAME));
        $formGroupRepository = $this->getContainer()->get(\sprintf('%s.repository', FormGroupDefinition::ENTITY_NAME));
        $formGroupFieldRepository = $this->getContainer()->get(\sprintf('%s.repository', FormGroupFieldDefinition::ENTITY_NAME));

        static::assertInstanceOf(EntityRepositoryInterface::class, $formRepository);
        static::assertInstanceOf(EntityRepositoryInterface::class, $formGroupRepository);
        static::assertInstanceOf(EntityRepositoryInterface::class, $formGroupFieldRepository);

        $this->formRepository = $formRepository;
        $this->formGroupRepository = $formGroupRepository;
        $this->formGroupFieldRepository = $formGroupFieldRepository;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if ($this->formId === null) {
            return;
        }

        $this->formRepository->delete([['id' => $this->formId]], Context::createDefaultContext());
    }

    public function testInsertWholeForm(): void
    {
        $context = Context::createDefaultContext();

        $this->createForm(
            [
                self::DEFAULT_FIELD_VALUES,
                \array_merge(self::DEFAULT_FIELD_VALUES, ['technicalName' => 'otherName']),
            ],
            $context
        );

        static::assertNotNull($this->formId);
        static::assertNotNull($this->formRepository->search(new Criteria([$this->formId]), $context)->first());
    }

    public function testInsertWholeFormFail(): void
    {
        $context = Context::createDefaultContext();

        $exceptionThrown = false;

        try {
            $this->createForm(
                [
                    self::DEFAULT_FIELD_VALUES,
                    self::DEFAULT_FIELD_VALUES,
                ],
                $context
            );
        } catch (WriteException $exception) {
            $exceptions = $exception->getExceptions();
            static::assertCount(1, $exceptions);

            $firstException = \current($exceptions);
            static::assertInstanceOf(WriteConstraintViolationException::class, $firstException);
            static::assertCount(2, $firstException->getViolations());

            $exceptionThrown = true;
        }

        static::assertTrue($exceptionThrown);
        static::assertNotNull($this->formId);
        static::assertNull($this->formRepository->search(new Criteria([$this->formId]), $context)->first());
    }

    public function testInsertField(): void
    {
        $context = Context::createDefaultContext();

        $this->createForm([self::DEFAULT_FIELD_VALUES], $context);

        $this->formGroupFieldRepository->create([
            \array_merge(self::DEFAULT_FIELD_VALUES, [
                'technicalName' => 'otherName',
                'groupId' => $this->getGroupId($context),
            ]),
        ], $context);

        static::assertNotNull($this->formId);
        static::assertNotNull($this->formRepository->search(new Criteria([$this->formId]), $context)->first());
    }

    public function testInsertFieldFail(): void
    {
        $context = Context::createDefaultContext();

        $this->createForm([self::DEFAULT_FIELD_VALUES], $context);

        $exceptionThrown = false;

        try {
            $this->formGroupFieldRepository->create([
                \array_merge(self::DEFAULT_FIELD_VALUES, [
                    'groupId' => $this->getGroupId($context),
                ]),
            ], $context);
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

    public function testInsertGroup(): void
    {
        $context = Context::createDefaultContext();

        $this->createForm([self::DEFAULT_FIELD_VALUES], $context);

        $this->formGroupRepository->create([
            [
                'formId' => $this->formId,
                'position' => 0,
                'technicalName' => 'My new group',
                'title' => 'Group title',
                'fields' => [
                    \array_merge(self::DEFAULT_FIELD_VALUES, [
                        'technicalName' => 'otherName',
                    ]),
                ],
            ],
        ], $context);

        static::assertNotNull($this->getGroupId($context));
    }

    public function testInsertGroupFail(): void
    {
        $context = Context::createDefaultContext();

        $this->createForm([self::DEFAULT_FIELD_VALUES], $context);

        $exceptionThrown = false;

        try {
            $this->formGroupRepository->create([
                [
                    'formId' => $this->formId,
                    'position' => 0,
                    'technicalName' => 'My new group',
                    'title' => 'Group title',
                    'fields' => [
                        self::DEFAULT_FIELD_VALUES,
                    ],
                ],
            ], $context);
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

    public function testInsertFieldWithWhitespace(): void
    {
        $context = Context::createDefaultContext();

        $this->createForm([self::DEFAULT_FIELD_VALUES], $context);

        $exceptionThrown = false;

        try {
            $this->formGroupFieldRepository->create([
                \array_merge(self::DEFAULT_FIELD_VALUES, [
                    'technicalName' => "other Name\t",
                    'groupId' => $this->getGroupId($context),
                ]),
            ], $context);
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

    /**
     * @param array<int, array<string, mixed>> $fields
     */
    private function createForm(array $fields, Context $context): void
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
                        'fields' => $fields,
                    ],
                ],
            ],
        ], $context);
    }

    private function getGroupId(Context $context): string
    {
        static::assertNotNull($this->formId);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('formId', $this->formId));
        $criteria->setLimit(1);
        $groupId = $this->formGroupRepository->searchIds($criteria, $context)->firstId();
        static::assertNotNull($groupId);

        return $groupId;
    }
}
