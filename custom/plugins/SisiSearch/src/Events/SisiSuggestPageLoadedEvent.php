<?php

declare(strict_types=1);

namespace Sisi\Search\Events;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\PageLoadedEvent;
use Shopware\Storefront\Page\Suggest\SuggestPage;
use Symfony\Component\HttpFoundation\Request;

class SisiSuggestPageLoadedEvent extends PageLoadedEvent
{
    /**
     * @var SuggestPage
     */
    protected $page;

    public function __construct(SuggestPage $page, SalesChannelContext $salesChannelContext, Request $request)
    {
        $this->page = $page;
        parent::__construct($salesChannelContext, $request);
    }

    public function getPage(): SuggestPage
    {
        return $this->page;
    }
}
