<?php

declare(strict_types=1);

namespace salty\Sw6PerformanceAnalysis\Controller;

use salty\Sw6PerformanceAnalysis\Analyzer\Analyzer;
use salty\Sw6PerformanceAnalysis\Struct\ResultCollection;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"api"})
 * @Route(path="/api/_action/salty-performance-analysis")
 */
class PerformanceAnalysis extends AbstractController
{
    /** @var iterable<Analyzer> */
    private $serverConfigAnalyzers;

    /** @var iterable<Analyzer> */
    private $shopwareConfigAnalyzers;

    public function __construct(iterable $serverConfigAnalyzers, iterable $shopwareConfigAnalyzers)
    {
        $this->serverConfigAnalyzers   = $serverConfigAnalyzers;
        $this->shopwareConfigAnalyzers = $shopwareConfigAnalyzers;
    }

    /**
     * @Route(path="/shopware-configuration", methods={"GET"}, name="api.salty.performance.analysis.shopware-configurations-information")
     */
    public function getShopwareConfigurationInformation(): JsonResponse
    {
        $result = new ResultCollection();

        foreach ($this->shopwareConfigAnalyzers as $analyzer) {
            $analyzer->analyze($result);
        }

        return new JsonResponse($result);
    }

    /**
     * @Route(path="/server-configuration", methods={"GET"}, name="api.salty.performance.analysis.server-configurations-information")
     */
    public function getServerConfigurationInformation(): JsonResponse
    {
        $result = new ResultCollection();

        foreach ($this->serverConfigAnalyzers as $analyzer) {
            $analyzer->analyze($result);
        }

        return new JsonResponse($result);
    }
}
