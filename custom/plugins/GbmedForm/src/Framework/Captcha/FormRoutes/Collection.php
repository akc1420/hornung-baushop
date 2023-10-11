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

use Symfony\Component\HttpFoundation\Request;

class Collection
{
    /**
     * @var iterable
     */
    private $routes;

    /**
     * CaptchaRouteListener constructor.
     * @param iterable $routes
     */
    public function __construct(iterable $routes = [])
    {
        $this->routes = $routes;
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function support(Request $request): bool
    {
        $result = false;
        /** @var FormRoutesInterface $route */
        if ($request->attributes->has('_route')
            && $route = $this->findRoute($request)
        ) {
            $result = $route->handle();
        }

        return $result;
    }

    /**
     * find supported rout
     * @param Request $request
     * @return FormRoutesInterface|null
     */
    public function findRoute(Request $request): ?FormRoutesInterface
    {
        if ($request->attributes->has('_route')) {
            /** @var FormRoutesInterface $route */
            foreach ($this->routes as $route) {
                if ($route->support($request->attributes->get('_route'))) {
                    return $route;
                }
            }
        }

        return null;
    }

    /**
     * find supported rout
     * @param string|null $name
     * @return FormRoutesInterface|null
     */
    public function findRouteByName(?string $name): ?FormRoutesInterface
    {
        if ($name) {
            /** @var FormRoutesInterface $route */
            foreach ($this->routes as $route) {
                if ($route->supportByName($name)) {
                    return $route;
                }
            }
        }

        return null;
    }
}
