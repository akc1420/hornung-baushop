<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT15675;

use Shopware\Core\Content\ImportExport\Aggregate\ImportExportFile\ImportExportFileEntity;
use Shopware\Core\Content\ImportExport\Exception\FileNotFoundException;
use Shopware\Core\Content\ImportExport\Exception\InvalidFileAccessTokenException;
use Shopware\Core\Content\ImportExport\Service\ImportExportService as CoreImportExportService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Swag\Security\Components\AbstractSecurityFix;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class SecurityFix extends AbstractSecurityFix
{
    /**
     * @var EntityRepositoryInterface $fileRepository
     */
    private $fileRepository;

    /**
     * @var EventDispatcherInterface $eventDispatcher
     */
    private $eventDispatcher;

    public function __construct(EntityRepositoryInterface $fileRepository, EventDispatcherInterface $eventDispatcher)
    {
        $this->fileRepository = $fileRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getTicket(): string
    {
        return 'NEXT-15675';
    }

    public static function getMaxVersion(): ?string
    {
        return '6.4.3.1';
    }

    public static function getMinVersion(): string
    {
        return '6.1.5';
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER_ARGUMENTS => 'onControllerArguments'
        ];
    }

    public static function buildContainer(ContainerBuilder $container): void
    {
        $coreImportExportService = $container->getDefinition(CoreImportExportService::class);

        $def = new Definition(ImportExportService::class);
        $def->setDecoratedService(CoreImportExportService::class);
        $def->setArguments([$coreImportExportService->getArguments(), new Reference('import_export_file.repository')]);
        $def->addTag('swag.security.fix', ['ticket' => \Swag\Security\Fixes\NEXT15675\SecurityFix::class]);

        $container
            ->setDefinition(ImportExportService::class, $def);
    }

    public function onControllerArguments(ControllerArgumentsEvent $event)
    {
        $request = $event->getRequest();

        $route = $request->attributes->get('_route', '');

        if ($route !== 'api.action.import_export.file.download') {
            return;
        }

        $fileId = (string) $request->query->get('fileId', '');

        /** @var ImportExportFileEntity $file */
        $file = $this->fileRepository->search(new Criteria([$fileId]), Context::createDefaultContext())->first();

        if ($file === null) {
            throw new FileNotFoundException($fileId);
        }

        if ($file->getAccessToken() === ImportExportService::PLACEHOLDER_EMPTY_ACCESS_TOKEN) {
            throw new InvalidFileAccessTokenException();
        }

        if ($file->getUpdatedAt() === null) {
            throw new InvalidFileAccessTokenException();
        }

        $diff = time() - $file->getUpdatedAt()->getTimestamp();

        if ($diff > 300) {
            throw new InvalidFileAccessTokenException();
        }


        $executed = false;
        $this->eventDispatcher->addListener(KernelEvents::TERMINATE, function () use($fileId, &$executed) {
            if ($executed) {
                return;
            }

            $this->fileRepository->update([
                [
                    'id' => $fileId,
                    'accessToken' => ImportExportService::PLACEHOLDER_EMPTY_ACCESS_TOKEN
                ]
            ], Context::createDefaultContext());

            $executed = true;
        });
    }
}
