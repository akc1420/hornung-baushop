<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Blacklist;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Data\TimestampsAware;

/**
 * Class Blacklist
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Blacklist
 */
class Blacklist extends TimestampsAware
{
    /**
     * @var string
     */
    protected $email;
    /**
     * @var string
     */
    protected $comment;
    /**
     * @var \DateTime
     */
    protected $stamp;
    /**
     * @var int
     */
    protected $isLocked;

    /**
     * Blacklist constructor.
     *
     * @param string $email
     */
    public function __construct($email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    /**
     * @return \DateTime
     */
    public function getStamp()
    {
        return $this->stamp;
    }

    /**
     * @param \DateTime $stamp
     */
    public function setStamp($stamp)
    {
        $this->stamp = $stamp;
    }

    /**
     * @return int
     */
    public function getIsLocked()
    {
        return $this->isLocked;
    }

    /**
     * @param int $isLocked
     */
    public function setIsLocked($isLocked)
    {
        $this->isLocked = $isLocked;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array(
            'email' => $this->getEmail(),
            'comment' => $this->getComment(),
            'stamp' => static::getTimestamp($this->getStamp()),
            'isLocked' => $this->getIsLocked(),
        );
    }

    /**
     * @param array $data
     *
     * @return Blacklist
     */
    public static function fromArray(array $data)
    {
        $blacklist = new static(static::getDataValue($data, 'email'));

        $blacklist->setComment(static::getDataValue($data, 'comment'));
        $blacklist->setStamp(static::getDate(static::getDataValue($data, 'stamp', null)));
        $blacklist->setIsLocked(static::getDataValue($data, 'isLocked', 0));

        return $blacklist;
    }
}
