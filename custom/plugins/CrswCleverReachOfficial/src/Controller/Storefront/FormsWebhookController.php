<?php


namespace Crsw\CleverReachOfficial\Controller\Storefront;

use Crsw\CleverReachOfficial\Components\Webhooks\EventsHandler;
use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\Initializer;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\FormEventsService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\WebHooks\Handler;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class FormsWebhookController
 *
 * @package Crsw\CleverReachOfficial\Controller\Storefront
 */
class FormsWebhookController extends AbstractController
{
    /**
     * @var Handler
     */
    private $formsHandler;
    /**
     * @var FormEventsService
     */
    private $eventsService;

    /**
     * FormsWebhookController constructor.
     *
     * @param Initializer $initializer
     * @param Handler $handler
     * @param FormEventsService $eventsService
     */
    public function __construct(
        Initializer $initializer,
        Handler $handler,
        FormEventsService $eventsService
    ) {
        Bootstrap::init();
        $initializer->registerServices();

        $this->formsHandler = $handler;
        $this->eventsService = $eventsService;
    }

    /**
     * Process request from CleverReach.
     *
     * @RouteScope(scopes={"api"})
     * @Route(path="/api/v{version}/cleverreach/formsWebhook", name="api.cleverreach.formsWebhook",
     *     defaults={"csrf_protected"=false, "auth_required"=false}, methods={"GET", "POST"})
     * @Route(path="/api/cleverreach/formsWebhook", name="api.cleverreach.formsWebhook.new",
     *     defaults={"csrf_protected"=false, "auth_required"=false}, methods={"GET", "POST"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function webhookHandler(Request $request): Response
    {
        $eventsHandler = new EventsHandler($this->eventsService, $this->formsHandler);
        $responseBody = $eventsHandler->handleRequest($request);

        if (!empty($responseBody['verificationToken'])) {
            return new Response($responseBody['verificationToken'], $responseBody['httpCode']);
        }

        return new Response();
    }
}
