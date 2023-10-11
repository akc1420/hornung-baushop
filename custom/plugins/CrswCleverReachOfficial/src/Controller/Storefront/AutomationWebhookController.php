<?php


namespace Crsw\CleverReachOfficial\Controller\Storefront;

use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\Initializer;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Entities\CartAutomation;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Interfaces\CartAutomationService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Webhooks\Handler;
use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\DTO\WebHook;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Exception;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AutomationWebhookController
 *
 * @package Crsw\CleverReachOfficial\Controller\Storefront
 */
class AutomationWebhookController extends AbstractController
{
    /**
     * @var CartAutomationService
     */
    private $cartAutomationService;

    /**
     * AutomationWebhookController constructor.
     *
     * @param Initializer $initializer
     * @param CartAutomationService $cartAutomationService
     */
    public function __construct(Initializer $initializer, CartAutomationService $cartAutomationService)
    {
        Bootstrap::init();
        $initializer->registerServices();

        $this->cartAutomationService = $cartAutomationService;
    }

    /**
     * Process request from CleverReach.
     *
     * @RouteScope(scopes={"api"})
     * @Route(path="/api/v{version}/cleverreach/automationWebhook", name="api.cleverreach.automationWebhook",
     *     defaults={"csrf_protected"=false, "auth_required"=false}, methods={"GET", "POST"})
     * @Route(path="/api/cleverreach/automationWebhook", name="api.cleverreach.automationWebhook.new",
     *     defaults={"csrf_protected"=false, "auth_required"=false}, methods={"GET", "POST"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function webhookHandler(Request $request): Response
    {
        $id = $request->get('crAutomationId');

        if (empty($id)) {
            return new Response('Automation not specified', 400);
        }

        $cart = $this->cartAutomationService->find($id);

        if ($cart === null) {
            return new Response('Automation not found', 400);
        }

        if ($request->getMethod() === 'GET') {
            return $this->register($cart, $request);
        }

        return $this->handle($cart, $request);
    }

    /**
     * @param CartAutomation $cartAutomation
     * @param Request $request
     *
     * @return Response
     */
    private function register(CartAutomation $cartAutomation, Request $request): Response
    {
        $secret = $request->get('secret');

        if (empty($secret)) {
            return new Response('Invalid request.', 400);
        }

        return new Response($cartAutomation->getWebhookVerificationToken() . ' ' . $secret);
    }

    /**
     * @param CartAutomation $cartAutomation
     * @param Request $request
     *
     * @return Response
     */
    private function handle(CartAutomation $cartAutomation, Request $request): Response
    {
        $token = $request->headers->get('x-cr-calltoken');

        if (empty($token) || $token !== $cartAutomation->getWebhookCallToken()) {
            return new Response('Invalid call token.', 400);
        }

        $payload = json_decode($request->getContent(), true);

        if (empty($payload['condition']) || empty($payload['event']) || empty($payload['payload'])) {
            return new Response('Invalid payload.', 400);
        }

        $webhook = new WebHook($payload['condition'], $payload['event'], $payload['payload']);
        $handler = new Handler();

        try {
            $handler->handle($webhook);

            return new Response();
        } catch (Exception $e) {
            Logger::logError('Unable to handle webhook because ' . $e->getMessage());
            return new Response('Unable to handle webhook.', 400);
        }
    }
}
