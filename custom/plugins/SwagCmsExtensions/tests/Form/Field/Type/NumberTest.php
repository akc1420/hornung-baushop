<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Test\Form\Field\Type;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteException;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\WriteConstraintViolationException;
use Swag\CmsExtensions\Form\FormDefinition;
use Swag\CmsExtensions\Util\Lifecycle\FormDefaults;

class NumberTest extends TestCase
{
    use KernelTestBehaviour;

    /**
     * @var EntityRepositoryInterface
     */
    private $formRepository;

    /**
     * @var string|null
     */
    private $formId;

    protected function setUp(): void
    {
        $this->formId = null;

        $formRepository = $this->getContainer()->get(\sprintf('%s.repository', FormDefinition::ENTITY_NAME));
        static::assertInstanceOf(EntityRepositoryInterface::class, $formRepository);
        $this->formRepository = $formRepository;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if ($this->formId === null) {
            return;
        }

        $this->formRepository->delete([['id' => $this->formId]], Context::createDefaultContext());
    }

    public function testInsert(): void
    {
        $context = Context::createDefaultContext();

        $this->assertConstraintViolations(0, function () use ($context): void {
            $form = $this->createFormArray();

            $this->formRepository->create([$form], $context);
        }, $context);
    }

    public function testInsertWrongDiff(): void
    {
        $context = Context::createDefaultContext();

        $this->assertConstraintViolations(1, function () use ($context): void {
            $form = $this->createFormArray();
            $form['groups'][0]['fields'][0]['config']['min'] = 5;
            $form['groups'][0]['fields'][0]['config']['max'] = 1;

            $this->formRepository->create([$form], $context);
        }, $context);
    }

    public function testInsertWrongTypes(): void
    {
        $context = Context::createDefaultContext();

        $this->assertConstraintViolations(2, function () use ($context): void {
            $form = $this->createFormArray();
            $form['groups'][0]['fields'][0]['config']['min'] = true;
            $form['groups'][0]['fields'][0]['config']['max'] = '5';
            $form['groups'][0]['fields'][0]['config']['step'] = 'foo';

            $this->formRepository->create([$form], $context);
        }, $context);
    }

    private function assertConstraintViolations(int $violationCount, callable $callable, Context $context): void
    {
        $exceptionThrown = false;

        try {
            $callable();
        } catch (WriteException $exception) {
            $exceptions = $exception->getExceptions();
            static::assertCount(1, $exceptions);

            $firstException = \current($exceptions);
            static::assertInstanceOf(WriteConstraintViolationException::class, $firstException);
            static::assertCount($violationCount, $firstException->getViolations());

            $exceptionThrown = true;
        }
        static::assertNotNull($this->formId);

        if ($violationCount === 0) {
            static::assertNotNull($this->formRepository->searchIds(new Criteria([$this->formId]), $context)->firstId());

            return;
        }

        static::assertTrue($exceptionThrown);
        static::assertNull($this->formRepository->searchIds(new Criteria([$this->formId]), $context)->firstId());
    }

    /**
     * @return array<string, mixed>
     */
    private function createFormArray(): array
    {
        $this->formId = Uuid::randomHex();

        return [
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
                    'fields' => [
                        [
                            'position' => 0,
                            'width' => 12,
                            'type' => 'number',
                            'required' => false,
                            'technicalName' => 'numberField',
                            'label' => 'Zahl',
                            'config' => [
                                'min' => 0,
                                'max' => 10,
                                'step' => 2,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
