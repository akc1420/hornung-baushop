<?php

declare(strict_types=1);

namespace salty\Sw6PerformanceAnalysis\Analyzer\Shopware;

use salty\Sw6PerformanceAnalysis\Analyzer\Analyzer;
use salty\Sw6PerformanceAnalysis\Struct\ResultCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class Plugin extends Analyzer
{
    private const REQUIREMENTS = [
        'pluginsInstalled' => [
            'maxValue'       => 15,
            'suggestedValue' => 10,
        ],
    ];

    /** @var EntityRepositoryInterface */
    private $pluginRepository;

    public function __construct(EntityRepositoryInterface $pluginRepository)
    {
        $this->pluginRepository = $pluginRepository;
    }

    public function analyze(ResultCollection $collection): ResultCollection
    {
        $this->checkPluginsInstalled($collection);

        return $collection;
    }

    private function checkPluginsInstalled(ResultCollection $collection): void
    {
        $quantity = $this->pluginRepository->searchIds(new Criteria(), Context::createDefaultContext())->getTotal();

        $this->getResult($collection, 'pluginsInstalled', $quantity, self::REQUIREMENTS);
    }
}
