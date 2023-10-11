<?php


namespace Crsw\CleverReachOfficial\Components\EventHandlers;

use Crsw\CleverReachOfficial\Core\BusinessLogic\DoubleOptIn\DTO\DoiData;
use Crsw\CleverReachOfficial\Core\BusinessLogic\DoubleOptIn\DTO\DoubleOptInEmail;
use Crsw\CleverReachOfficial\Core\BusinessLogic\DoubleOptIn\Tasks\SendDoubleOptInEmailsTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Contracts\FormService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\Exceptions\FailedToRetriveFormException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Group\Contracts\GroupService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\Contracts\SyncSettingsService;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Service\BusinessLogic\DoubleOptIn\DoubleOptInRecordService;

/**
 * Class DoubleOptInHandler
 *
 * @package Crsw\CleverReachOfficial\Components\EventHandlers
 */
class DoubleOptInHandler extends BaseHandler
{
    /**
     * Enqueues SendDoubleOptInEmailTask.
     *
     * @param string $email
     * @param DoiData $doiData
     * @param string $salesChannelId
     *
     * @throws FailedToRetriveFormException
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     */
    public function sendDoubleOptInEmail(string $email, DoiData $doiData, string $salesChannelId): void
    {
        $doiEmail = new DoubleOptInEmail($this->getFormId($salesChannelId), 'activate', $email, $doiData);
        $this->enqueueTask(new SendDoubleOptInEmailsTask([$doiEmail]));
    }

    /**
     * @param string $salesChannelId
     * @return int|string
     *
     * @throws FailedToRetriveFormException
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     */
    private function getFormId(string $salesChannelId)
    {
        $doiRecord = $this->getDoiRecordService()->findBySalesChannelId($salesChannelId);

        if (!$doiRecord || !$doiRecord->getFormId()) {
            $groupId = $this->getGroupService()->getId();
            $forms = $this->getFormService()->getForms($groupId);

            return $forms[0]->getId();
        }

        return $doiRecord->getFormId();
    }

    /**
     * @return FormService
     */
    private function getFormService(): FormService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(FormService::class);
    }

    /**
     * @return DoubleOptInRecordService
     */
    private function getDoiRecordService(): DoubleOptInRecordService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(DoubleOptInRecordService::class);
    }

    /**
     * @return GroupService
     */
    private function getGroupService(): GroupService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(GroupService::class);
    }

    /**
     * @return SyncSettingsService
     */
    private function getSyncSettingsService(): SyncSettingsService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(SyncSettingsService::class);
    }
}
