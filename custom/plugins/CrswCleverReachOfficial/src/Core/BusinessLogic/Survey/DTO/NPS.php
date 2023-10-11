<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Survey\DTO;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

class NPS extends DataTransferObject
{
    /**
     * @var Counts
     */
    protected $counts;
    /**
     * @var int
     */
    protected $nps;

    /**
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Survey\DTO\Counts
     */
    public function getCounts()
    {
        return $this->counts;
    }

    /**
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Survey\DTO\Counts $counts
     */
    public function setCounts($counts)
    {
        $this->counts = $counts;
    }

    /**
     * @return int
     */
    public function getNps()
    {
        return $this->nps;
    }

    /**
     * @param int $nps
     */
    public function setNps($nps)
    {
        $this->nps = $nps;
    }

    /**
     * @inheritDoc
     * @return array
     */
    public function toArray()
    {
        return array(
            'counts' => $this->counts->toArray(),
            'nps' => $this->nps,
        );
    }

    /**
     * @param array $data
     *
     * @return NPS
     */
    public static function fromArray(array $data)
    {
        $nps = new static();
        $nps->counts = Counts::fromArray(static::getDataValue($data, 'counts', array()));
        $nps->nps = static::getDataValue($data, 'nps');

        return $nps;
    }
}