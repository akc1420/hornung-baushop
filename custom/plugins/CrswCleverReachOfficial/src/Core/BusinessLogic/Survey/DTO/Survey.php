<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Survey\DTO;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

/**
 * Class Survey
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Survey\DTO
 */
class Survey extends DataTransferObject
{
    const CLASS_NAME = __CLASS__;

    /**
     * @var PollData
     */
    protected $meta;
    /**
     * @var string
     */
    protected $layout;
    /**
     * @var NPS
     */
    protected $nps;
    /**
     * @var string
     */
    protected $template;
    /**
     * @var string
     */
    protected $token;

    /**
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Survey\DTO\PollData
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Survey\DTO\PollData $meta
     */
    public function setMeta($meta)
    {
        $this->meta = $meta;
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
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Survey\DTO\NPS
     */
    public function getNps()
    {
        return $this->nps;
    }

    /**
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Survey\DTO\NPS $nps
     */
    public function setNps($nps)
    {
        $this->nps = $nps;
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param string $template
     */
    public function setTemplate($template)
    {
        $this->template = $template;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @inheritDoc
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'meta' => $this->meta->toArray(),
            'layout' => $this->layout,
            'template' => $this->template,
            'token' => $this->token
        );
    }

    /**
     * @param array $data
     *
     * @return Survey
     */
    public static function fromArray(array $data)
    {
        $survey = new static();
        $survey->meta = PollData::fromArray(static::getDataValue($data, 'meta', array()));
        $survey->layout = static::getDataValue($data, 'layout');
        $survey->nps = NPS::fromArray(static::getDataValue($data, 'nps', array()));
        $survey->template = static::getDataValue($data, 'template');
        $survey->token = static::getDataValue($data, 'token');

        return $survey;
    }
}
