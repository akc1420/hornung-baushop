<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT14883;

class IntegrationDefinition extends \Shopware\Core\System\Integration\IntegrationDefinition
{
    public function getDefaults(): array
    {
        return ['admin' => false];
    }
}
