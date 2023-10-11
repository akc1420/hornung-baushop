<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\SyncSettings\DTO;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

class SyncSettings extends DataTransferObject
{
    const CLASS_NAME = __CLASS__;

    /**
     * @var string[]
     */
    public $import;
    /**
     * @var string[]
     */
    public $notImport;

    /**
     * SyncSettings constructor.
     *
     * @param string[] $import
     * @param string[] $notImport
     */
    public function __construct(array $import, array $notImport)
    {
        $this->import = $import;
        $this->notImport = $notImport;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array(
            'import' => $this->import,
            'not_import' => $this->notImport,
        );
    }

    public static function fromArray(array $data)
    {
        return new static($data['import'], $data['not_import']);
    }
}