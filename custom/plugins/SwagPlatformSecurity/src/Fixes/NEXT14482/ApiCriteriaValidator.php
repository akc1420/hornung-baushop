<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT14482;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\AssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Field;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ReadProtected;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\RequestCriteriaBuilder;
use Shopware\Core\PlatformRequest;
use Symfony\Component\HttpFoundation\Request;

class ApiCriteriaValidator extends RequestCriteriaBuilder
{
    /**
     * @var RequestCriteriaBuilder
     */
    private $decorated;

    public function __construct(RequestCriteriaBuilder $decorated)
    {
        $this->decorated = $decorated;
    }

    public function handleRequest(Request $request, Criteria $criteria, EntityDefinition $definition, Context $context): Criteria
    {
        // internal request?
        if (!$request->attributes->has(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT)) {
            return $this->decorated->handleRequest($request, $criteria, $definition, $context);
        }

        $temp = new Criteria();

        $temp = $this->decorated->handleRequest($request, $temp, $definition, $context);

        $this->validate($definition, $temp, $context);

        return $this->decorated->handleRequest($request, $criteria, $definition, $context);
    }

    public function getMaxLimit(): int
    {
        return $this->decorated->getMaxLimit();
    }

    public function toArray(Criteria $criteria): array
    {
        return $this->decorated->toArray($criteria);
    }

    private function validate(EntityDefinition $definition, Criteria $criteria, Context $context): void
    {
        foreach ($criteria->getAllFields() as $accessor) {
            $fields = $this->getFieldsOfAccessor($definition, $accessor);

            foreach ($fields as $field) {
                if (!$field instanceof Field) {
                    continue;
                }

                /** @var ReadProtected|null $flag */
                $flag = $field->getFlag(ReadProtected::class);

                if ($flag === null) {
                    throw new ApiProtectionException($accessor);
                }

                if (!$flag->isSourceAllowed(get_class($context->getSource()))) {
                    throw new ApiProtectionException($accessor);
                }
            }
        }
    }

    private function getFieldsOfAccessor(EntityDefinition $definition, string $accessor, bool $resolveTranslated = true): array
    {
        $parts = explode('.', $accessor);
        if ($definition->getEntityName() === $parts[0]) {
            array_shift($parts);
        }

        $accessorFields = [];

        $source = $definition;

        foreach ($parts as $part) {
            $fields = $source->getFields();

            if ($part === 'extensions') {
                continue;
            }
            $field = $fields->get($part);

            if ($field instanceof TranslatedField && $resolveTranslated) {
                $source = $source->getTranslationDefinition();
                $fields = $source->getFields();
                $accessorFields[] = $fields->get($part);

                continue;
            }

            if ($field instanceof TranslatedField && !$resolveTranslated) {
                $accessorFields[] = $field;

                continue;
            }

            $accessorFields[] = $field;

            if (!$field instanceof AssociationField) {
                break;
            }

            $source = $field->getReferenceDefinition();
            if ($field instanceof ManyToManyAssociationField) {
                $source = $field->getToManyReferenceDefinition();
            }
        }

        return $accessorFields;
    }
}
