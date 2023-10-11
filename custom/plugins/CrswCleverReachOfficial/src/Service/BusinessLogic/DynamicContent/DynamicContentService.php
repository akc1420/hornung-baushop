<?php


namespace Crsw\CleverReachOfficial\Service\BusinessLogic\DynamicContent;

use Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\DynamicContent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\Response\SearchResult\Settings;
use Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DynamicContentService as BaseDynamicContentService;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Shopware\Core\PlatformRequest;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class DynamicContentService
 *
 * @package Crsw\CleverReachOfficial\Service\BusinessLogic\DynamicContent
 */
class DynamicContentService extends BaseDynamicContentService
{
    /**
     * Return list of supported contents
     *
     * @return DynamicContent[]
     *
     * @throws QueryFilterInvalidParamException
     */
    public function getSupportedDynamicContent(): array
    {
        return [
            $this->createDynamicContent('Product Search', $this->getDynamicContentUrl())
        ];
    }

    /**
     * @param string $label
     * @param string $url
     * @return DynamicContent
     *
     * @throws QueryFilterInvalidParamException
     */
    protected function createDynamicContent(string $label, string $url): DynamicContent
    {
        $name = DynamicContent::formatName('Shopware 6', $label);
        $dynamicContent = new DynamicContent();
        $dynamicContent->setUrl($url);
        $dynamicContent->setCors(true);
        $dynamicContent->setIcon('shopware 6');
        $dynamicContent->setPassword($this->getDynamicContentPassword());
        $dynamicContent->setName($name);
        $dynamicContent->setType(Settings::PRODUCT);

        return $dynamicContent;
    }

    /**
     * @return string
     */
    protected function getDynamicContentUrl(): string
    {
        $url = 'api.cleverreach.search.new';
        $params = [];
        if (version_compare($this->getParameterBag()->get('kernel.shopware_version'), '6.4.0', 'lt')) {
            $url = 'api.cleverreach.search';
            $params['version'] = PlatformRequest::API_VERSION;
        }

        return $this->getUrlGenerator()->generate($url, $params, UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * @return UrlGeneratorInterface
     */
    protected function getUrlGenerator(): UrlGeneratorInterface
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(UrlGeneratorInterface::class);
    }

    /**
     * @return ParameterBagInterface|object
     */
    private function getParameterBag(): ParameterBagInterface
    {
        return ServiceRegister::getService(ParameterBagInterface::class);
    }
}