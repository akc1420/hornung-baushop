<?php

namespace Crsw\CleverReachOfficial\Components\DynamicContent;

use Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\Request\DynamicContentRequest;
use Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\Request\SearchTerms;
use Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\Response\Filter\DropdownOption;
use Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\Response\Filter\Filter;
use Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\Response\Filter\FilterCollection;
use Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\Response\SearchResult\SearchResult;
use Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DynamicContentHandler as BaseDynamicContentHandler;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Language\Contracts\TranslationService;
use Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Language\LanguageService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Search\ProductSearchService;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class DynamicContentHandler
 *
 * @package Crsw\CleverReachOfficial\Components\DynamicContent
 */
class DynamicContentHandler extends BaseDynamicContentHandler
{
    public const PRODUCT_KEY = 'product';
    public const LANGUAGE_KEY = 'lang';

    /**
     * Processes dynamic content request from CleverReach.
     *
     * @param Request $request
     *
     * @return array
     *
     * @throws HttpAuthenticationException
     * @throws QueryFilterInvalidParamException
     */
    public function handleRequest(Request $request): array
    {
        $dynamicContentRequest = $this->formatDynamicContentRequest($request);

        return $this->handle($dynamicContentRequest);
    }

    /**
     * Returns filters for the dynamic content
     *
     * @return FilterCollection
     */
    protected function getFilters(): FilterCollection
    {
        $filters = new FilterCollection();

        $productFilterName = $this->getTranslationService()->translate('clever-reach.search.product');

        $filters->addFilter($this->getLanguageFilter());
        $filters->addFilter($this->createFilter($productFilterName, self::PRODUCT_KEY, Filter::INPUT));

        return $filters;
    }

    /**
     * Returns search results
     *
     * @param SearchTerms $searchTerms
     *
     * @return SearchResult
     */
    protected function getSearchResults(SearchTerms $searchTerms): SearchResult
    {
        $context = $this->getContext();

        $lang = $searchTerms->getSearchTerms()['lang'];
        $product = $searchTerms->getSearchTerms()['product'];

        return $this->getProductSearchService()->searchProducts($product, $lang, $context);
    }

    /**
     * @param Request $request
     *
     * @return DynamicContentRequest
     */
    protected function formatDynamicContentRequest(Request $request): DynamicContentRequest
    {
        $type = $request->get('get');

        $dynamicContentRequest = new DynamicContentRequest($type, $request->get('password'), '');

        if ($type === 'search') {
            $searchTerms = new SearchTerms();
            $searchTerms->add(self::LANGUAGE_KEY, $request->get('lang'));
            $searchTerms->add(self::PRODUCT_KEY, $request->get('product'));
            $dynamicContentRequest->setSearchTerms($searchTerms);
        }

        return $dynamicContentRequest;
    }

    /**
     * @return Filter
     */
    private function getLanguageFilter(): Filter
    {
        $filterName = $this->getTranslationService()->translate('clever-reach.search.language');

        $context = $this->getContext();
        $locales = $this->getLanguageService()->getLanguages($context);

        $filter = $this->createFilter($filterName, self::LANGUAGE_KEY, Filter::DROPDOWN);

        foreach ($locales as $locale) {
            $filter->addDropdownOption(new DropdownOption($locale->getName(), $locale->getId()));
        }

        return $filter;
    }

    /**
     * @return Context
     */
    private function getContext(): Context
    {
        $request = $this->getRequestStack()->getCurrentRequest();
        return $request ? $request->get('sw-context') : Context::createDefaultContext();
    }

    /**
     * @return RequestStack
     */
    private function getRequestStack(): RequestStack
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(RequestStack::class);
    }

    /**
     * @return TranslationService
     */
    private function getTranslationService(): TranslationService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(TranslationService::class);
    }

    /**
     * @return LanguageService
     */
    private function getLanguageService(): LanguageService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(LanguageService::class);
    }

    /**
     * @return ProductSearchService
     */
    private function getProductSearchService(): ProductSearchService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(ProductSearchService::class);
    }
}
