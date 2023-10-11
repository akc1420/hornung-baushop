<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Mailing\DTO;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

class Mailing extends DataTransferObject
{
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
     * @var \Crsw\CleverReachOfficial\Core\BusinessLogic\Mailing\DTO\MailingReceivers
     */
    protected $receivers;
    /**
     * @var \Crsw\CleverReachOfficial\Core\BusinessLogic\Mailing\DTO\MailingContent
     */
    protected $content;
    /**
     * @var \Crsw\CleverReachOfficial\Core\BusinessLogic\Mailing\DTO\MailingSettings
     */
    protected $settings;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    /**
     * @return string
     */
    public function getSenderName()
    {
        return $this->senderName;
    }

    /**
     * @param string $senderName
     */
    public function setSenderName($senderName)
    {
        $this->senderName = $senderName;
    }

    /**
     * @return string
     */
    public function getSenderEmail()
    {
        return $this->senderEmail;
    }

    /**
     * @param string $senderEmail
     */
    public function setSenderEmail($senderEmail)
    {
        $this->senderEmail = $senderEmail;
    }

    /**
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Mailing\DTO\MailingReceivers
     */
    public function getReceivers()
    {
        return $this->receivers;
    }

    /**
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Mailing\DTO\MailingReceivers $receivers
     */
    public function setReceivers($receivers)
    {
        $this->receivers = $receivers;
    }

    /**
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Mailing\DTO\MailingContent
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Mailing\DTO\MailingContent $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Mailing\DTO\MailingSettings
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Mailing\DTO\MailingSettings $settings
     */
    public function setSettings($settings)
    {
        $this->settings = $settings;
    }

    public function toArray()
    {
        $result = array(
            'name' => $this->getName(),
            'subject' => $this->getSubject(),
            'sender_name' => $this->getSenderName(),
            'sender_email' => $this->getSenderEmail(),
        );

        if ($this->content) {
            $result['content'] = $this->getContent()->toArray();
        }

        if ($this->settings) {
            $result['settings'] = $this->getSettings()->toArray();
        }

        if ($this->receivers) {
            $result['receivers'] = $this->getReceivers()->toArray();
        }

        return $result;
    }

    public static function fromArray(array $data)
    {
        $entity = new static;
        $entity->setName(static::getDataValue($data, 'name'));
        $entity->setSubject(static::getDataValue($data, 'subject'));
        $entity->setSenderName(static::getDataValue($data, 'sender_name'));
        $entity->setSenderEmail(static::getDataValue($data, 'sender_email'));

        if (!empty($data['settings'])) {
            $entity->setSettings(MailingSettings::fromArray($data['settings']));
        }

        if (!empty($data['receivers'])) {
            $entity->setReceivers(MailingReceivers::fromArray($data['receivers']));
        }

        if (!empty($data['content'])) {
            $entity->setContent(MailingContent::fromArray($data['content']));
        }

        return $entity;
    }
}