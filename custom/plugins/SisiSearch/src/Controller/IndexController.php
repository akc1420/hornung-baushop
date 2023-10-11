<?php

declare(strict_types=1);

namespace Sisi\Search\Controller;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Sisi\Search\Service\BackendIndexService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope"={"api"}})
 */
class IndexController extends AbstractController
{
    /**
     *
     * @var ContainerInterface
     */
    protected $container;


    /**
     *
     * @var SystemConfigService
     */
    protected $config;


    public function __construct(ContainerInterface $container, SystemConfigService $config)
    {
        $this->container = $container;
        $this->config = $config;
    }

    /**
     * @Route("api/_action/sisi/sisisearch", name="api.action.sisisearch", methods={"POST"})
     */
    public function startIndexApi(Request $request): JsonResponse
    {
        $result = (array)json_decode($request->getContent());
        $result = (array)$result['config'];
        $config = $this->config->get("SisiSearch.config", $result['shopID']);
        $pfad = $this->container->getParameter('kernel.project_dir');
        $indexServiceHaenlder = new BackendIndexService($pfad);
        $message = $indexServiceHaenlder->startIndex($result, $config);
        return new JsonResponse($message);
    }

    /**
     * @Route("api/_action/sisi/sisisearch/status", name="api.action.sisisearch.status", methods={"POST"})
     */
    public function getStatus(Request $request): JsonResponse
    {
        $result = (array)json_decode($request->getContent());
        $result = (array)$result['config'];
        $pfad = $this->container->getParameter('kernel.project_dir');
        $indexServiceHaenlder = new BackendIndexService($pfad);
        $message = $indexServiceHaenlder->status($result['pid']);
        $logs = $indexServiceHaenlder->getLog();
        $result['status'] = $message;
        $newLog = [];
        foreach ($logs as $log) {
            $newLog[] = str_replace(array("\r", "\n"), '', $log);
        }
        $result['log'] = $newLog;
        return new JsonResponse($result);
    }

    /**
     * @Route("api/_action/sisi/sisisearch/delete", name="api.action.sisisearch.delete", methods={"POST"})
     */
    public function delete(Request $request): JsonResponse
    {
        $result = (array)json_decode($request->getContent());
        $result = (array)$result['config'];
        $pfad = $this->container->getParameter('kernel.project_dir');
        $indexServiceHaenlder = new BackendIndexService($pfad);
        $config = $this->config->get("SisiSearch.config", $result['shopID']);
        $message = $indexServiceHaenlder->delete($result, $config);
        $logs = $indexServiceHaenlder->getLog();
        $result['status'] = $message;
        $newLog = [];
        foreach ($logs as $log) {
            $newLog[] = str_replace(array("\r", "\n"), '', $log);
        }
        $result['log'] = $newLog;
        return new JsonResponse($result);
    }

    /**
     *
     *
     * @Route("api/_action/sisi/sisisearch/inaktive", name="api.action.sisisearch.inaktive", methods={"POST"})
     */
    public function deleteinaktive(Request $request): JsonResponse
    {
        $result = (array)json_decode($request->getContent());
        $result = (array)$result['config'];
        $pfad = $this->container->getParameter('kernel.project_dir');
        $indexServiceHaenlder = new BackendIndexService($pfad);
        $config = $this->config->get("SisiSearch.config", $result['shopID']);
        $message = $indexServiceHaenlder->inaktive($result, $config);
        $logs = $indexServiceHaenlder->getLog();
        $result['status'] = $message;
        $newLog = [];
        foreach ($logs as $log) {
            $newLog[] = str_replace(array("\r", "\n"), '', $log);
        }
        $result['log'] = $newLog;
        return new JsonResponse($result);
    }


    /**
     *
     *
     * @Route("api/_action/sisi/sisisearch/channel", name="api.action.sisisearch.channel", methods={"POST"})
     */
    public function getChannels(): JsonResponse
    {
        $pfad = $this->container->getParameter('kernel.project_dir');
        $indexServiceHaenlder = new BackendIndexService($pfad);
        $channels = $indexServiceHaenlder->getChannel($this->container);
        $return = [];
        foreach ($channels as $channel) {
            $return['channel'][] = ['text' => $channel->getName(), 'value' => $channel->getId()];
        }
        $languages = $indexServiceHaenlder->getLanguages($this->container);
        $return['language'][] = ['text' => '', 'value' => ''];
        foreach ($languages as $lanuguage) {
            $return['language'][] = ['text' => $lanuguage->getName(), 'value' => $lanuguage->getId()];
        }
        return new JsonResponse($return);
    }
}
