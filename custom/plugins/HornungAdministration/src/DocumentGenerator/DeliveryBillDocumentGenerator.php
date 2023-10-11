<?php
declare(strict_types=1);

namespace HornungAdministration\DocumentGenerator;

use Shopware\Core\Checkout\Document\DocumentConfiguration;
use Shopware\Core\Checkout\Document\DocumentConfigurationFactory;
use Shopware\Core\Checkout\Document\DocumentGenerator\DocumentGeneratorInterface;
use Shopware\Core\Checkout\Document\Twig\DocumentTemplateRenderer;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;

class DeliveryBillDocumentGenerator implements DocumentGeneratorInterface
{
    public const DEFAULT_TEMPLATE = '@Hornung/documents/delivery_bill.html.twig';
    public const DOCUMENT_TYPE_TECHNICAL_NAME = 'delivery_bill';
    public const DOCUMENT_FILE_PREFIX = 'delivery_bill_';

    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var DocumentTemplateRenderer
     */
    private $documentTemplateRenderer;

    public function __construct(DocumentTemplateRenderer $documentTemplateRenderer, string $rootDir)
    {
        $this->rootDir = $rootDir;
        $this->documentTemplateRenderer = $documentTemplateRenderer;
    }

    public function supports(): string
    {
        return self::DOCUMENT_TYPE_TECHNICAL_NAME;
    }

    /**
     * @throws Error
     */
    public function generate(
        OrderEntity $order,
        DocumentConfiguration $config,
        Context $context,
        ?string $templatePath = null
    ): string {
        $templatePath = $templatePath ?? self::DEFAULT_TEMPLATE;

        $deliveries = null;
        if ($order->getDeliveries()) {
            $deliveries = $order->getDeliveries()->first();
        }

        $documentString = $this->documentTemplateRenderer->render(
            $templatePath,
            [
                'order' => $order,
                'orderDelivery' => $deliveries,
                'config' => DocumentConfigurationFactory::mergeConfiguration($config, new DocumentConfiguration())->jsonSerialize(),
                'rootDir' => $this->rootDir,
                'context' => $context,
            ],
            $context,
            $order->getSalesChannelId(),
            $order->getLanguageId(),
            $order->getLanguage()->getLocale()->getCode()
        );

        return $documentString;
    }

    public function getFileName(DocumentConfiguration $config): string
    {
        return "lieferschein_" . $config->getDocumentNumber() . "_rechnung_".$config->__get("documentInvoiceNumber")."_hornung_baushop";
    }
}
