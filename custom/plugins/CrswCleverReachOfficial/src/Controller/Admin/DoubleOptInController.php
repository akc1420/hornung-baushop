<?php


namespace Crsw\CleverReachOfficial\Controller\Admin;

use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\Initializer;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Http\UserProxy;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Contracts\FormService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Exceptions\FailedToRetriveFormException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Contracts\GroupService;
use Crsw\CleverReachOfficial\Core\Infrastructure\Exceptions\BaseException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Service\BusinessLogic\DoubleOptIn\DoubleOptInRecordService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\DoubleOptIn\Entities\DoubleOptInRecord;
use Shopware\Core\Framework\Api\Response\JsonApiResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class DoubleOptInController
 *
 * @package Crsw\CleverReachOfficial\Controller\Admin
 */
class DoubleOptInController extends AbstractController
{
    /**
     * @var FormService
     */
    private $formsService;
    /**
     * @var DoubleOptInRecordService
     */
    private $doiRecordService;
    /**
     * @var GroupService
     */
    private $groupService;
    /**
     * @var UserProxy
     */
    private $userProxy;

    /**
     * DoubleOptInController constructor.
     *
     * @param Initializer $initializer
     * @param FormService $formsService
     * @param DoubleOptInRecordService $doiRecordService
     * @param GroupService $groupService
     * @param UserProxy $userProxy
     */
    public function __construct(
        Initializer $initializer,
        FormService $formsService,
        DoubleOptInRecordService $doiRecordService,
        GroupService $groupService,
        UserProxy $userProxy
    ) {
        Bootstrap::init();
        $initializer->registerServices();
        $this->formsService = $formsService;
        $this->doiRecordService = $doiRecordService;
        $this->groupService = $groupService;
        $this->userProxy = $userProxy;
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/doi/status", name="api.cleverreach.doi.status", methods={"GET", "POST"})
     * @Route(path="api/cleverreach/doi/status", name="api.cleverreach.doi.status.new", methods={"GET", "POST"})
     *
     * @param Request $request
     *
     * @return JsonApiResponse
     */
    public function getDoiStatus(Request $request): JsonApiResponse
    {
        if (!$this->isUserDataComplete()) {
            return new JsonApiResponse(['userError' => true]);
        }

        $salesChannelId = $request->get('shopId');
        $data = [
            'status' => false,
            'chosenForm' => ''
        ];

        try {
            $doiRecord = $this->doiRecordService->findBySalesChannelId($salesChannelId);

            if ($doiRecord) {
                $selectedForm = $doiRecord->getFormId() ? $this->formsService->getForm($doiRecord->getFormId()) : '';
                $data = [
                    'status' => $doiRecord->isStatus(),
                    'selectedForm' => [
                        'id' => $selectedForm ? $selectedForm->getId() : '',
                        'name' => $selectedForm ? $selectedForm->getName() : ''
                    ]
                ];
            }
        } catch (BaseException $e) {
            Logger::logError('Failed to fetch double opt in data because: ' . $e->getMessage(), 'Integration');
        }

        return new JsonApiResponse($data);
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/doi/forms", name="api.cleverreach.doi.forms", methods={"GET", "POST"})
     * @Route(path="api/cleverreach/doi/forms", name="api.cleverreach.doi.forms.new", methods={"GET", "POST"})
     *
     * @return JsonApiResponse
     */
    public function getForms(): JsonApiResponse
    {
        $forms = [];

        try {
            $crForms = $this->formsService->getForms($this->groupService->getId());

            foreach ($crForms as $form) {
                $forms[] = [
                    'id' => $form->getId(),
                    'name' => $form->getName()
                ];
            }
        } catch (FailedToRetriveFormException $e) {
            Logger::logError('Failed to retrieve forms because: ' . $e->getMessage(), 'Integration');
        }

        return new JsonApiResponse(['forms' => $forms]);
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/doi/enable", name="api.cleverreach.doi.enable", methods={"GET", "POST"})
     * @Route(path="api/cleverreach/doi/enable", name="api.cleverreach.doi.enable.new", methods={"GET", "POST"})
     *
     * @param Request $request
     *
     * @return JsonApiResponse
     */
    public function enable(Request $request): JsonApiResponse
    {
        $salesChannelId = $request->get('shopId');

        try {
            $record = new DoubleOptInRecord();
            $record->setSalesChannelId($salesChannelId);
            $record->setStatus(true);

            $this->doiRecordService->createOrUpdate($record);

            return new JsonApiResponse(['success' => true]);
        } catch (BaseException $e) {
            Logger::logError('Failed to enable double opt in because: ' . $e->getMessage(), 'Integration');
            return new JsonApiResponse(['success' => false]);
        }
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/doi/disable",
     *      name="api.cleverreach.doi.disable", methods={"GET", "POST"})
     * @Route(path="api/cleverreach/doi/disable",
     *      name="api.cleverreach.doi.disable.new", methods={"GET", "POST"})
     *
     * @param Request $request
     *
     * @return JsonApiResponse
     */
    public function disable(Request $request): JsonApiResponse
    {
        $salesChannelId = $request->get('shopId');

        try {
            $record = new DoubleOptInRecord();
            $record->setSalesChannelId($salesChannelId);
            $record->setStatus(false);

            $this->doiRecordService->createOrUpdate($record);

            return new JsonApiResponse(['success' => true]);
        } catch (BaseException $e) {
            Logger::logError('Failed to disable double opt in because: ' . $e->getMessage(), 'Integration');
            return new JsonApiResponse(['success' => false]);
        }
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/doi/chooseform",
     *     name="api.cleverreach.doi.chooseform", methods={"GET", "POST"})
     * @Route(path="api/cleverreach/doi/chooseform",
     *     name="api.cleverreach.doi.chooseform.new", methods={"GET", "POST"})
     *
     * @param Request $request
     *
     * @return JsonApiResponse
     */
    public function chooseDoiForm(Request $request): JsonApiResponse
    {
        $salesChannelId = $request->get('shopId');
        $formId = $request->get('formId');

        $record = new DoubleOptInRecord();
        $record->setSalesChannelId($salesChannelId);
        $record->setStatus(true);
        $record->setFormId($formId);

        try {
            $this->doiRecordService->createOrUpdate($record);

            return new JsonApiResponse(['success' => true]);
        } catch (BaseException $e) {
            Logger::logError('Failed to save chosen double opt in form because: ' . $e->getMessage(), 'Integration');
            return new JsonApiResponse(['success' => false]);
        }
    }

    /**
     * Checks if user data is complete.
     *
     * @return bool
     */
    private function isUserDataComplete(): bool
    {
        try {
            $userInfo = $this->userProxy->getUserInfo();
        } catch (BaseException $e) {
            Logger::logError($e->getMessage(), 'Integration');
            return false;
        }

        return $userInfo->getFirstName() && $userInfo->getLastName() && $userInfo->getStreet() && $userInfo->getZip()
            && $userInfo->getCompany() && $userInfo->getCity() && $userInfo->getPhone() && $userInfo->getCountry();
    }
}
