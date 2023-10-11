<?php

declare(strict_types=1);

namespace Sisi\Search\Events;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\PageLoadedEvent;
use Shopware\Storefront\Page\Search\SearchPage;
use Symfony\Component\HttpFoundation\Request;

class SisiSearchPageLoadedEvent extends PageLoadedEvent
{
    /**
     * @var SearchPage
     */
    protected $page;

    /**
     * SisiSearchPageLoadedEvent constructor.
     * @param SearchPage $page
     * @param SalesChannelContext $salesChannelContext
     * @param Request $request
     */
    public function __construct(SearchPage $page, SalesChannelContext $salesChannelContext, Request $request)
    {
        $this->page = $page;
        parent::__construct($salesChannelContext, $request);
    }

    /**
     * @return SearchPage
     */
    public function getPage(): SearchPage
    {
        return $this->page;
    }
}
