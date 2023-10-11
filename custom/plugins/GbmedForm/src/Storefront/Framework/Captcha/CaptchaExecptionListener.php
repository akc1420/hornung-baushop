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

use Gbmed\Form\Framework\Exception\CaptchaInvalidException;
use Gbmed\Form\Framework\Captcha\FormRoutes\Collection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

class CaptchaExecptionListener implements EventSubscriberInterface
{
    /**
     * @var Collection
     */
    private $formRoutes;

    /**
     * CaptchaRouteListener constructor.
     * @param Collection $formRoutes
     * @param Environment $twig
     */
    public function __construct(Collection $formRoutes)
    {
        $this->formRoutes = $formRoutes;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => [
                ['exceptionValidateCaptcha', 10]
            ],
        ];
    }

    /**
     * handle CaptchaInvalidException
     *
     * @param ExceptionEvent $event
     * @throws \Exception
     */
    public function exceptionValidateCaptcha(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if(($exception instanceof CaptchaInvalidException)){
            try {
                $response = $this->getResponse($event->getRequest(), $exception);
                if(($response instanceof Response)){
                    $event->setResponse($response);
                }
            } catch (\Exception $e) {
                throw $e;
            }
        }
    }

    /**
     * @param Request $request
     * @param CaptchaInvalidException $exception
     * @return Response|null
     */
    private function getResponse(Request $request, CaptchaInvalidException $exception): ?Response
    {
        if($route = $this->formRoutes->findRoute($request)){
            return $route->response($exception);
        }

        return null;
    }
}
