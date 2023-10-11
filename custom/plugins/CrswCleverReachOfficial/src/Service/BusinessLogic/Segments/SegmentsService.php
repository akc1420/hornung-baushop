<?php


namespace Crsw\CleverReachOfficial\Service\BusinessLogic\Segments;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Tag;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\DTO\Segment;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\SegmentService;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Tag\TagService;

/**
 * Class SegmentsService
 *
 * @package Crsw\CleverReachOfficial\Service\BusinessLogic\Segments
 */
class SegmentsService extends SegmentService
{
    /**
     * Retrieves list of available segments.
     *
     * @return Segment[] The list of available segments.
     */
    public function getSegments(): array
    {
        $tags = $this->getTagService()->getTags();

        return $this->transformToSegments($tags);
    }

    /**
     * Retrieves list of segments' names.
     *
     * @return string
     */
    public function getSegmentNames(): string
    {
        $segments = $this->getSegments();

        $names = '';

        foreach ($segments as $segment) {
            $names .= $segment->getName() . ', ';
        }

        return $names;
    }

    /**
     * Transforms tags to segments.
     *
     * @param Tag[] $tags
     *
     * @return array
     */
    protected function transformToSegments($tags): array
    {
        $segments = [];

        foreach ($tags as $tag) {
            $segments[] = $tag->toSegment();
        }

        return $segments;
    }

    /**
     * @return TagService
     */
    protected function getTagService(): TagService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(TagService::class);
    }
}