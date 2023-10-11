<?php declare(strict_types=1);

namespace Ott\IdealoConnector\Controller\Api;

use Ott\IdealoConnector\Service\ClientService;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"administration"})
 */
class ApiTestController extends AbstractController
{
    private ClientService $clientService;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }

    /**
     * @Route(path="/api/_action/ott-idealo-api-test/verify")
     */
    public function check(Request $request): JsonResponse
    {
        $clientId     = $request->get('clientId');
        $clientSecret = $request->get('clientSecret');
        $isSandbox    = (bool) $request->get('isSandbox');

        $success = false;
        if (!empty($clientId) && !empty($clientSecret)) {
            if ($this->clientService->getAccessToken([
                'clientId'     => $clientId,
                'clientSecret' => $clientSecret,
                'isSandbox'    => $isSandbox
            ])) {
                $success = true;
            }
        }

        return new JsonResponse(['success' => $success]);
    }
}
