<?php


namespace Crsw\CleverReachOfficial\Controller\Admin;

use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\Initializer;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Dashboard\Contracts\DeepLinks;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Contracts\FormService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Exceptions\FailedToRetriveFormException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Contracts\GroupService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Utility\SingleSignOn\SingleSignOnProvider;
use Crsw\CleverReachOfficial\Core\Infrastructure\Exceptions\BaseException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Automation\AutomationService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\DoubleOptIn\DoubleOptInRecordService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\DoubleOptIn\Entities\DoubleOptInRecord;
use Shopware\Core\Framework\Api\Response\JsonApiResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AbandonedCartTheaStatusController
 *
 * @package Crsw\CleverReachOfficial\Controller\Admin
 */
class AbandonedCartTheaStatusController extends AbstractController
{
    /**
     * @var AutomationService
     */
    private $automationService;
    /**
     * @var FormService
     */
    private $formService;
    /**
     * @var GroupService
     */
    private $groupService;

    /**
     * AbandonedCartTheaStatusController constructor.
     *
     * @param Initializer $initializer
     * @param AutomationService $automationService
     * @param FormService $formService
     * @param GroupService $groupService
     */
    public function __construct(
        Initializer $initializer,
        AutomationService $automationService,
        FormService $formService,
        GroupService $groupService
    ) {
        Bootstrap::init();
        $initializer->registerServices();
        $this->automationService = $automationService;
        $this->formService = $formService;
        $this->groupService = $groupService;
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/abandonedcart/theastatus/index",
     *     name="api.cleverreach.abandonedcart.theastatus.index", methods={"GET", "POST"})
     * @Route(path="api/cleverreach/abandonedcart/theastatus/index",
     *     name="api.cleverreach.abandonedcart.theastatus.index.new", methods={"GET", "POST"})
     *
     * @param Request $request
     *
     * @return JsonApiResponse
     */
    public function index(Request $request): JsonApiResponse
    {
        $shopId = $request->get('shopId');

        try {
            $cart = $this->automationService->get($shopId);

            $result = $cart ? $cart->isActive() : false;

            if ($result) {
                $this->enableDoi($shopId);
            }

            return new JsonApiResponse(['status' => $result]);
        } catch (BaseException $e) {
            Logger::logError($e->getMessage());
            return new JsonApiResponse(['status' => false]);
        }
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/abandonedcart/theastatus/url",
     *     name="api.cleverreach.abandonedcart.theastatus.url", methods={"GET", "POST"})
     * @Route(path="api/cleverreach/abandonedcart/theastatus/url",
     *     name="api.cleverreach.abandonedcart.theastatus.url.new", methods={"GET", "POST"})
     *
     * @param Request $request
     * @return JsonApiResponse
     */
    public function getAbandonedCartUrl(Request $request): JsonApiResponse
    {
        $shopId = $request->get('shopId');

        try {
            $shop = $this->automationService->get($shopId);

            if (!$shop) {
                return new JsonApiResponse([
                    'url' => SingleSignOnProvider::getUrl(DeepLinks::CLEVERREACH_AUTOMATION_URL)
                ]);
            }


            return new JsonApiResponse([
                'url' =>
                    SingleSignOnProvider::getUrl(DeepLinks::CLEVERREACH_EDIT_AUTOMATION_URL . $shop->getCondition())
                ]);
        } catch (BaseException $e) {
            Logger::logError($e->getMessage());
        }

        return new JsonApiResponse();
    }

    /**
     * @param $shopId
     *
     * @throws RepositoryNotRegisteredException
     * @throws QueryFilterInvalidParamException
     */
    private function enableDoi($shopId): void
    {
        $doiRecord = $this->getDoiRecordService()->findBySalesChannelId($shopId);

        if ($doiRecord) {
            return;
        }

        $record = new DoubleOptInRecord();
        $record->setSalesChannelId($shopId);
        $record->setStatus(true);
        $record->setFormId($this->getDefaultForm());

        $this->getDoiRecordService()->createOrUpdate($record);
    }

    /**
     * @return object | DoubleOptInRecordService
     */
    private function getDoiRecordService()
    {
        return ServiceRegister::getService(DoubleOptInRecordService::class);
    }

    private function getDefaultForm(): string
    {
        $groupId = $this->groupService->getId();
        try {
            $forms = $this->formService->getForms($groupId);
        } catch (FailedToRetriveFormException $e) {
            Logger::logError("Failed to retrieve forms because {$e->getMessage()}");
            return '';
        }

        foreach ($forms as $form) {
            if ($form->getName() === 'Shopware 6') {
                return $form->getId();
            }
        }

        return $forms[0] ? $forms[0]->getId() : '';
    }
}
