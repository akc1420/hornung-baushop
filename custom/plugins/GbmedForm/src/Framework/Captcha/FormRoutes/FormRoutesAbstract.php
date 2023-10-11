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

use Psr\Container\ContainerInterface;
use Shopware\Core\Framework\Routing\RequestTransformerInterface;
use Shopware\Storefront\Framework\Routing\Router;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

abstract class FormRoutesAbstract implements FormRoutesInterface
{
    /**
     * @var ContainerInterface
     */
    public $container;

    /**
     * FormRoutesAbstract constructor.
     * @param ContainerInterface $container
     */
    public function __construct(
        ContainerInterface $container
    ) {
        $this->container = $container;
    }

    /**
     * @param string $route
     * @return FormRoutesInterface|null
     */
    public function support(string $route): ?FormRoutesInterface
    {
        return $route === static::ROUTE ? $this : null;
    }

    /**
     * @param string $name
     * @return FormRoutesInterface|null
     */
    public function supportByName(string $name): ?FormRoutesInterface
    {
        return $name === static::NAME ? $this : null;
    }

    public function getRoute()
    {
        return static::ROUTE;
    }

    public function getName()
    {
        return static::NAME;
    }

    /**
     * Returns a rendered view.
     *
     * @final
     * @param string $view
     * @param array $parameters
     * @return string
     */
    protected function renderView(string $view, array $parameters = []): string
    {
        return $this->container->get('twig')->render($view, $parameters);
    }

    /**
     * @param string $routeName
     * @param array $attributes
     * @param array $routeParameters
     * @return Response
     */
    protected function forwardToRoute(string $routeName, array $attributes = [], array $routeParameters = []): Response
    {
        $router = $this->container->get('router');

        $url = $this->generateUrl($routeName, $routeParameters, Router::PATH_INFO);

        // for the route matching the request method is set to "GET" because
        // this method is not ought to be used as a post passthrough
        // rather it shall return templates or redirects to display results of the request ahead
        $method = $router->getContext()->getMethod();
        $router->getContext()->setMethod(Request::METHOD_GET);

        $route = $router->match($url);
        $router->getContext()->setMethod($method);

        $request = $this->container->get('request_stack')->getCurrentRequest();

        $attributes = array_merge(
            $this->container->get(RequestTransformerInterface::class)->extractInheritableAttributes($request),
            $route,
            $attributes,
            ['_route_params' => $routeParameters]
        );

        return $this->forward($route['_controller'], $attributes, $routeParameters);
    }

    /**
     * Forwards the request to another controller.
     *
     * @param string $controller The controller name (a string like Bundle\BlogBundle\Controller\PostController::indexAction)
     * @param array $path
     * @param array $query
     * @return Response
     */
    protected function forward(string $controller, array $path = [], array $query = []): Response
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $path['_controller'] = $controller;
        $subRequest = $request->duplicate($query, null, $path);

        return $this->container->get('http_kernel')->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }

    /**
     * Returns a RedirectResponse to the given URL.
     *
     * @final
     * @param string $url
     * @param int $status
     * @return RedirectResponse
     */
    protected function redirect(string $url, int $status = 302): RedirectResponse
    {
        return new RedirectResponse($url, $status);
    }

    /**
     * Returns a RedirectResponse to the given route with the given parameters.
     *
     * @final
     * @param string $route
     * @param array $parameters
     * @param int $status
     * @return RedirectResponse
     */
    protected function redirectToRoute(string $route, array $parameters = [], int $status = 302): RedirectResponse
    {
        return $this->redirect($this->generateUrl($route, $parameters), $status);
    }

    /**
     * Generates a URL from the given parameters.
     *
     * @param string $route
     * @param array $parameters
     * @param int $referenceType
     * @return string
     * @see UrlGeneratorInterface
     *
     * @final
     */
    protected function generateUrl(
        string $route,
        array $parameters = [],
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        return $this->container->get('router')->generate($route, $parameters, $referenceType);
    }
}
