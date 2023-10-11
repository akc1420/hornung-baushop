<?php


namespace Crsw\CleverReachOfficial\Controller\Storefront;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Exceptions\FailedToRetriveFormException;
use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\Initializer;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Entities\Form;
use Crsw\CleverReachOfficial\Core\Infrastructure\Exceptions\BaseException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\RepositoryRegistry;
use Crsw\CleverReachOfficial\Entity\Form\Repositories\FormRepository;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class CrFormsController
 *
 * @package Crsw\CleverReachOfficial\Controller\Storefront
 */
class CrFormsController extends AbstractController
{
    /**
     * CrFormsController constructor.
     *
     * @param Initializer $initializer
     */
    public function __construct(Initializer $initializer)
    {
        Bootstrap::init();
        $initializer->registerServices();
    }

	/**
	 * @RouteScope(scopes={"api"})
	 * @Route(path="/api/v{version}/cleverreach/crForms", name="api.cleverreach.crForms",
	 *     defaults={"csrf_protected"=false, "XmlHttpRequest"=true, "auth_required"=false}, methods={"GET", "POST"})
	 * @Route(path="/api/cleverreach/crForms", name="api.cleverreach.crForms.new",
	 *     defaults={"csrf_protected"=false, "XmlHttpRequest"=true, "auth_required"=false}, methods={"GET", "POST"})
	 *
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
    public function form(Request $request): JsonResponse
    {
        $shopwareId = $request->get('formId');
        try {
            /** @var FormRepository $repository */
            $repository = RepositoryRegistry::getRepository(Form::class);

            $data = $repository->getByShopwareId($shopwareId);

            return new JsonResponse(['content' => html_entity_decode($data)]);
        } catch (BaseException | FailedToRetriveFormException $e) {
            Logger::logError($e->getMessage());
            return new JsonResponse();
        }
    }
}
