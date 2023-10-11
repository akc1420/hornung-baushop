<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Extension\Feature\FormBuilder;

use Shopware\Core\Content\MailTemplate\MailTemplateDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Swag\CmsExtensions\Form\FormDefinition;

class MailTemplateExtension extends EntityExtension
{
    public function getDefinitionClass(): string
    {
        return MailTemplateDefinition::class;
    }

    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            new OneToManyAssociationField(
                'swagCmsExtensionsForms',
                FormDefinition::class,
                FormDefinition::MAIL_TEMPLATE_FOREIGN_KEY_STORAGE_NAME
            )
        );
    }
}
