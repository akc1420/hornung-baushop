<?php
/**
 * Example plugin to extend Shopware 6 plugin GbmedForm
 *
 * @category       Shopware
 * @package        Shopware_Plugins
 * @subpackage     GbmedFormNewsletter
 * @copyright      Copyright (c) 2020, gb media
 */

declare(strict_types=1);

namespace Gbmed\FormNewsletter\Framework\Captcha\FormRoutes;

use Gbmed\Form\Framework\Exception\CaptchaInvalidException;
use Gbmed\Form\Framework\Captcha\FormRoutes\FormRoutesAbstract;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class Newsletter extends FormRoutesAbstract
{
    public const NAME = 'newsletter';
    public const ROUTE = 'frontend.form.newsletter.register.handle';

    /**
     * handle your functionality here and return true to start recaptcha request validation.
     * throw CaptchaInvalidException to display error message which is handled by self::response
     *
     * @return bool
     * @throws CaptchaInvalidException
     */
    public function handle(): bool
    {
        /*
        // example
        $value = 2;
        if($value !== 1){
            throw new CaptchaInvalidException('value is not 1');
        }
        */

        return true;
    }

    /**
     * return response on invalid captcha exception
     *
     * @param CaptchaInvalidException $exception
     * @return JsonResponse
     */
    public function response(CaptchaInvalidException $exception): ?Response
    {
        return new JsonResponse([
            [
                'type' => 'danger',
                'alert' => $this->renderView('@Storefront/storefront/utilities/alert.html.twig', [
                    'type' => 'danger',
                    'list' => [
                        $exception->getMessage()
                    ],
                ]),
            ]
        ]);
    }
}
