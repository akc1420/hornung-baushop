<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT13371;

use Shopware\Core\Framework\Api\Context\SalesChannelApiSource;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Field;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ReadProtected;
use Swag\Security\Components\AbstractSecurityFix;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SecurityFix extends AbstractSecurityFix
{
    /**
     * @var DefinitionInstanceRegistry
     */
    private $registry;

    public function __construct(DefinitionInstanceRegistry $registry)
    {
        $this->registry = $registry;
    }

    public static function getTicket(): string
    {
        return 'NEXT-13371';
    }

    public static function getMinVersion(): string
    {
        return '6.1.0';
    }

    public static function getMaxVersion(): ?string
    {
        return '6.3.4.1';
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', PHP_INT_MAX]
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $list = file_get_contents(__DIR__ . '/protection-list.json');
        $list = json_decode($list, true);

        $definitionList = array_keys($this->registry->getDefinitions());

        foreach ($list as $entity => $fields) {
            if (!in_array($entity, $definitionList)) {
                continue;
            }

            try {
                $definition = $this->registry->getByEntityName($entity);
            } catch (\Exception $e) {
                continue;
            }

            foreach ($fields as $field) {
                try {
                    $instance = $definition->getField($field);
                } catch (\Exception $e) {
                    continue;
                }

                if (!$instance instanceof Field) {
                    continue;
                }

                $instance->addFlags(new ReadProtected(SalesChannelApiSource::class));
            }
        }

    }
}
