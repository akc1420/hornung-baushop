<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Test\Form;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Swag\CmsExtensions\Form\FormDefinition;
use Swag\CmsExtensions\Form\FormEntity;
use Swag\CmsExtensions\Util\Lifecycle\FormDefaults;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FormCrudTest extends TestCase
{
    use IntegrationTestBehaviour;

    private const FORM_TITLE = 'Form title';
    private const GROUP_TITLE = 'Group title';
    private const GROUP_TECHNICAL_NAME = 'My group';
    private const FIELD_TYPE = 'textarea';
    private const TECHNICAL_FIELD_NAME = 'name';
    private const FIELD_LABEL = 'Name';
    private const FIELD_CONFIG_KEY = 'rows';
    private const FIELD_CONFIG_VALUE = 5;
    private const FORM_TECHNICAL_NAME = 'Technical form name';

    /**
     * @var EntityRepositoryInterface
     */
    private $formRepository;

    protected function setUp(): void
    {
        /** @var EntityRepositoryInterface|null $repository */
        $repository = $this->getContainer()->get(
            \sprintf(
                '%s.repository',
                FormDefinition::ENTITY_NAME
            ),
            ContainerInterface::NULL_ON_INVALID_REFERENCE
        );
        static::assertNotNull($repository);

        $this->formRepository = $repository;
    }

    public function testCrud(): void
    {
        $context = Context::createDefaultContext();
        $entityWritten = $this->createForm($context);

        $primaryKeys = $entityWritten->getPrimaryKeys(FormDefinition::ENTITY_NAME);
        static::assertCount(1, $primaryKeys);

        $criteria = new Criteria([$primaryKeys[0]]);
        $criteria->addAssociation('groups.fields');

        /** @var FormEntity|null $form */
        $form = $this->formRepository->search($criteria, $context)->first();
        static::assertNotNull($form);
        static::assertSame(self::FORM_TITLE, $form->getTitle());
        static::assertSame(self::FORM_TECHNICAL_NAME, $form->getTechnicalName());
        $groups = $form->getGroups();
        static::assertNotNull($groups);
        $group = $groups->first();
        static::assertNotNull($group);
        static::assertSame(self::GROUP_TECHNICAL_NAME, $group->getTechnicalName());
        static::assertSame(self::GROUP_TITLE, $group->getTitle());

        $formGroupFieldCollection = $group->getFields();
        static::assertNotNull($formGroupFieldCollection);

        $field = $formGroupFieldCollection->first();
        static::assertNotNull($field);
        static::assertSame(self::FIELD_TYPE, $field->getType());
        static::assertSame(self::FIELD_LABEL, $field->getLabel());
        static::assertSame(self::TECHNICAL_FIELD_NAME, $field->getTechnicalName());
        $config = $field->getConfig();
        static::assertNotNull($config);
        static::assertArrayHasKey(self::FIELD_CONFIG_KEY, $config);
        static::assertSame(self::FIELD_CONFIG_VALUE, $config[self::FIELD_CONFIG_KEY]);
    }

    private function createForm(Context $context): EntityWrittenContainerEvent
    {
        return $this->formRepository->create([
            [
                'title' => self::FORM_TITLE,
                'technicalName' => self::FORM_TECHNICAL_NAME,
                'mailTemplate' => [
                    'mailTemplateTypeId' => FormDefaults::FORM_MAIL_TEMPLATE_TYPE_ID,
                    'subject' => 'Subject',
                    'contentHtml' => '<p>Hello World!</p>',
                    'contentPlain' => 'Hello World!',
                ],
                'groups' => [
                    [
                        'position' => 0,
                        'technicalName' => self::GROUP_TECHNICAL_NAME,
                        'title' => self::GROUP_TITLE,
                        'fields' => [
                            [
                                'position' => 0,
                                'width' => 12,
                                'type' => self::FIELD_TYPE,
                                'required' => false,
                                'technicalName' => self::TECHNICAL_FIELD_NAME,
                                'label' => self::FIELD_LABEL,
                                'config' => [
                                    self::FIELD_CONFIG_KEY => self::FIELD_CONFIG_VALUE,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], $context);
    }
}
