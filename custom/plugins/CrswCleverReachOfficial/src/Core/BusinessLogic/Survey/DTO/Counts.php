<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Survey\DTO;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

class Counts extends DataTransferObject
{
    /**
     * @var int
     */
    protected $total;
    /**
     * @var int
     */
    protected $promotors;
    /**
     * @var int
     */
    protected $detractors;
    /**
     * @var int
     */
    protected $yes;
    /**
     * @var int
     */
    protected $no;
    /**
     * @var int
     */
    protected $score;

    /**
     * @return int
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * @param int $total
     */
    public function setTotal($total)
    {
        $this->total = $total;
    }

    /**
     * @return int
     */
    public function getPromotors()
    {
        return $this->promotors;
    }

    /**
     * @param int $promotors
     */
    public function setPromotors($promotors)
    {
        $this->promotors = $promotors;
    }

    /**
     * @return int
     */
    public function getDetractors()
    {
        return $this->detractors;
    }

    /**
     * @param int $detractors
     */
    public function setDetractors($detractors)
    {
        $this->detractors = $detractors;
    }

    /**
     * @return int
     */
    public function getYes()
    {
        return $this->yes;
    }

    /**
     * @param int $yes
     */
    public function setYes($yes)
    {
        $this->yes = $yes;
    }

    /**
     * @return int
     */
    public function getNo()
    {
        return $this->no;
    }

    /**
     * @param int $no
     */
    public function setNo($no)
    {
        $this->no = $no;
    }

    /**
     * @return int
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * @param int $score
     */
    public function setScore($score)
    {
        $this->score = $score;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array(
            'total' => $this->total,
            'promotors' => $this->promotors,
            'detractors' => $this->detractors,
            'yes' => $this->yes,
            'no' => $this->no,
            'score' => $this->score,
        );
    }

    /**
     * @param array $data
     *
     * @return Counts
     */
    public static function fromArray(array $data)
    {
        $counts = new static();
        $counts->total = static::getDataValue($data, 'total', 0);
        $counts->promotors = static::getDataValue($data, 'promotors', 0);
        $counts->detractors = static::getDataValue($data, 'detractors', 0);
        $counts->yes = static::getDataValue($data, 'yes', 0);
        $counts->no = static::getDataValue($data, 'no', 0);
        $counts->score = static::getDataValue($data, 'score', 0);

        return $counts;
    }
}