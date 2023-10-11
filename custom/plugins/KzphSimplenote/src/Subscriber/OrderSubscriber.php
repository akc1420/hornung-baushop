<?php

declare(strict_types=1);

namespace Kzph\Simplenote\Subscriber;

use Shopware\Core\Checkout\Order\OrderEvents;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Framework\Uuid\Uuid;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Doctrine\DBAL\Connection;

class OrderSubscriber implements EventSubscriberInterface
{
    private $kzphSimplenoteRepository;
    private $requestStack;
    private $systemConfigService;
    private $connection;
    private $config;

    public function __construct(
        EntityRepository $kzphSimplenoteRepository,
        RequestStack $requestStack,
        Connection $connection,
        ?SystemConfigService $systemConfigService = null
    ) {
        $this->kzphSimplenoteRepository = $kzphSimplenoteRepository;
        $this->requestStack = $requestStack;
        $this->connection = $connection;
        $this->systemConfigService = $systemConfigService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OrderEvents::ORDER_WRITTEN_EVENT => 'onOrderWritten',
        ];
    }

    public function onOrderWritten(EntityWrittenEvent $entityWrittenEvent): void
    {
        $this->_userCommentAction($entityWrittenEvent);
        $this->_replicateAction($entityWrittenEvent);
    }

    private function _userCommentAction($entityWrittenEvent)
    {
        try {
            $config = $this->systemConfigService->get('KzphSimplenote.config');
            $this->config = $config;

            if (($this->config['showNotefieldInCheckout'] ?? false) === false)
                return;

            $writeResults = $entityWrittenEvent->getWriteResults();
            $request = $this->requestStack->getCurrentRequest();

            foreach ($writeResults as $writeResult) {
                $payload = $writeResult->getPayload();

                if (($payload['id'] ?? false)) {
                    if (($request->get('kzphSimpleNote') ?? false)) {
                        $this->kzphSimplenoteRepository->upsert([
                            [
                                'id' => $payload['id'],
                                'entityId' => $payload['id'],
                                'entityType' => 'order',
                                'username' => 'System (Frontend)',
                                'note' => $request->get('kzphSimpleNote'),
                                'showDesktop' => 1,
                                'showMessage' => 0,
                                'replicateInOrder' => 0
                            ],
                        ], $entityWrittenEvent->getContext());

                        return;
                    }
                }
            }
        } catch (\Exception $e) {
        }
    }

    private function _replicateAction($entityWrittenEvent)
    {
        try {
            $config = $this->systemConfigService->get('KzphSimplenote.config');
            $this->config = $config;

            $writeResults = $entityWrittenEvent->getWriteResults();

            foreach ($writeResults as $writeResult) {

                if($writeResult->getEntityName() != 'order')
                    continue;

                if($writeResult->getOperation() != 'insert')
                    continue;

                $payload = $writeResult->getPayload();

                if($payload['versionId'] != '0fa91ce3e96a4bc2be4bd9ce752c3425')
                    continue;

                if (($payload['id'] ?? false)) {
                    foreach ($this->_findAllReplicateMessages($payload['id']) as $oneMessage) {
                        $this->kzphSimplenoteRepository->upsert([
                            [
                                'id' => Uuid::randomHex(),
                                'entityId' => $payload['id'],
                                'entityType' => 'order',
                                'username' => 'System (Backend)',
                                'note' => $oneMessage,
                                'showDesktop' => 0,
                                'showMessage' => 0,
                                'replicateInOrder' => 0
                            ],
                        ], $entityWrittenEvent->getContext());
                    }

                    return;
                }
            }
        } catch (\Exception $e) {
        }
    }

    private function _findAllReplicateMessages($orderId)
    {
        $sqlQuery = 'SELECT IF(CC.replicate_in_order = 1, CC.note, "") AS customerNote, IF(LIC.replicate_in_order = 1, LIC.note, "") AS lineItemNote FROM `order` A
        LEFT JOIN `order_customer` B ON A.id = B.order_id AND A.version_id = UNHEX("0FA91CE3E96A4BC2BE4BD9CE752C3425")
        LEFT JOIN `order_line_item` C ON A.id = C.order_id AND C.version_id = A.version_id
        LEFT JOIN `kzph_simplenote` CC ON CC.entity_id = B.customer_id
        LEFT JOIN `kzph_simplenote` LIC ON LIC.entity_id = C.product_id
        WHERE (CC.replicate_in_order = 1 OR LIC.replicate_in_order = 1) AND A.id = :orderId';
        $sqlResult = $this->connection->executeQuery($sqlQuery, ['orderId' => hex2bin($orderId)]);

        $notes = $sqlResult->fetchAllAssociative();
        $noteStack = array();
        foreach ($notes as $oneNote) {
            if ($oneNote['customerNote'])
                $noteStack[md5($oneNote['customerNote'])] = $oneNote['customerNote'];

            if ($oneNote['lineItemNote'])
                $noteStack[md5($oneNote['lineItemNote'])] = $oneNote['lineItemNote'];
        }

        return $noteStack;
    }
}
