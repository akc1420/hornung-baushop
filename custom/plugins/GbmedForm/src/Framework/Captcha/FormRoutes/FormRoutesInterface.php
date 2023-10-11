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
use Symfony\Component\HttpFoundation\Response;

interface FormRoutesInterface
{
    /**
     * get supported route
     *
     * @param string $route
     * @return FormRoutesInterface|null
     */
    public function support(string $route): ?FormRoutesInterface;

    /**
     * get supported route
     *
     * @param string $name
     * @return FormRoutesInterface|null
     */
    public function supportByName(string $name): ?FormRoutesInterface;

    /**
     * handle supported route
     *
     * @return bool
     * @throws CaptchaInvalidException
     */
    public function handle(): bool;

    /**
     * get exception responde
     *
     * @param CaptchaInvalidException $exception
     * @return Response|null
     */
    public function response(CaptchaInvalidException $exception): ?Response;
}
