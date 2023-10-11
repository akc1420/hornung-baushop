<?php


namespace Crsw\CleverReachOfficial\Controller\Storefront;

use Crsw\CleverReachOfficial\Components\Webhooks\EventsHandler;
use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\Initializer;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\FormEventsService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\WebHooks\Handler as FormHandler;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\ReceiverEventsService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\WebHooks\Handler;
use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Contracts\EventsService;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class WebhookController
 *
 * @package Crsw\CleverReachOfficial\Controller\Storefront
 *
 * This controller handles webhooks for users that are migrated from v2.
 */
class WebhookController extends AbstractController
{
    /**
     * WebhookController constructor.
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
     * @Route(path="/api/v{version}/cleverreach/webhook", name="api.cleverreach.webhook",
     *      defaults={"csrf_protected"=false, "auth_required"=false}, methods={"GET", "POST"})
     * @Route(path="/api/cleverreach/webhook", name="api.cleverreach.webhook.new",
     *      defaults={"csrf_protected"=false, "auth_required"=false}, methods={"GET", "POST"})
     *
     * @param Request $request
     * @param SalesChannelContext $context
     * @param Context $defaultContext
     *
     * @return Response
     */
    public function webhookHandler(Request $request, SalesChannelContext $context, Context $defaultContext): Response
    {
        $event = json_decode($request->getContent(), true)['event'];

        $eventsService = $this->getEventsService($event);
        $handler = $this->getHandler($event);

        if (!$eventsService || !$handler) {
            return new Response('', 400);
        }

        $eventsHandler = new EventsHandler($eventsService, $handler);
        $responseData = $eventsHandler->handleRequest($request);

        if (!empty($responseData['verificationToken'])) {
            return new Response($responseData['verificationToken'], $responseData['httpCode']);
        }

        return new Response();
    }

    /**
     * @param $event
     * @return EventsService
     */
    private function getEventsService($event): ?EventsService
    {
        if (strpos($event, 'receiver') !== false) {
            /** @noinspection PhpIncompatibleReturnTypeInspection */
            return ServiceRegister::getService(ReceiverEventsService::CLASS_NAME);
        }

        if (strpos($event, 'forms') !== false) {
            /** @noinspection PhpIncompatibleReturnTypeInspection */
            return ServiceRegister::getService(FormEventsService::CLASS_NAME);
        }

        return null;
    }

    /**
     * @param $event
     * @return mixed
     */
    private function getHandler($event)
    {
        if (strpos($event, 'receiver') !== false) {
            return new Handler();
        }

        if (strpos($event, 'forms') !== false) {
            return new FormHandler();
        }

        return null;
    }
}
