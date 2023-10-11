<?php

namespace Sisi\Search\Service;

use Doctrine\DBAL\Connection;
use Elasticsearch\ClientBuilder;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceParameters;
use Sisi\Search\Service\ContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;

class SisiGetFieldsService
{
    /**
     * @var EntityRepositoryInterface
     */
    private $salesChannelRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $searchEsFieldsRepository;

    /**
     * @var Context
     */
    private static $defaultContext;

    public function __construct(
        EntityRepositoryInterface $salesChannelRepository,
        EntityRepositoryInterface $searchEsFieldsRepository
    ) {
        $this->salesChannelRepository = $salesChannelRepository;
        $this->searchEsFieldsRepository = $searchEsFieldsRepository;

        self::$defaultContext = Context::createDefaultContext();
    }

    /**
     * @return EntitySearchResult|string
     */
    public function getFields()
    {
        $fieldsService = $this->searchEsFieldsRepository;
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('tablename', 'product'));
        $criteria->addFilter(new EqualsFilter('fieldtype', 'text'));
        if ($fieldsService != null) {
            return $fieldsService->search($criteria, self::$defaultContext);
        }
        return '';
    }

    /**
     * @return array|string
     */
    public function channels()
    {
        $criteriaChannel = new Criteria();
        $criteriaChannel->addAssociation('languages');
        if ($this->salesChannelRepository != null) {
            return $this->salesChannelRepository->search($criteriaChannel, self::$defaultContext)->getEntities()->getElements();
        }
        return '';
    }
}
