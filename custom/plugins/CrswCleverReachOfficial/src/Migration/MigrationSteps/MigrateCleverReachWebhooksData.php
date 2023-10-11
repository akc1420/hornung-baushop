<?php


namespace Crsw\CleverReachOfficial\Migration\MigrationSteps;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Form\FormEventsService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\ReceiverEventsService;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Migration\Repository\V2Repository;

/**
 * Class MigrateCleverReachWebhooksData
 *
 * @package Crsw\CleverReachOfficial\Migration\MigrationSteps
 */
class MigrateCleverReachWebhooksData extends Step
{
    /**
     * Migrate CleverReach webhooks data.
     */
    public function execute(): void
    {
        $webhooksData = V2Repository::getWebHooksData();

        if (!$webhooksData) {
            return;
        }

        $callToken = '';
        $formsCallToken = '';
        $verificationToken = '';

        foreach ($webhooksData as $data) {
            if ($data['key'] === 'CLEVERREACH_EVENT_CALL_TOKEN') {
                $callToken = $data['value'];
            }

            if ($data['key'] === 'CLEVERREACH_EVENT_VERIFICATION_TOKEN') {
                $verificationToken = $data['value'];
            }

            if ($data['key'] === 'CLEVERREACH_FORM_EVENT_CALL_TOKEN') {
                $formsCallToken = $data['value'];
            }
        }

        $this->getReceiverEventsService()->setCallToken($callToken);
        $this->getReceiverEventsService()->setVerificationToken($verificationToken);
        $this->getFormEventsService()->setCallToken($formsCallToken);
    }

    /**
     * @return ReceiverEventsService
     */
    private function getReceiverEventsService(): ReceiverEventsService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(ReceiverEventsService::CLASS_NAME);
    }

    /**
     * @return FormEventsService
     */
    private function getFormEventsService(): FormEventsService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(FormEventsService::CLASS_NAME);
    }
}