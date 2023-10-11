<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Util\Lifecycle;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use Swag\CmsExtensions\Form\FormDefinition;

class FormDefaults
{
    public const FORM_MAIL_TEMPLATE_TYPE_ID = '7072eded48ee479185c4a51ff4c9634d';

    private EntityRepository $mailTemplateRepository;

    private EntityRepository $mailTemplateTypeRepository;

    public function __construct(
        EntityRepository $mailTemplateRepository,
        EntityRepository $mailTemplateTypeRepository
    ) {
        $this->mailTemplateRepository = $mailTemplateRepository;
        $this->mailTemplateTypeRepository = $mailTemplateTypeRepository;
    }

    public function update(UpdateContext $context): void
    {
        if (\version_compare($context->getCurrentPluginVersion(), '1.8.0', '<')) {
            $this->addMailTemplateType($context->getContext());
            $this->addMailTemplate($context->getContext());
        }
    }

    public function activate(Context $context): void
    {
        $this->addMailTemplateType($context);
        $this->addMailTemplate($context);
    }

    public function deactivate(Context $context): void
    {
        $this->removeMailTemplate($context);
        $this->removeMailTemplateType($context);
    }

    private function addMailTemplate(Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('mailTemplateTypeId', self::FORM_MAIL_TEMPLATE_TYPE_ID));
        $criteria->addFilter(new EqualsFilter('systemDefault', true));
        $firstId = $this->mailTemplateRepository->searchIds($criteria, $context)->firstId();

        if ($firstId !== null) {
            return;
        }

        $this->mailTemplateRepository->upsert([
            [
                'mailTemplateTypeId' => self::FORM_MAIL_TEMPLATE_TYPE_ID,
                'systemDefault' => true,
                'senderName' => '{{ salesChannel.name }}',
                'description' => 'Shopware Default Template',
                'subject' => 'Form "{{ form.technicalName }}" has been submitted',
                'contentPlain' => "The form \"{{ form.technicalName }}\" has been submitted.\n" . $this->getContentPlain(),
                'contentHtml' => '<div style="font-family:arial; font-size:12px;"><p>The form "{{ form.technicalName }}" has been submitted.</p>' . $this->getContentHtml() . '</div>',
                'translations' => [
                    'de-DE' => [
                        'senderName' => '{{ salesChannel.name }}',
                        'description' => 'Shopware Standard-Template',
                        'subject' => 'Formular "{{ form.technicalName }}" wurde abgeschickt',
                        'contentPlain' => "Das Formular \"{{ form.technicalName }}\" wurde abgeschickt.\n" . $this->getContentPlain(),
                        'contentHtml' => '<div style="font-family:arial; font-size:12px;"><p>Das Formular \"{{ form.technicalName }}\" wurde abgeschickt.</p>' . $this->getContentHtml() . '</div>',
                    ],
                    'en-GB' => [
                        'senderName' => '{{ salesChannel.name }}',
                        'description' => 'Shopware Default Template',
                        'subject' => 'Form "{{ form.technicalName }}" has been submitted',
                        'contentPlain' => "The form \"{{ form.technicalName }}\" has been submitted.\n" . $this->getContentPlain(),
                        'contentHtml' => '<div style="font-family:arial; font-size:12px;"><p>The form "{{ form.technicalName }}" has been submitted.</p>' . $this->getContentHtml() . '</div>',
                    ],
                ],
            ],
        ], $context);
    }

    private function removeMailTemplate(Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('mailTemplateTypeId', self::FORM_MAIL_TEMPLATE_TYPE_ID));
        $criteria->addFilter(new EqualsFilter('systemDefault', true));
        $ids = $this->mailTemplateRepository->searchIds($criteria, $context)->getIds();

        if (\count($ids) > 0) {
            $this->mailTemplateRepository->delete(\array_map(static fn ($id) => ['id' => $id], $ids), $context);
        }
    }

    private function addMailTemplateType(Context $context): void
    {
        $this->mailTemplateTypeRepository->upsert([
            [
                'id' => self::FORM_MAIL_TEMPLATE_TYPE_ID,
                'technicalName' => 'cms_extensions.form',
                'availableEntities' => [
                    'form' => FormDefinition::ENTITY_NAME,
                    'salesChannel' => SalesChannelDefinition::ENTITY_NAME,
                ],
                'name' => 'Custom Form',
                'translations' => [
                    'de-DE' => [
                        'name' => 'Eigenes Formular',
                    ],
                    'en-GB' => [
                        'name' => 'Custom Form',
                    ],
                ],
            ],
        ], $context);
    }

    private function removeMailTemplateType(Context $context): void
    {
        $this->mailTemplateTypeRepository->delete([['id' => self::FORM_MAIL_TEMPLATE_TYPE_ID]], $context);
    }

    private function getContentHtml(): string
    {
        return '
            <table>
                {% for field in form.groups.fields %}
                    {% if field.technicalName in formData|keys %}
                        <tr>
                            <td>{{ field.label ?: field.technicalName }}</td>
                            <td>{{ attribute(formData, field.technicalName)|nl2br }}</td>
                        </tr>
                    {% endif %}
                {% endfor %}
            </table>';
    }

    private function getContentPlain(): string
    {
        return '
{% for field in form.groups.fields %}
{% if field.technicalName in formData|keys %}
{{ field.label ?: field.technicalName }}: {{ attribute(formData, field.technicalName) }}
{% endif %}
{% endfor %}';
    }
}
