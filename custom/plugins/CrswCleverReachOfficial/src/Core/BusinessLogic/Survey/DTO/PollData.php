<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Survey\DTO;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

/**
 * Class PollData
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Survey\DTO
 */
class PollData extends DataTransferObject
{
    /**
     * @var string
     */
    protected $id;
    /**
     * @var string
     */
    protected $user;
    /**
     * @var string
     */
    protected $userName;
    /**
     * @var string
     */
    protected $category;
    /**
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $type;
    /**
     * @var true
     */
    protected $path;
    /**
     * @var string
     */
    protected $layout;
    /**
     * @var int
     */
    protected $start;
    /**
     * @var int
     */
    protected $stop;
    /**
     * @var bool
     */
    protected $paused;
    /**
     * @var string
     */
    protected $feature;
    /**
     * @var int
     */
    protected $delay;
    /**
     * @var integer
     */
    protected $infoDelay;
    /**
     * @var string
     */
    protected $contentId;
    /**
     * @var string
     */
    protected $iconset;
    /**
     * @var array
     */
    protected $content;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param string $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return string
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * @param string $userName
     */
    public function setUserName($userName)
    {
        $this->userName = $userName;
    }

    /**
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param string $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

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
     * @return true
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param true $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * @param string $layout
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;
    }

    /**
     * @return int
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @param int $start
     */
    public function setStart($start)
    {
        $this->start = $start;
    }

    /**
     * @return int
     */
    public function getStop()
    {
        return $this->stop;
    }

    /**
     * @param int $stop
     */
    public function setStop($stop)
    {
        $this->stop = $stop;
    }

    /**
     * @return bool
     */
    public function isPaused()
    {
        return $this->paused;
    }

    /**
     * @param bool $paused
     */
    public function setPaused($paused)
    {
        $this->paused = $paused;
    }

    /**
     * @return string
     */
    public function getFeature()
    {
        return $this->feature;
    }

    /**
     * @param string $feature
     */
    public function setFeature($feature)
    {
        $this->feature = $feature;
    }

    /**
     * @return int
     */
    public function getDelay()
    {
        return $this->delay;
    }

    /**
     * @param int $delay
     */
    public function setDelay($delay)
    {
        $this->delay = $delay;
    }

    /**
     * @return int
     */
    public function getInfoDelay()
    {
        return $this->infoDelay;
    }

    /**
     * @param int $infoDelay
     */
    public function setInfoDelay($infoDelay)
    {
        $this->infoDelay = $infoDelay;
    }

    /**
     * @return string
     */
    public function getContentId()
    {
        return $this->contentId;
    }

    /**
     * @param string $contentId
     */
    public function setContentId($contentId)
    {
        $this->contentId = $contentId;
    }

    /**
     * @return string
     */
    public function getIconset()
    {
        return $this->iconset;
    }

    /**
     * @param string $iconset
     */
    public function setIconset($iconset)
    {
        $this->iconset = $iconset;
    }

    /**
     * @return array
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param array $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $content = array();
        /** @var LocaleContent $localeContent */
        foreach ($this->content as $lang => $localeContent) {
            $content[$lang] = $localeContent->toArray();
        }

        return array(
            'id' => $this->id,
            'user' => $this->user,
            'user_name' => $this->userName,
            'category' => $this->category,
            'name' => $this->name,
            'type' => $this->type,
            'path' => $this->path,
            'layout' => $this->layout,
            'start' => $this->start,
            'stop' => $this->stop,
            'paused' => $this->paused,
            'feature' => $this->feature,
            'delay' => $this->delay,
            'info_delay' => $this->infoDelay,
            'content_id' => $this->contentId,
            'iconset' => $this->iconset,
            'content' => $content,
        );
    }

    /**
     * @param array $data
     *
     * @return PollData
     */
    public static function fromArray(array $data)
    {
        $poll = new static();
        $poll->id = static::getDataValue($data, 'id');
        $poll->user = static::getDataValue($data, 'user');
        $poll->userName = static::getDataValue($data, 'user_name');
        $poll->category = static::getDataValue($data, 'category');
        $poll->name = static::getDataValue($data, 'name');
        $poll->type = static::getDataValue($data, 'type');
        $poll->path = static::getDataValue($data, 'path');
        $poll->layout = static::getDataValue($data, 'layout');
        $poll->start = static::getDataValue($data, 'start', time());
        $poll->stop = static::getDataValue($data, 'stop', time());
        $poll->paused = static::getDataValue($data, 'paused', false);
        $poll->feature = static::getDataValue($data, 'feature');
        $poll->delay = static::getDataValue($data, 'delay', 0);
        $poll->infoDelay = static::getDataValue($data, 'info_delay', 0);
        $poll->contentId = static::getDataValue($data, 'content_id');
        $poll->iconset = static::getDataValue($data, 'iconset');
        $poll->id = static::getDataValue($data, 'id');
        $poll->content = LocaleContent::fromBatch(static::getDataValue($data, 'content', array()));

        return $poll;
    }
}
