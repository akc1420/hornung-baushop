<?php declare(strict_types=1);

namespace SwagSocialShopping\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1659081754AddAclRights extends MigrationStep
{
    public const NEW_PRIVILEGES = [
        'order.viewer' => [
            'swag_social_shopping_order:read',
            'sales_channel_type:read',
        ],
        'customer.viewer' => [
            'swag_social_shopping_customer:read',
            'sales_channel_type:read',
        ],
    ];

    public function getCreationTimestamp(): int
    {
        return 1659081754;
    }

    public function update(Connection $connection): void
    {
        $roles = $connection->iterateAssociative('SELECT * from `acl_role`');

        try {
            $connection->beginTransaction();

            foreach ($roles as $role) {
                $currentPrivileges = \json_decode($role['privileges'], true, 512, \JSON_THROW_ON_ERROR);
                $newPrivileges = $this->fixRolePrivileges($currentPrivileges);

                if ($currentPrivileges === $newPrivileges) {
                    continue;
                }

                $role['privileges'] = \json_encode($newPrivileges, \JSON_THROW_ON_ERROR);
                $role['updated_at'] = (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_FORMAT);

                $connection->update('acl_role', $role, ['id' => $role['id']]);
            }

            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();

            throw $e;
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }

    private function fixRolePrivileges(array $rolePrivileges): array
    {
        foreach (self::NEW_PRIVILEGES as $key => $new) {
            if (\in_array($key, $rolePrivileges, true)) {
                $rolePrivileges = \array_merge($rolePrivileges, $new);
            }
        }

        return \array_values(\array_unique($rolePrivileges));
    }
}
