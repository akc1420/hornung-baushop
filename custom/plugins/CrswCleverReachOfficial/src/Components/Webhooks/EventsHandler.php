<?php

namespace Crsw\CleverReachOfficial\Components\Webhooks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\WebHooks\Handler as FormsHandler;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\WebHooks\Handler as ReceiverHandler;
use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Contracts\EventsService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\DTO\WebHook;
use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Exceptions\UnableToHandleWebHookException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class EventsHandler
 *
 * @package Crsw\CleverReachOfficial\Components\Crsw\CleverReachOfficial\Core\Webhooks
 */
class EventsHandler
{
    /**
     * @var EventsService
     */
    private $eventsService;
    /**
     * @var FormsHandler | ReceiverHandler
     */
    private $handler;

    /**
     * EventsHandler constructor.
     *
     * @param EventsService $eventsService
     * @param FormsHandler|ReceiverHandler $handler
     */
    public function __construct(EventsService $eventsService, $handler)
    {
        $this->eventsService = $eventsService;
        $this->handler = $handler;
    }

    /**
     * Handles request from CleverReach.
     *
     * @param Request $request
     *
     * @return array
     */
    public function handleRequest(Request $request): array
    {
        if ($request->getMethod() === 'GET') {
            $response = $this->register($request);
        } else {
            $response['httpCode'] = $this->handle($request);
        }

        return $response;
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    private function register(Request $request): array
    {
        $secret = $request->get('secret');

        if ($secret === null) {
            $response['httpCode'] = 400;
        } else {
            $response['verificationToken'] = $this->eventsService->getVerificationToken() . ' ' . $secret;
            $response['httpCode'] = 200;
        }

        return $response;
    }

    /**
     * @param Request $request
     *
     * @return int
     */
    private function handle(Request $request): int
    {
        if (($token = $request->headers->get('x-cr-calltoken')) === null
            || $this->eventsService->getCallToken() !== $token) {
            return 401;
        }

        $requestBody = json_decode($request->getContent(), true);

        if (!$this->validate($requestBody)) {
            return 400;
        }

        $webHook = new WebHook($requestBody['condition'], $requestBody['event'], $requestBody['payload']);

        try {
            $this->handler->handle($webHook);
        } catch (UnableToHandleWebHookException $e) {
            Logger::logError($e->getMessage(), 'Integration');
        }

        return 200;
    }

    /**
     * @param array $requestBody
     *
     * @return bool
     */
    private function validate(array $requestBody): bool
    {
        return !empty($requestBody['payload'])
            && !empty($requestBody['event'])
            && !empty($requestBody['condition']);
    }
}
