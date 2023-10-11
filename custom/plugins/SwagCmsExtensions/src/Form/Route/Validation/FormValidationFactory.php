<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Route\Validation;

use Shopware\Core\Framework\DataAbstractionLayer\Validation\EntityExists;
use Shopware\Core\Framework\Validation\BuildValidationEvent;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\Framework\Validation\DataValidationDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\CmsExtensions\Form\Component\ComponentRegistry;
use Swag\CmsExtensions\Form\FormDefinition;
use Swag\CmsExtensions\Form\FormEntity;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class FormValidationFactory
{
    private EventDispatcherInterface $eventDispatcher;

    private ComponentRegistry $componentRegistry;

    public function __construct(EventDispatcherInterface $eventDispatcher, ComponentRegistry $componentRegistry)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->componentRegistry = $componentRegistry;
    }

    public function create(FormEntity $form, DataBag $data, SalesChannelContext $context): DataValidationDefinition
    {
        $definition = new DataValidationDefinition('cms_extensions.form.create');
        $definition->add('formId', new NotBlank(), new EntityExists(['entity' => FormDefinition::ENTITY_NAME, 'context' => $context->getContext()]));

        $fieldValidations = $this->getFieldValidations($form, $context);
        foreach ($fieldValidations as $field => $constraints) {
            $definition->add($field, ...$constraints);
        }

        $validationEvent = new BuildValidationEvent($definition, $data, $context->getContext());
        $this->eventDispatcher->dispatch($validationEvent, $validationEvent->getName());

        return $definition;
    }

    /**
     * @return Constraint[][]
     */
    private function getFieldValidations(FormEntity $form, SalesChannelContext $context): array
    {
        $groups = $form->getGroups();
        $constraints = [];
        if ($groups === null) {
            return $constraints;
        }

        foreach ($groups->getFields() as $field) {
            $constraints[$field->getTechnicalName()] = $this->componentRegistry->getHandler($field->getType())->getValidationDefinition($field, $context);
        }

        return $constraints;
    }
}
