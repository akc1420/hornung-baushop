<?php

namespace Recommendy\Controller\Api;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as CoreAbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractController extends CoreAbstractController
{
    /** @var EntityRepository */
    protected $entityRepository;
    /** @var Connection */
    protected $connection;
    /** @var LoggerInterface */
    protected $logger;
    /** @var string */
    protected $tableName;

    /**
     * @param EntityRepository $entityRepository
     * @param Connection $connection
     * @param LoggerInterface $logger
     * @param string $tableName
     */
    public function __construct(
        EntityRepository $entityRepository,
        Connection                $connection,
        LoggerInterface           $logger,
        string                    $tableName
    )
    {
        $this->entityRepository = $entityRepository;
        $this->connection = $connection;
        $this->logger = $logger;
        $this->tableName = $tableName;
    }

    /**
     * @param Request $request
     * @param Context $context
     * @return JsonResponse
     */
    public function postAction(Request $request, Context $context): JsonResponse
    {
        $action = $request->get('RecommendyAction');

        if ($action === 'RecommendyDelete') {
            return $this->delete($request, $context);
        }

        if ($action === 'RecommendyInsert') {
            return $this->insert($request, $context);
        }

        return $this->notFound($action);
    }

    /**
     * @param Request $request
     * @param Context $context
     * @return JsonResponse
     */
    protected function insert(Request $request, Context $context): JsonResponse
    {
        $dataInsert = $request->get('Data');

        if (count($dataInsert) > 100 || count($dataInsert) === 0) {
            $errorMessage = "Only 100 Items allowed. Table: {$this->tableName}";
            $this->logger->error($errorMessage);
            return new JsonResponse([
                'success' => false,
                'error' => $errorMessage
            ]);
        }

        $upsertData = $this->generateUpsertData($dataInsert);

        try {
            $this->entityRepository->upsert($upsertData, $context);
        } catch (\Exception $e) {
            $this->logger->error("Couldn't insert to table {$this->tableName}. {$e->getMessage()}");
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }

        return new JsonResponse(['success' => true]);
    }

    /**
     * @param Request $request
     * @param Context $context
     * @return JsonResponse
     */
    protected function delete(Request $request, Context $context): JsonResponse
    {
        if ($request->get('RecommendyDeleteKey') !== 'RecommendyTruncateTable') {
            $errorMessage = "the action RecommendyDelete does not have RecommendyTruncateTable key. Table: {$this->tableName}";
            $this->logger->error($errorMessage);
            return new JsonResponse([
                'success' => false,
                'error' => $errorMessage
            ]);
        }

        $sqlData = 'TRUNCATE ' . $this->tableName;

        try {
            $this->connection->executeStatement($sqlData);
        } catch (Exception $e) {
            $this->logger->error("Couldn't truncate the table {$this->tableName}. {$e->getMessage()}");
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }

        return new JsonResponse(['success' => true]);
    }

    /**
     * @param Request $request
     * @param Context $context
     * @return JsonResponse
     */
    protected function fetchAll(Request $request, Context $context): JsonResponse
    {
        $elements = $this->entityRepository->search(new Criteria(), $context)->getElements();
        return new JsonResponse([
            'success' => true,
            'Data' => $elements
        ]);
    }

    /**
     * @param array $dataInsert
     * @return array
     */
    protected function generateUpsertData(array $dataInsert): array
    {
        $upsertData = [];
        foreach ($dataInsert as $data) {
            array_push($upsertData, [
                'id' => md5($data['P'] . $data['S']. $data['H']),
                'primaryProductId' => $data['P'],
                'secondaryProductId' => $data['S'],
                'similarity' => (float)$data['M'],
                'shop' => $data['H'],
            ]);
        }
        return $upsertData;
    }

    /**
     * @param string|null $action
     * @return JsonResponse
     */
    protected function notFound(?string $action): JsonResponse
    {
        $errorMessage = "RecommendyAction is unknown: {$action}";
        $this->logger->error($errorMessage);
        return new JsonResponse([
            'success' => false,
            'error' => $errorMessage
        ]);
    }
}
