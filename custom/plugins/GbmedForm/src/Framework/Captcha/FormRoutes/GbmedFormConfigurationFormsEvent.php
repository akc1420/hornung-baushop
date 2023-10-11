<?php
/**
 * gb media
 * All Rights Reserved.
 *
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * The content of this file is proprietary and confidential.
 *
 * @category       Shopware
 * @package        Shopware_Plugins
 * @subpackage     GbmedForm
 * @copyright      Copyright (c) 2020, gb media
 * @license        proprietary
 * @author         Giuseppe Bottino
 * @link           http://www.gb-media.biz
 */

declare(strict_types=1);

namespace Gbmed\Form\Framework\Captcha\FormRoutes;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

class GbmedFormConfigurationFormsEvent extends Event
{

    private array $extensionForms;
    private SalesChannelContext $salesChannelContext;

    public function __construct(array $extensionForms, SalesChannelContext $salesChannelContext)
    {
        $this->extensionForms = $extensionForms;
        $this->salesChannelContext = $salesChannelContext;
    }

    public function getExtensionForms(): array
    {
        return array_unique($this->extensionForms);
    }

    public function addExtensionForms(string $extensionForm): GbmedFormConfigurationFormsEvent
    {
        $this->extensionForms[] = $extensionForm;
        return $this;
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }
}
