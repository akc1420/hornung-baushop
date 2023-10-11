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

namespace Gbmed\Form\Framework\Captcha\FormRoutes;

use Gbmed\Form\Framework\Exception\CaptchaInvalidException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CmsExtensions extends FormRoutesAbstract
{
    const NAME = 'cms-extensions';
    const ROUTE = 'frontend.swag.cms-extensions.form.send';

    /**
     * @return bool
     */
    public function handle(): bool
    {
        return true;
    }

    /**
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
