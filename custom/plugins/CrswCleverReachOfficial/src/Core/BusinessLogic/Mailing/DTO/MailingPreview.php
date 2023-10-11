<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Mailing\DTO;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

/**
 * Class MailingPreview
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Mailing\DTO
 */
class MailingPreview extends DataTransferObject
{
    /**
     * @var array
     */
    protected $receivers;
    /**
     * @var string
     */
    protected $previewText;

    /**
     * MailingPreview constructor.
     *
     * @param array $receivers
     * @param string $previewText
     */
    public function __construct(array $receivers, $previewText = '')
    {
        $this->receivers = $receivers;
        $this->previewText = $previewText;
    }

    /**
     * @return array
     */
    public function getReceivers()
    {
        return $this->receivers;
    }

    /**
     * @return string
     */
    public function getPreviewText()
    {
        return $this->previewText;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array(
            'receivers' => $this->receivers,
            'previewText' => $this->previewText,
        );
    }
}