<?php

namespace Crsw\CleverReachOfficial\Entity\Automation\Repositories;

use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Entity;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Entity\Base\Repositories\BaseRepository;
use Doctrine\DBAL\DBALException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

/**
 * Class AutomationRepository
 *
 * @package Crsw\CleverReachOfficial\Entity\Automation\Repositories
 */
class AutomationRepository extends BaseRepository
{
    /**
     * @var string
     */
    protected static $doctrineModel = 'cleverreach_automation_entity';

    /**
     * Returns full class name.
     *
     * @return string Full class name.
     */
    public static function getClassName(): string
    {
        return static::class;
    }

    /**
     * Executes delete query and returns success flag.
     *
     * @param Entity $entity Entity to be deleted.
     *
     * @return bool TRUE if operation succeeded; otherwise, FALSE.
     */
    public function delete(Entity $entity): bool
    {
        $sql = "DELETE FROM cleverreach_automation_entity WHERE id=:id";
        try {
            $this->getConnection()->executeUpdate($sql, ['id' => $entity->getId()]);
            return true;
        } catch (DBALException $e) {
            Logger::logError($e->getMessage());
            return false;
        }
    }

    /**
     * @return EntityRepositoryInterface
     */
    protected function getEntityRepository(): EntityRepositoryInterface
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService('Automation' . EntityRepositoryInterface::class);
    }
}
