<?php

declare(strict_types=1);

namespace Sisi\Search\Storefront\Page;

use Shopware\Core\Content\Category\Exception\CategoryNotFoundException;
use Shopware\Core\Content\Product\SalesChannel\Search\AbstractProductSearchRoute;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\GenericPageLoaderInterface;
use Shopware\Storefront\Page\Search\SearchPage;
use Shopware\Storefront\Page\Suggest\SuggestPage;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

class SearchPageLoader
{
    /**
     * @var GenericPageLoaderInterface
     */
    private $genericLoader;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var AbstractProductSearchRoute
     */
    private $productSearchRoute;

    public function __construct(
        GenericPageLoaderInterface $genericLoader,
        AbstractProductSearchRoute $productSearchRoute,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->genericLoader = $genericLoader;
        $this->productSearchRoute = $productSearchRoute;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param Request $request
     * @param SalesChannelContext $salesChannelContext
     * @return SearchPage
     * @throws CategoryNotFoundException
     * @throws InconsistentCriteriaIdsException
     * @throws MissingRequestParameterException
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     */
    public function load(Request $request, SalesChannelContext $salesChannelContext)
    {
        $page = $this->genericLoader->load($request, $salesChannelContext);
        $page = SearchPage::createFrom($page);
        $page->setSearchTerm(
            (string)$request->query->get('search')
        );
        return $page;
    }

    /**
     * @param Request $request
     * @param SalesChannelContext $salesChannelContext
     * @return SuggestPage
     * @throws CategoryNotFoundException
     * @throws InconsistentCriteriaIdsException
     * @throws MissingRequestParameterException
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     */
    public function loadSuggest(Request $request, SalesChannelContext $salesChannelContext)
    {
        $page = $this->genericLoader->load($request, $salesChannelContext);
        $page = SuggestPage::createFrom($page);
        $page->setSearchTerm(
            (string)$request->query->get('search')
        );
        return $page;
    }


    /**
     * @param Request $request
     * @param SalesChannelContext $salesChannelContext
     * @return \Shopware\Storefront\Page\Page
     */
    public function getPage(Request $request, SalesChannelContext $salesChannelContext)
    {
        return $this->genericLoader->load($request, $salesChannelContext);
    }
}
