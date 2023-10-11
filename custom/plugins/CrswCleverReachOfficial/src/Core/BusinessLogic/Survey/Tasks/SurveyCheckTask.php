<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Survey\Tasks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Language\Contracts\TranslationService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Notification\Contracts\NotificationService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Notification\DTO\Notification;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Scheduler\ScheduledTask;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Survey\Contracts\SurveyService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Survey\Contracts\SurveyStorageService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Survey\Contracts\SurveyType;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Survey\DTO\PollData;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

/**
 * Class SurveyCheckTask
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Survey\Tasks
 */
class SurveyCheckTask extends ScheduledTask
{
    /**
     * @var SurveyService
     */
    protected $surveyService;
    /**
     * @var SurveyStorageService
     */
    protected $surveyStorageService;

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function execute()
    {
        $this->reportProgress(10);
        /** @var TranslationService $translationService */
        $translationService = ServiceRegister::getService(TranslationService::CLASS_NAME);
        $surveyService = ServiceRegister::getService(SurveyService::CLASS_NAME);
        $survey = $surveyService->getSurvey(SurveyType::PERIODIC, $translationService->getSystemLanguage());

        if ($survey && $survey->getMeta()->getId() !== $this->getSurveyStorageService()->getLastPollId()) {
            $this->reportProgress(40);

            /** @var NotificationService $notificationService */
            $notificationService = ServiceRegister::getService(NotificationService::CLASS_NAME);
            $notificationService->push($this->createNotification($survey->getMeta()));

            $this->reportProgress(60);
        }

        $this->reportProgress(100);
    }

    /**
     * Creates notification
     *
     * @param PollData $pollData
     *
     * @return Notification
     * @throws \Exception
     */
    protected function createNotification(PollData $pollData)
    {
        $notification = new Notification($pollData->getId(), $pollData->getName());
        $notification->setDescription($this->getSurveyStorageService()->getDefaultMessage());
        $notification->setUrl($this->getSurveyStorageService()->getPopUpUrl());
        $notification->setDate(new \DateTime());

        return $notification;
    }

    /**
     * @return SurveyStorageService
     */
    protected function getSurveyStorageService()
    {
        if ($this->surveyStorageService === null) {
            $this->surveyStorageService = ServiceRegister::getService(SurveyStorageService::CLASS_NAME);
        }

        return $this->surveyStorageService;
    }
}