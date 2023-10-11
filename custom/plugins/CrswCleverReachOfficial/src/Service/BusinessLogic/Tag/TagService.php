<?php


namespace Crsw\CleverReachOfficial\Service\BusinessLogic\Tag;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Special\Buyer;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Special\Contact;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Special\Subscriber;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Tag;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Entity\CustomerGroup\Repositories\CustomerGroupRepository;
use Crsw\CleverReachOfficial\Entity\SalesChannel\Repositories\SalesChannelRepository;
use Crsw\CleverReachOfficial\Entity\Tag\Repositories\TagRepository;
use Doctrine\DBAL\DBALException;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\Tag\TagEntity;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class TagService
 *
 * @package Crsw\CleverReachOfficial\Service\BusinessLogic\Tag
 */
class TagService
{
    public const SOURCE = 'Shopware 6';
    public const CUSTOMER_GROUP_TAG = 'CustomerGroup';
    public const SHOP_TAG = 'SalesChannel';
    public const TAG = 'Tag';
    public const GUEST = 'Guest';
    public const ORIGIN = 'Origin';

    /**
     * @var TagRepository
     */
    private $tagRepository;
    /**
     * @var SalesChannelRepository
     */
    private $salesChannelRepository;
    /**
     * @var CustomerGroupRepository
     */
    private $customerGroupRepository;
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * TagService constructor.
     *
     * @param TagRepository $tagRepository
     * @param SalesChannelRepository $salesChannelRepository
     * @param CustomerGroupRepository $customerGroupRepository
     * @param RequestStack $requestStack
     */
    public function __construct(
        TagRepository $tagRepository,
        SalesChannelRepository $salesChannelRepository,
        CustomerGroupRepository $customerGroupRepository,
        RequestStack $requestStack
    ) {
        $this->tagRepository = $tagRepository;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->customerGroupRepository = $customerGroupRepository;
        $this->requestStack = $requestStack;
    }

    /**
     * Gets tags.
     *
     * @return Tag[]
     */
    public function getTags(): array
    {
        return array_merge($this->getSpecialTags(), $this->getOriginTags());
    }

    /**
     * Gets list of special tags.
     *
     * @return array
     */
    protected function getSpecialTags(): array
    {
        return [
            new Buyer(self::SOURCE),
            new Contact(self::SOURCE),
            new Subscriber(self::SOURCE),
        ];
    }

    /**
     * Gets list of origin tags.
     *
     * @return array
     */
    protected function getOriginTags(): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $context = $request ? $request->get('sw-context') : Context::createDefaultContext();

        $customerGroups = $this->customerGroupRepository->getCustomerGroups($context);
        $salesChannels = $this->salesChannelRepository->getSalesChannels($context);
        try {
            $tags = $this->tagRepository->getTags($context);
        } catch (DBALException $e) {
            Logger::logError('Failed to get tags because: ' . $e->getMessage());
            $tags = [];
        }

        $guestTag = new Tag(self::SOURCE, self::GUEST);
        $guestTag->setType(self::ORIGIN);

        return array_merge(
            $this->formatTags($customerGroups, self::CUSTOMER_GROUP_TAG),
            $this->formatTags($salesChannels, self::SHOP_TAG),
            $this->formatTags($tags, self::TAG),
            [$guestTag]
        );
    }

    /**
     * Formats tags.
     *
     * @param EntityCollection $collection
     * @param string $type
     *
     * @return array
     */
    protected function formatTags(EntityCollection $collection, string $type): array
    {
        $tags = [];

        /** @var CustomerGroupEntity | TagEntity | SalesChannelEntity $entity */
        foreach ($collection as $entity) {
            if (trim($entity->getName()) === '') {
                continue;
            }

            $tag = new Tag(self::SOURCE, trim($entity->getName()));
            $tag->setType($type);
            $tags[] = $tag;
        }

        return $tags;
    }
}
