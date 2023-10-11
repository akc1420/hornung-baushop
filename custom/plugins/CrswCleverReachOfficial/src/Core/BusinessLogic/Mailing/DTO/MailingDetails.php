<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Mailing\DTO;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

/**
 * Class MailingDetails
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Mailing\DTO
 */
class MailingDetails extends DataTransferObject
{
    /**
     * @var string
     */
    protected $id;
    /**
     * @var string
     */
    protected $categoryId;
    /**
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $subject;
    /**
     * @var string
     */
    protected $senderName;
    /**
     * @var string
     */
    protected $senderEmail;
    /**
     * @var string
     */
    protected $bodyHtml;
    /**
     * @var string
     */
    protected $bodyText;
    /**
     * @var int
     */
    protected $stamp;
    /**
     * @var int
     */
    protected $lastChanged;
    /**
     * @var int
     */
    protected $started;
    /**
     * @var int
     */
    protected $finished;
    /**
     * @var int
     */
    protected $ready;

    /**
     * MailingDetails constructor.
     *
     * @param string $id
     * @param string $categoryId
     * @param string $name
     * @param string $subject
     * @param string $senderName
     * @param string $senderEmail
     */
    public function __construct($id, $categoryId, $name, $subject, $senderName, $senderEmail)
    {
        $this->id = $id;
        $this->categoryId = $categoryId;
        $this->name = $name;
        $this->subject = $subject;
        $this->senderName = $senderName;
        $this->senderEmail = $senderEmail;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @return string
     */
    public function getSenderName()
    {
        return $this->senderName;
    }

    /**
     * @return string
     */
    public function getSenderEmail()
    {
        return $this->senderEmail;
    }

    /**
     * @return string
     */
    public function getBodyHtml()
    {
        return $this->bodyHtml;
    }

    /**
     * @param string $bodyHtml
     */
    public function setBodyHtml($bodyHtml)
    {
        $this->bodyHtml = $bodyHtml;
    }

    /**
     * @return string
     */
    public function getBodyText()
    {
        return $this->bodyText;
    }

    /**
     * @param string $bodyText
     */
    public function setBodyText($bodyText)
    {
        $this->bodyText = $bodyText;
    }

    /**
     * @return int
     */
    public function getStamp()
    {
        return $this->stamp;
    }

    /**
     * @param int $stamp
     */
    public function setStamp($stamp)
    {
        $this->stamp = $stamp;
    }

    /**
     * @return int
     */
    public function getLastChanged()
    {
        return $this->lastChanged;
    }

    /**
     * @param int $lastChanged
     */
    public function setLastChanged($lastChanged)
    {
        $this->lastChanged = $lastChanged;
    }

    /**
     * @return int
     */
    public function getStarted()
    {
        return $this->started;
    }

    /**
     * @param int $started
     */
    public function setStarted($started)
    {
        $this->started = $started;
    }

    /**
     * @return int
     */
    public function getFinished()
    {
        return $this->finished;
    }

    /**
     * @param int $finished
     */
    public function setFinished($finished)
    {
        $this->finished = $finished;
    }

    /**
     * @return int
     */
    public function getReady()
    {
        return $this->ready;
    }

    /**
     * @param int $ready
     */
    public function setReady($ready)
    {
        $this->ready = $ready;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array(
            'id' => $this->id,
            'category_id' => $this->categoryId,
            'name' => $this->name,
            'subject' => $this->subject,
            'sender_name' => $this->senderName,
            'sender_email' => $this->senderEmail,
            'body_html' => $this->bodyHtml,
            'body_text' => $this->bodyText,
            'stamp' => $this->stamp,
            'last_changed' => $this->lastChanged,
            'started' => $this->started,
            'finished' => $this->finished,
            'ready' => $this->ready,
        );
    }

    /**
     * @param array $data
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Mailing\DTO\MailingDetails
     */
    public static function fromArray(array $data)
    {
        $details = new static(
            static::getDataValue($data, 'id'),
            static::getDataValue($data, 'category_id'),
            static::getDataValue($data, 'name'),
            static::getDataValue($data, 'subject'),
            static::getDataValue($data, 'sender_name'),
            static::getDataValue($data, 'sender_email')
        );

        $details->bodyHtml = static::getDataValue($data, 'body_html');
        $details->bodyText = static::getDataValue($data, 'body_text');
        $details->stamp = static::getDataValue($data, 'stamp');
        $details->lastChanged = static::getDataValue($data, 'last_changed');
        $details->started = static::getDataValue($data, 'started');
        $details->finished = static::getDataValue($data, 'finished');
        $details->ready = static::getDataValue($data, 'ready');

        return $details;
    }
}
