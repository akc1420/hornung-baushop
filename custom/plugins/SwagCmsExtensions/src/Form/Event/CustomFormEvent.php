<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Event;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\EventData\ArrayType;
use Shopware\Core\Framework\Event\EventData\EntityType;
use Shopware\Core\Framework\Event\EventData\EventDataCollection;
use Shopware\Core\Framework\Event\EventData\ObjectType;
use Shopware\Core\Framework\Event\EventData\ScalarValueType;
use Shopware\Core\Framework\Event\SalesChannelAware;
use Shopware\Core\Framework\Event\ShopwareSalesChannelEvent;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\CmsExtensions\Form\FormDefinition;
use Swag\CmsExtensions\Form\FormEntity;
use Symfony\Contracts\EventDispatcher\Event;

class CustomFormEvent extends Event implements SalesChannelAware, ShopwareSalesChannelEvent
{
    public const EVENT_NAME = 'cms_extensions.form.sent';

    private SalesChannelContext $salesChannelContext;

    private FormEntity $form;

    /**
     * @var array<string, string|null>
     */
    private array $formData;

    /**
     * @param array<string, string|null> $formData
     */
    public function __construct(SalesChannelContext $context, FormEntity $form, array $formData)
    {
        $this->salesChannelContext = $context;
        $this->form = $form;
        $this->formData = $formData;
    }

    public static function getAvailableData(): EventDataCollection
    {
        return (new EventDataCollection())
            ->add('formData', new ArrayType(new ScalarValueType(ScalarValueType::TYPE_STRING)))
            ->add('form', new EntityType(FormDefinition::class))
            ->add('salesChannelContext', new ObjectType());
    }

    public function getName(): string
    {
        return self::EVENT_NAME;
    }

    public function getContext(): Context
    {
        return $this->salesChannelContext->getContext();
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }

    public function getSalesChannelId(): string
    {
        return $this->salesChannelContext->getSalesChannelId();
    }

    /**
     * @return array<string, string|null>
     */
    public function getFormData(): array
    {
        return $this->formData;
    }

    public function getForm(): FormEntity
    {
        return $this->form;
    }
}
