<?php

namespace Recommendy\Services;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Psr\Log\LoggerInterface;
use Recommendy\Components\Struct\ActionStruct;
use Recommendy\Services\Interfaces\TrackingServiceInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;

class TrackingService implements TrackingServiceInterface
{
    /** @var Connection */
    private $connection;
    /** @var EntityRepository */
    private $recommendyTrackingRepository;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param Connection $connection
     * @param EntityRepository $recommendyTrackingRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        Connection                $connection,
        EntityRepository $recommendyTrackingRepository,
        LoggerInterface           $logger
    )
    {
        $this->connection = $connection;
        $this->recommendyTrackingRepository = $recommendyTrackingRepository;
        $this->logger = $logger;
    }

    /**
     * @param ActionStruct $struct
     * @param Context $context
     */
    public function handleTracking(ActionStruct $struct, Context $context)
    {
        $identifier = $struct->getIdentifier();
        if (empty($identifier)) {
            $identifier = $this->getIdentifierId($struct->getProductId());
        }

        if(!$identifier) {
            $identifier = "Reco_" . $struct->getProductId();
        }

        try {
            $this->recommendyTrackingRepository->create([[
                'id' => Uuid::randomHex(),
                'action' => $struct->getActionId(),
                'primaryProductId' => $identifier,
                'sessionId' => $struct->getSessionId(),
                'price' => $struct->getPrice(),
                'created' => (new \DateTime())->format('Y-m-d H:i:s')
            ]], $context);
        } catch (\Exception $e) {
            $this->logger->error("TrackingService - handleTracking: {$e->getMessage()}");
        }
    }

    /**
     * @param string $productId
     * @return string|null
     */
    private function getIdentifierId(string $productId): ?string
    {
        $builder = $this->connection->createQueryBuilder();
        $builder->select('identifier')
            ->from('recommendy_identifier', 'ponIdentifier')
            ->where('ponIdentifier.pon = :productid')
            ->setParameter('productid', $productId)
            ->setMaxResults(1);

        try {
            $identifier = $builder->execute()->fetch(\PDO::FETCH_COLUMN);
        } catch (DbalException $e) {
            $this->logger->error("TrackingService - getIdentifierId: {$e->getMessage()}");
        }

        return !empty($identifier) ? $identifier : null;
    }
}
