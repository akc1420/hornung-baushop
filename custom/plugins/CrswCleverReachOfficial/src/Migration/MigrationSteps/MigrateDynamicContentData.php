<?php


namespace Crsw\CleverReachOfficial\Migration\MigrationSteps;


use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\ConfigurationManager;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Crsw\CleverReachOfficial\Migration\Exceptions\FailedToExecuteMigrationStepException;
use Crsw\CleverReachOfficial\Migration\Repository\V2Repository;

/**
 * Class MigrateDynamicContentData
 *
 * @package Crsw\CleverReachOfficial\Migration\MigrationSteps
 */
class MigrateDynamicContentData extends Step
{
    /**
     * Migrate dynamic content data.
     *
     * @throws FailedToExecuteMigrationStepException
     */
    public function execute(): void
    {
        $dynamicContentData = V2Repository::getDynamicContentData();

        if (!$dynamicContentData) {
            return;
        }

        $password = '';
        $contentId = '';

        foreach ($dynamicContentData as $data) {
            if ($data['key'] === 'CLEVERREACH_PRODUCT_SEARCH_PASSWORD') {
                $password = $data['value'];
            }

            if ($data['key'] === 'CLEVERREACH_PRODUCT_SEARCH_CONTENT_ID') {
                $contentId = $data['value'];
            }
        }

        try {
            ConfigurationManager::getInstance()->saveConfigValue(
                'dynamicContentPassword',
                $password
            );

            ConfigurationManager::getInstance()->saveConfigValue(
                'dynamicContentIds',
                json_encode([$contentId])
            );
        } catch (QueryFilterInvalidParamException $e) {
            throw new FailedToExecuteMigrationStepException(
                'Failed to execute MigrateDynamicContentData step because: ' . $e->getMessage()
            );
        }
    }
}