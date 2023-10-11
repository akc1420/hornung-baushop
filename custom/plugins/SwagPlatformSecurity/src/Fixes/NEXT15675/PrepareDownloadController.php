<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT15675;

use Shopware\Core\Content\ImportExport\Aggregate\ImportExportFile\ImportExportFileEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Swag\Security\Components\State;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PrepareDownloadController
{
    /**
     * @var EntityRepositoryInterface $importFileRepository
     */
    private $importFileRepository;

    /**
     * @var State $state
     */
    private $state;

    public function __construct(EntityRepositoryInterface $importFileRepository, State $state)
    {
        $this->importFileRepository = $importFileRepository;
        $this->state = $state;
    }

    /**
     * @Route(path="/api/v{version}/swag-security/_action/regenerate-import-file-key/{fileId}")
     * @Route(path="/api/swag-security/_action/regenerate-import-file-key/{fileId}")
     */
    public function regenerateImportFileKey(string $fileId, Context $context): Response
    {
        if (!$this->state->isActive('NEXT-15675')) {
            return new Response('', Response::HTTP_FORBIDDEN);
        }

        $token = ImportExportFileEntity::generateAccessToken();

        $this->importFileRepository->update([
            [
                'id' => $fileId,
                'accessToken' => $token
            ]
        ], $context);

        return new JsonResponse(['token' => $token]);
    }
}
