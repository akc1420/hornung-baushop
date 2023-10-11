<?php


namespace Crsw\CleverReachOfficial\Controller\Admin;

use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\Initializer;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Utility\SingleSignOn\SingleSignOnProvider;
use Crsw\CleverReachOfficial\Core\Infrastructure\Exceptions\BaseException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Shopware\Core\Framework\Api\Response\JsonApiResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class SingleSignOnController
 *
 * @package Crsw\CleverReachOfficial\Controller\Admin
 */
class SingleSignOnController extends AbstractController
{
    /**
     * SingleSignOnController constructor.
     *
     * @param Initializer $initializer
     */
    public function __construct(Initializer $initializer)
    {
        Bootstrap::init();
        $initializer->registerServices();
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/singleSignOn", name="api.cleverreach.singleSignOn",
     *     defaults={"auth_required"=false}, methods={"GET", "POST"})
     * @Route(path="api/cleverreach/singleSignOn", name="api.cleverreach.singleSignOn.new",
     *     defaults={"auth_required"=false}, methods={"GET", "POST"})
     *
     * @param Request $request
     *
     * @return JsonApiResponse
     */
    public function getRedirectUrl(Request $request): JsonApiResponse
    {
        $url = $request->get('url');

        try {
            $signedUrl = SingleSignOnProvider::getUrl($url);
            return new JsonApiResponse(['url' => $signedUrl]);
        } catch (BaseException $e) {
            Logger::logError($e->getMessage());
        }

        return new JsonApiResponse();
    }
}
