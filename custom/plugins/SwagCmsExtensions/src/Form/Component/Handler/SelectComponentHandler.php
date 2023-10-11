<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Component\Handler;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\DataAbstractionLayer\Validation\EntityExists;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\System\Country\CountryDefinition;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\Salutation\SalutationDefinition;
use Shopware\Core\System\Salutation\SalutationEntity;
use Swag\CmsExtensions\Form\Aggregate\FormGroupField\FormGroupFieldEntity;
use Swag\CmsExtensions\Form\Aggregate\FormGroupField\Type\Select;
use Swag\CmsExtensions\Form\Component\AbstractComponentHandler;
use Swag\CmsExtensions\Form\Component\Exception\UnsupportedSelectEntityException;
use Swag\CmsExtensions\Form\FormEntity;
use Symfony\Component\Validator\Constraints\AtLeastOneOf;
use Symfony\Component\Validator\Constraints\Blank;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;

class SelectComponentHandler extends AbstractComponentHandler
{
    protected EntityRepository $countryRepository;

    protected EntityRepository $salutationRepository;

    public function __construct(
        EntityRepository $countryRepository,
        EntityRepository $salutationRepository
    ) {
        $this->countryRepository = $countryRepository;
        $this->salutationRepository = $salutationRepository;
    }

    public function getComponentType(): string
    {
        return Select::NAME;
    }

    public function prepareStorefront(FormEntity $form, FormGroupFieldEntity $field, SalesChannelContext $context): void
    {
        parent::prepareStorefront($form, $field, $context);

        $config = $field->getTranslation('config');
        if (isset($config['entity'])) {
            $addedOptions = $this->prepareEntity($config['entity'], $context);

            $config['options'] = \array_merge($config['options'] ?? [], $addedOptions);

            $field->addTranslated('config', $config);
        }
    }

    public function render(FormGroupFieldEntity $field, DataBag $formData, SalesChannelContext $context): ?string
    {
        $data = $formData->get($field->getTechnicalName());

        $config = $field->getTranslation('config');
        if (!isset($config['entity']) || !Uuid::isValid($data)) {
            return parent::render($field, $formData, $context);
        }

        $criteria = new Criteria([$data]);
        $property = 'name';

        switch ($config['entity']) {
            case SalutationDefinition::ENTITY_NAME:
                $repository = $this->salutationRepository;
                $property = 'displayName';

                break;
            case CountryDefinition::ENTITY_NAME:
                $repository = $this->countryRepository;

                break;
            default:
                throw new UnsupportedSelectEntityException($config['entity']);
        }

        $entity = $repository->search($criteria, $context->getContext())->first();

        if ($entity === null) {
            return parent::render($field, $formData, $context);
        }

        return $entity->get($property);
    }

    public function getValidationDefinition(FormGroupFieldEntity $field, SalesChannelContext $context): array
    {
        $parent = parent::getValidationDefinition($field, $context);

        $config = $field->getTranslation('config');
        if ($config === null || (!isset($config['options']) && !isset($config['entity']))) {
            // no values are possible, if there is no config
            return [new Blank(), new NotBlank()];
        }

        $validations = [];

        if (isset($config['options'])) {
            $atLeastOneOfCollection = [
                new Choice(\array_keys($config['options'])),
                new Choice(\array_values($config['options'])),
            ];

            if (!$field->isRequired()) {
                $atLeastOneOfCollection[] = new Blank();
            }

            $validations[] = new AtLeastOneOf($atLeastOneOfCollection);
        }

        if (isset($config['entity'])) {
            $exists = new EntityExists(['entity' => $config['entity'], 'context' => $context->getContext()]);
            $validations[] = $exists;
        }

        if (\count($validations) === 1) {
            $parent[] = \current($validations);
        }

        if (\count($validations) > 1) {
            $parent[] = new AtLeastOneOf($validations);
        }

        return $parent;
    }

    /**
     * @return array<string, string|null>
     */
    protected function prepareEntity(string $entityType, SalesChannelContext $context): array
    {
        $options = [];

        switch ($entityType) {
            case SalutationDefinition::ENTITY_NAME:
                $result = $this->salutationRepository->search(new Criteria(), $context->getContext());

                /** @var SalutationEntity $entity */
                foreach ($result->getEntities() as $entity) {
                    $options[$entity->getId()] = $entity->getTranslation('displayName');
                }

                break;
            case CountryDefinition::ENTITY_NAME:
                $criteria = new Criteria();
                $criteria->addFilter(new EqualsFilter('active', true));
                $criteria->addFilter(new EqualsFilter('salesChannels.id', $context->getSalesChannelId()));
                $criteria->addSorting(new FieldSorting('position'), new FieldSorting('name'));
                $result = $this->countryRepository->search($criteria, $context->getContext());

                /** @var CountryEntity $entity */
                foreach ($result->getEntities() as $entity) {
                    $options[$entity->getId()] = $entity->getTranslation('name');
                }

                break;
            default:
                throw new UnsupportedSelectEntityException($entityType);
        }

        return $options;
    }
}
