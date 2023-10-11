<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Order\DTO\Category;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

class Category extends DataTransferObject
{
    /**
     * @var string
     */
    private $value;

    /**
     * Category constructor.
     *
     * @param $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array(
            'value' => $this->getValue(),
        );
    }

    /**
     * @inheritDoc
     */
    public static function fromArray(array $data)
    {
        return new static(static::getDataValue($data, 'value'));
    }
}