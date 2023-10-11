<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT15669;

use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Shopware\Core\System\SystemConfig\SystemConfigEntity;
use Swag\Security\Components\AbstractSecurityFix;

class SecurityFix extends AbstractSecurityFix
{
    public static function getTicket(): string
    {
        return 'NEXT-15669';
    }

    public function onSystemConfigLoaded(EntityLoadedEvent $event): void
    {
        $allowedValues = [null, '', '-t', '-bs'];

        /** @var SystemConfigEntity $entity */
        foreach ($event->getEntities() as $entity) {
            if ($entity->getConfigurationKey() !== 'core.mailerSettings.sendMailOptions') {
                continue;
            }

            $val = $entity->getConfigurationValue();
            if (!in_array($val, $allowedValues, true)) {
                $entity->setConfigurationValue('-t');
            }
        }
    }

    public static function getMaxVersion(): ?string
    {
        return '6.4.3.1';
    }

    public static function getMinVersion(): string
    {
        return '6.4.0.0';
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'system_config.loaded' => 'onSystemConfigLoaded'
        ];
    }
}
