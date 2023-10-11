<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent;

use Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\Contracts\DynamicContentHandler as BaseService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\Request\DynamicContentRequest;
use Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\Request\SearchTerms;
use Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\Response\Filter\Filter;
use Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\Response\Filter\FilterCollection;
use Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\Response\SearchResult\SearchResult;
use Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\Response\SearchResult\Settings;
use Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\Response\SearchResult\Transformer\Transformer;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager;
use Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

/**
 * Class DynamicContentHandler
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent
 */
abstract class DynamicContentHandler implements BaseService
{
    /**
     * @var DynamicContentService
     */
    protected $dynamicContentService;

    /**
     * @param DynamicContentRequest $request
     *
     * @return array
     *
     * @throws HttpAuthenticationException
     * @throws QueryFilterInvalidParamException
     */
    public function handle(DynamicContentRequest $request)
    {
        ConfigurationManager::getInstance()->setContext($request->getContext());
        $this->verifyPassword($request->getPassword());
        if ($request->getType() === 'filter') {
            return $this->getFilters()->toArray();
        }

        $results = $this->getSearchResults($request->getSearchTerms());

        return $results ? Transformer::transform($results) : array();
    }

    /**
     * Validates dynamic content password
     *
     * @param string $password
     *
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     */
    protected function verifyPassword($password)
    {
        if ($this->getDynamicContentService()->getDynamicContentPassword() !== $password) {
            throw new HttpAuthenticationException("Dynamic content password doesn't match with the stored password", 403);
        }
    }

    /**
     * Returns filters for the dynamic content
     *
     * @return FilterCollection
     */
    abstract protected function getFilters();

    /**
     * Returns search results
     *
     * @param SearchTerms $searchTerms
     *
     * @return SearchResult
     */
    abstract protected function getSearchResults(SearchTerms $searchTerms);

    /**
     * Creates filter for the provided parameters
     *
     * @param string $name
     * @param string $key
     * @param string $type
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\Response\Filter\Filter
     */
    protected function createFilter($name, $key, $type)
    {
        $filter = new Filter($name, $key, $type);
        $filter->setDescription($name);
        $filter->setRequired(true);

        return $filter;
    }

    /**
     * Creates default settings DTO
     *
     * @param string $type
     *
     * @return Settings
     */
    protected function createDefaultSettings($type)
    {
        $settings = new Settings();
        $settings->setType($type);
        $settings->setLinkEditable(true);
        $settings->setImageSizeEditable(true);
        $settings->setLinkTextEditable(true);

        return $settings;
    }

    /**
     * @return DynamicContentService
     */
    protected function getDynamicContentService()
    {
        if ($this->dynamicContentService === null) {
            $this->dynamicContentService = ServiceRegister::getService(DynamicContentService::CLASS_NAME);
        }

        return $this->dynamicContentService;
    }
}