<?php
/**
 * gb media
 * All Rights Reserved.
 *
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * The content of this file is proprietary and confidential.
 *
 * @category       Shopware
 * @package        Shopware_Plugins
 * @subpackage     GbmedForm
 * @copyright      Copyright (c) 2020, gb media
 * @license        proprietary
 * @author         Giuseppe Bottino
 * @link           http://www.gb-media.biz
 */

declare(strict_types=1);

namespace Gbmed\Form\Storefront\Framework\Captcha;

use Gbmed\Form\Framework\Captcha\FormRoutes\FormRoutesInterface;
use Gbmed\Form\Framework\Captcha\FormRoutes\GbmedFormConfigurationFormsEvent;
use Gbmed\Form\Framework\Exception\CaptchaInvalidException;
use Gbmed\Form\Services\ReCaptcha;
use Gbmed\Form\Framework\Captcha\FormRoutes\Collection;
use Shopware\Core\Framework\Adapter\Translation\Translator;
use Shopware\Core\Framework\Routing\KernelListenerPriorities;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CaptchaRouteListener implements EventSubscriberInterface
{
    private Collection $formRoutes;
    private ReCaptcha $service;
    private Translator $translator;
    private SystemConfigService $systemConfigService;
    private EventDispatcherInterface $eventDispatcher;

    /**
     * CaptchaRouteListener constructor.
     * @param ReCaptcha $service
     * @param Collection $formRoutes
     * @param Translator $translator
     * @param SystemConfigService $systemConfigService
     */
    public function __construct(
        ReCaptcha $service,
        Collection $formRoutes,
        Translator $translator,
        SystemConfigService $systemConfigService,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->service = $service;
        $this->formRoutes = $formRoutes;
        $this->translator = $translator;
        $this->systemConfigService = $systemConfigService;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => [
                ['validateCaptcha', KernelListenerPriorities::KERNEL_CONTROLLER_EVENT_SCOPE_VALIDATE],
            ],
        ];
    }

    /**
     * throw CaptchaInvalidException if g-recaptcha-response is not valide
     *
     * @param ControllerEvent $event
     */
    public function validateCaptcha(ControllerEvent $event): void
    {
        $salesChannelContext = $this->getSalesChannelContext($event->getRequest());
        $supports = $this->supports($event->getRequest(), $salesChannelContext);

        if ($supports === false) {
            return;
        }

        if (!$this->service->validate($event->getRequest()->request, $salesChannelContext)) {
            throw new CaptchaInvalidException(
                $this->translator->trans('gbmed-form.contact.exception')
            );
        }
    }

    /**
     * @param Request $request
     * @param SalesChannelContext|null $salesChannelContext
     * @return bool
     */
    private function supports(Request $request, ?SalesChannelContext $salesChannelContext)
    {
        /** @var bool $isRecaptcha */
        $isRecaptcha = $this->service->getSecret($salesChannelContext) && $this->service->getSitekey($salesChannelContext);

        /** @var FormRoutesInterface $route */
        $route = $this->formRoutes->findRoute($request);

        return $isRecaptcha
            && $request->isMethod(Request::METHOD_POST)
            && $route
            && in_array($route->getName(), $this->getConfigForms($salesChannelContext));
    }

    /**
     * helper to get SalesChannelContext
     *
     * @param Request $request
     * @return SalesChannelContext|null
     */
    private function getSalesChannelContext(Request $request): ?SalesChannelContext
    {
        return $request->attributes->get('sw-sales-channel-context');
    }

    private function getConfigForms(?SalesChannelContext $salesChannelContext): array
    {
        /** @var array $config */
        $config = $this->systemConfigService->get(
            'GbmedForm.config',
            $salesChannelContext ? $salesChannelContext->getSalesChannel()->getId() : null
        );

        $event = new GbmedFormConfigurationFormsEvent([], $salesChannelContext);
        $this->eventDispatcher->dispatch($event);

        return array_merge($event->getExtensionForms(), $config['forms']);
    }
}
