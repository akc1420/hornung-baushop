<?php

namespace Crsw\CleverReachOfficial\Controller\Storefront;

use Crsw\CleverReachOfficial\Components\AuthorizationHandler\UserAuthorizedEvent;
use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\Initializer;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\AuthorizationService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Events\AuthorizationEventBus;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Http\AuthProxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Tasks\Composite\ConnectTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Stats\Contracts\SnapshotService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\TaskExecution\QueueService;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Configuration\ConfigService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\MigratedUser\MigratedUserService;
use Exception;
use Firebase\JWT\JWT;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\PlatformRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class AuthController
 *
 * @package Crsw\CleverReachOfficial\Controller\Storefront
 */
class AuthController extends AbstractController
{
    /**
     * @var QueueService
     */
    private $queueService;
    /**
     * @var AuthProxy
     */
    private $authProxy;
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;
    /**
     * @var AuthorizationService
     */
    private $authService;
    /**
     * @var SnapshotService
     */
    private $snapshotService;
    /**
     * @var MigratedUserService
     */
    private $migratedUserService;
    /**
     * @var ParameterBagInterface
     */
    private $params;

    /**
     * AuthController constructor.
     *
     * @param QueueService $queueService
     * @param AuthProxy $authProxy
     * @param UrlGeneratorInterface $urlGenerator
     * @param Initializer $initializer
     * @param AuthorizationService $authService
     * @param SnapshotService $snapshotService
     * @param MigratedUserService $migratedUserService
     * @param ParameterBagInterface $params
     */
    public function __construct(
        QueueService $queueService,
        AuthProxy $authProxy,
        UrlGeneratorInterface $urlGenerator,
        Initializer $initializer,
        AuthorizationService $authService,
        SnapshotService $snapshotService,
        MigratedUserService $migratedUserService,
        ParameterBagInterface $params
    ) {
        $this->queueService = $queueService;
        $this->authProxy = $authProxy;
        $this->urlGenerator = $urlGenerator;
        $this->authService = $authService;
        $this->snapshotService = $snapshotService;
        $this->migratedUserService = $migratedUserService;
        $this->params = $params;

        Bootstrap::init();
        $initializer->registerServices();
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="/api/v{version}/cleverreach/auth", name="api.cleverreach.auth", defaults={"auth_required"=false}, methods={"GET"})
     * @Route(path="/api/cleverreach/auth", name="api.cleverreach.auth.new", defaults={"auth_required"=false}, methods={"GET"})
     * @param Request $request
     * @return Response|JsonResponse
     */
    public function callback(Request $request)
    {
        try {
            $code = $request->get('code');

            if (empty($code)) {
                throw new HttpRequestException('Wrong parameters. Code not set.', 400);
            }

            $isRefresh = $request->get('isRefresh');
            $authInfo = $this->authProxy->getAuthInfo($code, $this->authService->getRedirectURL($isRefresh));

            if ($isRefresh) {
                $tokenInfo = $this->getTokenInfo($authInfo->getAccessToken());
                $userInfo = $this->authService->getUserInfo();
                if (($userInfo->getId() !== (string)$tokenInfo->client_id)) {
                    throw new Exception('You can not change CleverReach account.');
                }
            }

            $this->authService->setAuthInfo($authInfo);
            $this->authService->setIsOffline(false);

            $this->queueService->enqueue(
                $this->getConfigService()->getDefaultQueueName(),
                new ConnectTask()
            );

            $this->snapshotService->setInterval(30);
            $this->migratedUserService->enqueueMigrationInitialSyncTask();

            AuthorizationEventBus::getInstance()->fire(new UserAuthorizedEvent($isRefresh));
        } catch (Exception $exception) {
            return new JsonResponse(['status' => $exception->getCode(), 'message' => $exception->getMessage()]);
        }

        $content = $this->getResponse();

        return new Response($content);
    }

    /**
     * Retrieves token info.
     *
     * @param string $token
     * @return mixed
     */
    protected function getTokenInfo(string $token)
    {
        $parts = explode('.', $token);

        return json_decode(JWT::urlsafeB64Decode($parts[1]), false);
    }

    /**
     * Returns response content
     *
     * @return string
     */
    private function getResponse(): string
    {
        $routeName = 'api.cleverreach.status.new';
        $params = [];
        if (version_compare($this->params->get('kernel.shopware_version'), '6.4.0', 'lt')) {
            $routeName = 'api.cleverreach.status';
            $params['version'] = PlatformRequest::API_VERSION;
        }

        $checkStatusUrl = $this->urlGenerator->generate(
            $routeName,
            $params,
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return $this->render(
            '/administration/iframe/cleverreach.auth-iframe.script.html.twig',
            ['checkStatusUrl' => $checkStatusUrl]
        )->getContent();
    }

    /**
     * @return ConfigService
     */
    private function getConfigService(): ConfigService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(Configuration::class);
    }
}
