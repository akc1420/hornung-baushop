<?php

namespace Crsw\CleverReachOfficial\Service\BusinessLogic\Automation\Entities;

use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Configuration\EntityConfiguration;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Configuration\IndexMap;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Entity;

/**
 * Class RecoveryRecord
 *
 * @package Crsw\CleverReachOfficial\Service\BusinessLogic\Automation\Entities
 */
class RecoveryRecord extends Entity
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * @var string
     */
    protected $token;
    /**
     * @var string
     */
    protected $email;
    /**
     * @var array
     */
    protected $items;
    /**
     * @var string[]
     */
    protected $fields = [
        'id',
        'token',
        'email',
        'items'
    ];

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @param string $token
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param array $items
     */
    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    /**
     * @return EntityConfiguration
     */
    public function getConfig(): EntityConfiguration
    {
        $map = new IndexMap();
        $map->addStringIndex('token');
        $map->addStringIndex('email');

        return new EntityConfiguration($map, 'RecoveryRecord');
    }
}
