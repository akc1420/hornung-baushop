<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\Tasks;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\Contracts\DynamicContentService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\DynamicContent;
use Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\Exceptions\ContentWithSameNameExistsException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\Http\Proxy;
use Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Core\Infrastructure\TaskExecution\Task;

/**
 * Class RegisterDynamicContentTask
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent
 */
class RegisterDynamicContentTask extends Task
{
    const CLASS_NAME = __CLASS__;

    const INITIAL_PROGRESS_PERCENT = 10;

    /**
     * @var DynamicContentService
     */
    protected $dynamicContentService;
    /**
     * @var Proxy
     */
    protected $dynamicContentProxy;

    /**
     * Runs task logic
     *
     * @throws FailedToRefreshAccessToken
     * @throws FailedToRetrieveAuthInfoException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws QueryFilterInvalidParamException
     */
    public function execute()
    {
        $this->reportProgress(static::INITIAL_PROGRESS_PERCENT);
        $supportedContents = $this->getDynamicContentService()->getSupportedDynamicContent();

        if (!empty($supportedContents)) {
            $currentProgress = self::INITIAL_PROGRESS_PERCENT;
            $progressStep = (int)((100 - self::INITIAL_PROGRESS_PERCENT) / count($supportedContents));
            foreach ($supportedContents as $supportedContent) {
                if ($createdContent = $this->registerDynamicContent($supportedContent)) {
                    $this->getDynamicContentService()->addCreatedDynamicContentId($createdContent->getId());
                }

                $currentProgress += $progressStep;
                $this->reportProgress($currentProgress);
            }
        }


        $this->reportProgress(100);
    }

    /**
     * @return DynamicContentService
     */
    protected function getDynamicContentService()
    {
        if ($this->dynamicContentService === null) {
            $this->dynamicContentService = ServiceRegister::getService(DynamicContentService::CLASS_NAME);
        }

        return $this->dynamicContentService;
    }

    /**
     * @return Proxy
     */
    protected function getDynamicContentProxy()
    {
        if ($this->dynamicContentProxy === null) {
            $this->dynamicContentProxy = ServiceRegister::getService(Proxy::CLASS_NAME);
        }

        return $this->dynamicContentProxy;
    }

    /**
     * Creates new dynamic content, if content with the same name or url already registered, updates existing one
     *
     * @param DynamicContent $content
     *
     * @return DynamicContent
     *
     * @throws FailedToRefreshAccessToken
     * @throws FailedToRetrieveAuthInfoException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws \Exception
     */
    protected function registerDynamicContent(DynamicContent $content)
    {
        try {
            return $this->getDynamicContentProxy()->create($content);
        } catch (ContentWithSameNameExistsException $exception) {
            Logger::logInfo("Dynamic content already created: {$exception->getMessage()}", 'Core');
            $existingContents = $this->getDynamicContentProxy()->fetchAll();

            $this->reportAlive();

            foreach ($existingContents as $existingContent) {
                if ($this->contentsMatch($content, $existingContent)) {
                    return $this->getDynamicContentProxy()->update($existingContent->getId(), $content);
                }
            }
        }

        return null;
    }

    /**
     * Checks if the names or the urls are the same
     *
     * @param DynamicContent $newContent
     * @param DynamicContent $existingContent
     *
     * @return bool
     */
    protected function contentsMatch(DynamicContent $newContent, DynamicContent $existingContent)
    {
        return $existingContent->getUrl() === $newContent->getUrl() ||
            $existingContent->getName() === $newContent->getName();
    }
}
