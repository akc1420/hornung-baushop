<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\InstallationLibrary\AclRole;

use Pickware\DalBundle\EntityManager;
use Shopware\Core\Framework\Api\Acl\Role\AclRoleDefinition;
use Shopware\Core\Framework\Context;

class AclRoleUninstaller
{
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param Context|null $context @deprecated next-major parameter will be non-optional
     */
    public function uninstallAclRole(AclRole $aclRole, ?Context $context = null): void
    {
        if (!$context) {
            trigger_error(
                sprintf('Not passing %s parameter is deprecated in %s.', Context::class, __METHOD__),
                E_USER_DEPRECATED,
            );
            $context = Context::createDefaultContext();
        }
        $this->entityManager->deleteByCriteria(
            AclRoleDefinition::class,
            ['name' => $aclRole->getName()],
            $context,
        );
    }
}
