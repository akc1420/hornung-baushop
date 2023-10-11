<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT10905;

use Shopware\Core\Content\ProductExport\Error\ErrorCollection;
use Shopware\Core\Content\ProductExport\ProductExportEntity;
use Shopware\Core\Content\ProductExport\Validator\ValidatorInterface;
use Shopware\Core\Content\ProductExport\Validator\XmlValidator;

class XmlValidatorDecorator implements ValidatorInterface
{
    /**
     * @var XmlValidator|null
     */
    private $decorated;

    public function __construct(?XmlValidator $decorated)
    {
        $this->decorated = $decorated;
    }

    public function validate(ProductExportEntity $productExportEntity, string $productExportContent, ErrorCollection $errors): void
    {
        $backup = libxml_disable_entity_loader(true);

        $this->decorated->validate($productExportEntity, $productExportContent, $errors);

        libxml_disable_entity_loader($backup);
    }
}
