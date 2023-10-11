<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT15675;

use Shopware\Core\Content\ImportExport\Aggregate\ImportExportLog\ImportExportLogEntity;
use Shopware\Core\Content\ImportExport\Service\ImportExportService as CoreImportExportService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImportExportService extends CoreImportExportService
{
    public const PLACEHOLDER_EMPTY_ACCESS_TOKEN = '-';

    /**
     * @var EntityRepositoryInterface $fileRepository
     */
    private $fileRepository;

    public function __construct(array $parentArguments, EntityRepositoryInterface $fileRepository)
    {
        parent::__construct(...$parentArguments);
        $this->fileRepository = $fileRepository;
    }

    public function prepareImport(Context $context, string $profileId, \DateTimeInterface $expireDate, UploadedFile $file, array $config = []): ImportExportLogEntity
    {
        $ret = parent::prepareImport($context, $profileId, $expireDate, $file, $config);

        $this->updateToEmptyPlaceholder($ret->getFile()->getId());
        $ret->getFile()->setAccessToken(self::PLACEHOLDER_EMPTY_ACCESS_TOKEN);

        return $ret;
    }

    public function prepareExport(Context $context, string $profileId, \DateTimeInterface $expireDate, ?string $originalFileName = null, array $config = [], ?string $destinationPath = null, string $activity = ImportExportLogEntity::ACTIVITY_EXPORT): ImportExportLogEntity
    {
        $ret = parent::prepareExport($context, $profileId, $expireDate, $originalFileName, $config, $destinationPath, $activity);

        $this->updateToEmptyPlaceholder($ret->getFile()->getId());
        $ret->getFile()->setAccessToken(self::PLACEHOLDER_EMPTY_ACCESS_TOKEN);

        return $ret;
    }

    private function updateToEmptyPlaceholder(string $fileId): void
    {
        $this->fileRepository->update([
            [
                'id' => $fileId,
                'accessToken' => self::PLACEHOLDER_EMPTY_ACCESS_TOKEN
            ]
        ], Context::createDefaultContext());
    }
}
