<?php


namespace Crsw\CleverReachOfficial\Controller\Storefront;

use Crsw\CleverReachOfficial\Components\Webhooks\EventsHandler;
use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\Initializer;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\ReceiverEventsService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\WebHooks\Handler;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ReceiverWebhookController
 *
 * @package Crsw\CleverReachOfficial\Controller\Storefront
 */
class ReceiverWebhookController extends AbstractController
{
    /**
     * @var Handler
     */
    private $receiverHandler;
    /**
     * @var ReceiverEventsService
     */
    private $eventsService;

    /**
     * ReceiverWebhookController constructor.
     *
     * @param Initializer $initializer
     * @param Handler $handler
     * @param ReceiverEventsService $eventsService
     */
    public function __construct(
        Initializer $initializer,
        Handler $handler,
        ReceiverEventsService $eventsService
    ) {
        Bootstrap::init();
        $initializer->registerServices();

        $this->receiverHandler = $handler;
        $this->eventsService = $eventsService;
    }

    /**
     * Process request from CleverReach.
     *
     * @RouteScope(scopes={"api"})
     * @Route(path="/api/v{version}/cleverreach/receiverWebhook", name="api.cleverreach.receiverWebhook",
     *      defaults={"csrf_protected"=false, "auth_required"=false}, methods={"GET", "POST"})
     * @Route(path="/api/cleverreach/receiverWebhook", name="api.cleverreach.receiverWebhook.new",
     *      defaults={"csrf_protected"=false, "auth_required"=false}, methods={"GET", "POST"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function webhookHandler(Request $request): Response
    {
        $eventsHandler = new EventsHandler($this->eventsService, $this->receiverHandler);
        $responseBody = $eventsHandler->handleRequest($request);

        if (!empty($responseBody['verificationToken'])) {
            return new Response($responseBody['verificationToken'], $responseBody['httpCode']);
        }

        return new Response();
    }
}
