<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Test\Form\Validation;

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

class TechnicalNameValidatorTest extends TestCase
{
    use KernelTestBehaviour;

    /**
     * @var EntityRepositoryInterface
     */
    private $formRepository;

    /**
     * @var string[]
     */
    private $formIds;

    protected function setUp(): void
    {
        $this->formIds = [];

        $formRepository = $this->getContainer()->get(\sprintf('%s.repository', FormDefinition::ENTITY_NAME));
        static::assertInstanceOf(EntityRepositoryInterface::class, $formRepository);
        $this->formRepository = $formRepository;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (empty($this->formIds)) {
            return;
        }

        $this->formRepository->delete(
            \array_map(static function (string $id) {
                return ['id' => $id];
            }, $this->formIds),
            Context::createDefaultContext()
        );
    }

    public function testInsertForm(): void
    {
        $context = Context::createDefaultContext();

        $this->createForm('firstName', $context);
        $this->createForm('secondName', $context);

        static::assertCount(2, $this->formIds);
        static::assertSame(2, $this->formRepository->search(new Criteria($this->formIds), $context)->getTotal());
    }

    public function testInsertFormFailTwoRequests(): void
    {
        $context = Context::createDefaultContext();

        $exceptionThrown = false;

        try {
            $this->createForm('firstName', $context);
            $this->createForm('firstName', $context);
        } catch (WriteException $exception) {
            $exceptions = $exception->getExceptions();
            static::assertCount(1, $exceptions);

            $firstException = \current($exceptions);
            static::assertInstanceOf(WriteConstraintViolationException::class, $firstException);
            static::assertCount(1, $firstException->getViolations());

            $exceptionThrown = true;
        }

        static::assertTrue($exceptionThrown);
        static::assertCount(2, $this->formIds);
        static::assertSame(1, $this->formRepository->search(new Criteria($this->formIds), $context)->getTotal());
    }

    public function testInsertFormFailOneRequest(): void
    {
        $context = Context::createDefaultContext();

        $exceptionThrown = false;

        try {
            $this->formRepository->create([
                [
                    'title' => 'Form title',
                    'technicalName' => 'firstName',
                    'mailTemplate' => [
                        'mailTemplateTypeId' => FormDefaults::FORM_MAIL_TEMPLATE_TYPE_ID,
                        'subject' => 'Subject',
                        'contentHtml' => '<p>Hello World!</p>',
                        'contentPlain' => 'Hello World!',
                    ],
                ],
                [
                    'title' => 'Form title',
                    'technicalName' => 'firstName',
                    'mailTemplate' => [
                        'mailTemplateTypeId' => FormDefaults::FORM_MAIL_TEMPLATE_TYPE_ID,
                        'subject' => 'Subject',
                        'contentHtml' => '<p>Hello World!</p>',
                        'contentPlain' => 'Hello World!',
                    ],
                ],
            ], $context);
        } catch (WriteException $exception) {
            $exceptions = $exception->getExceptions();
            static::assertCount(1, $exceptions);

            $firstException = \current($exceptions);
            static::assertInstanceOf(WriteConstraintViolationException::class, $firstException);
            static::assertCount(2, $firstException->getViolations());

            $exceptionThrown = true;
        }

        static::assertTrue($exceptionThrown);
        static::assertSame(0, $this->formRepository->search(new Criteria($this->formIds), $context)->getTotal());
    }

    private function createForm(string $technicalName, Context $context): void
    {
        $id = Uuid::randomHex();
        $this->formIds[] = $id;
        $this->formRepository->create([
            [
                'id' => $id,
                'title' => 'Form title',
                'technicalName' => $technicalName,
                'mailTemplate' => [
                    'mailTemplateTypeId' => FormDefaults::FORM_MAIL_TEMPLATE_TYPE_ID,
                    'subject' => 'Subject',
                    'contentHtml' => '<p>Hello World!</p>',
                    'contentPlain' => 'Hello World!',
                ],
            ],
        ], $context);
    }
}
