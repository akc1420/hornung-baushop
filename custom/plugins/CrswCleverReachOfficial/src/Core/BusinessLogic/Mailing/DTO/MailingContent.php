<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Mailing\DTO;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

class MailingContent extends DataTransferObject
{
    /**
     * @var string
     */
    protected $type;
    /**
     * @var string
     */
    protected $html;
    /**
     * @var string
     */
    protected $text;

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getHtml()
    {
        return $this->html;
    }

    /**
     * @param string $html
     */
    public function setHtml($html)
    {
        $this->html = $html;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    public function toArray()
    {
        return array(
            'type' => $this->getType(),
            'html' => $this->getHtml(),
            'text' => $this->getText(),
        );
    }

    public static function fromArray(array $data)
    {
        $entity = new static;

        $entity->setType(static::getDataValue($data, 'type'));
        $entity->setHtml(static::getDataValue($data, 'html'));
        $entity->setText(static::getDataValue($data, 'text'));

        return $entity;
    }
}