<?php


namespace Crsw\CleverReachOfficial\Controller\Admin;

use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\Initializer;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Contracts\FormCacheService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Entities\Form;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Exceptions\FailedToRetrieveFormCacheException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Contracts\GroupService;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\RepositoryRegistry;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Entity\Form\Repositories\FormRepository;
use Shopware\Core\Framework\Api\Response\JsonApiResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\PlatformRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class FormsController
 *
 * @package Crsw\CleverReachOfficial\Controller\Admin
 */
class FormsController extends AbstractController
{
    /**
     * @var GroupService
     */
    private $groupService;
    /**
     * @var FormCacheService
     */
    private $formService;
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * FormsController constructor.
     *
     * @param GroupService $groupService
     * @param FormCacheService $formService
     * @param Initializer $initializer
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function __construct(
        GroupService          $groupService,
        FormCacheService      $formService,
        Initializer           $initializer,
        UrlGeneratorInterface $urlGenerator
    )
    {
        Bootstrap::init();
        $initializer->registerServices();
        $this->groupService = $groupService;
        $this->formService = $formService;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Gets forms.
     *
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/forms/get", name="api.cleverreach.forms.get", methods={"GET", "POST"})
     * @Route(path="api/cleverreach/forms/get", name="api.cleverreach.forms.get.new", methods={"GET", "POST"})
     *
     * @return JsonApiResponse
     */
    public function getForms(): JsonApiResponse
    {
        $data = [];

        try {
            $forms = $this->formService->getForms();
            $groupName = $this->groupService->getName();

            foreach ($forms as $key => $form) {
                $data[$key]['formName'] = $form->getName();
                $data[$key]['formId'] = $form->getApiId();
                $data[$key]['listName'] = $groupName;
            }

            return new JsonApiResponse(['formsData' => $data]);
        } catch (FailedToRetrieveFormCacheException $e) {
            Logger::logError($e->getMessage());
            return new JsonApiResponse(['success' => false]);
        }
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/forms/getDefault",
     *     name="api.cleverreach.forms.getDefault", methods={"GET", "POST"})
     * @Route(path="api/cleverreach/forms/getDefault",
     *     name="api.cleverreach.forms.getDefault.new", methods={"GET", "POST"})
     *
     * @return JsonApiResponse
     */
    public function getDefaultForm(): JsonApiResponse
    {
        try {
            /** @var FormRepository $formsRepository */
            $formsRepository = RepositoryRegistry::getRepository(Form::class);

            return new JsonApiResponse([
                'defaultForm' => $formsRepository->getDefaultForm(),
                'formPath' => $this->getFormPath()
            ]);
        } catch (RepositoryNotRegisteredException $e) {
            Logger::logError('Failed to retrieve default form because: ' . $e->getMessage());
            return new JsonApiResponse(['error' => 'Failed to retrieve default form']);
        }
    }

    /**
     * @return string
     */
    private function getFormPath(): string
    {
        $parameterBag = ServiceRegister::getService(ParameterBagInterface::class);
        $routeName = 'api.cleverreach.crForms.new';
        $params = [];
        if (version_compare($parameterBag->get('kernel.shopware_version'), '6.4.0', 'lt')) {
            $routeName = 'api.cleverreach.crForms';
            $params['version'] = PlatformRequest::API_VERSION;
        }

        return $this->urlGenerator->generate(
            $routeName,
            $params,
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }
}
