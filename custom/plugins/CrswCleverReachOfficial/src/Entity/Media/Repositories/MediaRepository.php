<?php

namespace Crsw\CleverReachOfficial\Entity\Media\Repositories;

use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

/**
 * Class MediaRepository
 *
 * @package Crsw\CleverReachOfficial\Entity\Media\Repositories
 */
class MediaRepository
{
    /**
     * @var EntityRepositoryInterface
     */
    private $baseRepository;

    /**
     * MediaRepository constructor.
     *
     * @param EntityRepositoryInterface $baseRepository
     */
    public function __construct(EntityRepositoryInterface $baseRepository)
    {
        $this->baseRepository = $baseRepository;
    }

    /**
     * Get media by id.
     *
     * @param $mediaId
     * @param Context $context
     *
     * @return MediaEntity
     */
    public function getMediaById($mediaId, Context $context): MediaEntity
    {
        $criteria = new Criteria([$mediaId]);

        return $this->baseRepository->search($criteria, $context)->first();
    }
}