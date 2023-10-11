<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Components;


class BlacklistedEmailsResolver extends ReceiverSyncSubTask
{
    const CLASS_NAME = __CLASS__;

    /**
     * Resolves blacklisted emails.
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRefreshAccessToken
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Exceptions\FailedToRetrieveAuthInfoException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Crsw\CleverReachOfficial\Core\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function execute()
    {
        $context = $this->getExecutionContext();
        $context->blacklistedEmails = $this->removeSuffix($this->getReceiverProxy()->getBlacklisted());

        $this->reportProgress(100);
    }

    /**
     * Removes suffix from blacklisted emails
     *
     * @param array $emails
     *
     * @return array|string[]
     */
    protected function removeSuffix(array $emails)
    {
        $suffix = $this->getGroupService()->getBlacklistedEmailsSuffix();

        return array_map(function ($email) use($suffix) {return rtrim($email, $suffix);}, $emails);
    }
}
