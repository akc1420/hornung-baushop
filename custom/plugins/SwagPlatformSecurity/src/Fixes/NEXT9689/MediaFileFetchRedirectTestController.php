<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT9689;

use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MediaFileFetchRedirectTestController extends AbstractController
{
    /**
     * @Route("/api/v{version}/_action/swag-platform-security/redirect-to-echo", name="api.action.swag_platform_security.test.redirect-to-echo", defaults={"auth_required"=false}, methods={"GET"})
     */
    public function redirectAction(Request $request, string $version): RedirectResponse
    {
        $parameters = array_merge($request->query->all(), [
            'version' => $version,
        ]);
        $response = new RedirectResponse($this->generateUrl('api.action.swag_platform_security.test.echo_json', $parameters));
        // only send location header
        $response->setContent('');

        return $response;
    }

    /**
     * @Route("/api/v{version}/_action/swag-platform-security/echo-json", name="api.action.swag_platform_security.test.echo_json", defaults={"auth_required"=false}, methods={"GET"})
     */
    public function echoJsonAction(Request $request): JsonResponse
    {
        $data = [
            'headers' => $request->headers->all(),
            'query' => $request->query->all(),
        ];

        return new JsonResponse($data);
    }
}
