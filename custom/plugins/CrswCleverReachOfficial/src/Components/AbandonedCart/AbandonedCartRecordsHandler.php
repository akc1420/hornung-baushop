<?php


namespace Crsw\CleverReachOfficial\Components\AbandonedCart;

use Crsw\CleverReachOfficial\Components\AbandonedCart\DTO\AbandonedCartRecordsRequestPayload;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Contracts\RecoveryEmailStatus;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Entities\AutomationRecord;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Entities\CartAutomation;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToDeleteAutomationRecordException;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Services\AutomationRecordService;
use Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Services\CartAutomationService;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\Operators;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\RepositoryRegistry;
use Crsw\CleverReachOfficial\Entity\Cart\Repositories\CartRepository;
use Crsw\CleverReachOfficial\Entity\Language\Repositories\LanguageRepository;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Automation\AutomationService;
use Shopware\Core\Framework\Context;

class AbandonedCartRecordsHandler
{
    private const RECOVERED = 1;
    private const NOT_RECOVERED = 0;

    /**
     * @var string[][]
     */
    private static $statusLabels = [
        RecoveryEmailStatus::SENT => [
            'en-GB' => 'Sent',
            'de-DE' => 'Versendet',
            'es-ES' => 'Sent',
            'fr-FR' => 'Sent',
            'it-IT' => 'Sent',
        ],
        RecoveryEmailStatus::NOT_SENT => [
            'en-GB' => 'Not sent',
            'de-DE' => 'Nicht gesendet',
            'es-ES' => 'Not sent',
            'fr-FR' => 'Not sent',
            'it-IT' => 'Not sent',
        ],
        RecoveryEmailStatus::PENDING => [
            'en-GB' => 'Pending',
            'de-DE' => 'Ausstehend',
            'es-ES' => 'Pending',
            'fr-FR' => 'Pending',
            'it-IT' => 'Pending',
        ],
        RecoveryEmailStatus::SENDING => [
            'en-GB' => 'Sending',
            'de-DE' => 'Wird versendet',
            'es-ES' => 'Sending',
            'fr-FR' => 'Sending',
            'it-IT' => 'Sending',
        ],
        self::RECOVERED => [
            'en-GB' => 'Recovered',
            'de-DE' => 'Wiederhergestellt',
            'es-ES' => 'Recovered',
            'fr-FR' => 'Recovered',
            'it-IT' => 'Recovered',
        ],
        self::NOT_RECOVERED => [
            'en-GB' => 'Not recovered',
            'de-DE' => 'Nicht wiederhergestellt',
            'es-ES' => 'Not recovered',
            'fr-FR' => 'Not recovered',
            'it-IT' => 'Not recovered',
        ],
    ];
    /**
     * @var AutomationRecordService
     */
    private $automationRecordsService;
    /**
     * @var CartRepository
     */
    private $cartRepository;
    /**
     * @var LanguageRepository
     */
    private $languageRepository;
    private $cartAutomationService;


    /**
     * AbandonedCartRecordsHandler constructor.
     *
     * @param AutomationRecordService $automationRecordsService
     * @param CartRepository $cartRepository
     * @param LanguageRepository $languageRepository
     * @param CartAutomationService $cartAutomationService
     */
    public function __construct(
        AutomationRecordService $automationRecordsService,
        CartRepository $cartRepository,
        LanguageRepository $languageRepository,
        CartAutomationService $cartAutomationService
    ) {
        $this->automationRecordsService = $automationRecordsService;
        $this->cartRepository = $cartRepository;
        $this->languageRepository = $languageRepository;
        $this->cartAutomationService = $cartAutomationService;
    }

    /**
     * @param AbandonedCartRecordsRequestPayload $payload
     *
     * @return array
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     */
    public function getRecords(AbandonedCartRecordsRequestPayload $payload)
    {
        $records = $this->automationRecordsService->filter($this->createQueryFilter($payload));

        $results = [];
        $statusLabelsMap = $this->getStatusLabelsMap();
        $cartAutomationMap = $this->getCartAutomationMap();
        foreach ($records as $record) {
            $formattedRecord = $record->toArray();
            $formattedRecord['status'] = [
                'value' => $record->getStatus(),
                'translations' => $statusLabelsMap[$record->getStatus()],
            ];

            $formattedRecord['isRecovered'] = [
                'value' => (int)$record->getIsRecovered(),
                'translations' => $statusLabelsMap[(int)$record->getIsRecovered()],
            ];

            $this->setCartInformation($record->getCartId(), $formattedRecord);
            $automationId = $record->getAutomationId();
            $automationName = !empty($cartAutomationMap[$automationId]) ? $cartAutomationMap[$automationId]->getName() : $formattedRecord['salesChannel'];
            $formattedRecord['salesChannel'] = str_replace(AutomationService::THEA_TITLE_PREFIX, '', $automationName);

            $results[] = $formattedRecord;
        }

        return $results;
    }

    /**
     * Counts all records
     *
     * @return int
     *
     * @throws RepositoryNotRegisteredException
     */
    public function countRecords(): int
    {
        return RepositoryRegistry::getRepository(AutomationRecord::getClassName())->count();
    }

    /**
     * @param string $recordId
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Exceptions\FailedToTriggerAutomationRecordException
     */
    public function trigger(string $recordId)
    {
        $this->automationRecordsService->triggerRecord($recordId);
    }

    /**
     * @param string $recordId
     *
     * @throws FailedToDeleteAutomationRecordException
     */
    public function delete(string $recordId)
    {
        $this->automationRecordsService->delete($recordId);
    }

    /**
     * @param string $cartId
     * @param array $record
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function setCartInformation(string $cartId, array &$record)
    {
        $cartInfo = $this->cartRepository->getCart($cartId);

        if ($cartInfo) {
            $record['amount'] = number_format($cartInfo['price'], 2, ',', '.') . ' ' . $cartInfo['symbol'];
            $record['salesChannel'] = $cartInfo['name'];
        }
    }

    /**
     * @param AbandonedCartRecordsRequestPayload $payload
     *
     * @return QueryFilter
     * @throws QueryFilterInvalidParamException
     */
    private function createQueryFilter(AbandonedCartRecordsRequestPayload $payload): QueryFilter
    {
        $queryFilter = new QueryFilter();
        foreach ($payload->getFilters() as $column => $value) {
            if ($value !== null) {
                $queryFilter->where($column, Operators::EQUALS, $value);
            }
        }

        if (!empty($payload->getTerm())) {
            $queryFilter->where('email', Operators::LIKE, '%' . $payload->getTerm() . '%');
        }

        $queryFilter->setLimit($payload->getLimit());
        $offset = ($payload->getPage() - 1) * $payload->getLimit();
        $queryFilter->setOffset($offset);

        $sortBy = $payload->getSortBy();
        if ($sortBy === 'salesChannel') {
            $sortBy = 'automationId';
        }

        $queryFilter->orderBy($sortBy, $payload->getSortDirection());

        return $queryFilter;
    }

    /**
     * @return array
     */
    private function getStatusLabelsMap(): array
    {
        $languages = $this->languageRepository->getLanguages(Context::createDefaultContext());
        $languageMap = [];

        foreach ($languages as $language) {
            $languageMap[$language->getTranslationCode()->getCode()] = $language->getId();
        }

        $statusLabels = [];
        foreach (static::$statusLabels as $status => $statusLanguages) {
            $statusLabels[$status] = $this->mapToLanguageId($statusLanguages, $languageMap);
        }


        return $statusLabels;
    }

    /**
     * @param array $statusLanguages
     * @param array $languageMap
     *
     * @return array
     */
    private function mapToLanguageId(array $statusLanguages, array $languageMap): array
    {
        $langIdMap = [];
        foreach ($statusLanguages as $code => $translation) {
            $id = array_key_exists($code, $languageMap) ? $languageMap[$code] : $languageMap['en-GB'];
            $langIdMap[$id] = $translation;
        }

        return $langIdMap;
    }

    /**
     * @return CartAutomation[] in format [id => CartAutomation]
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     */
    private function getCartAutomationMap(): array
    {
        $map = [];
        $cartAutomations = $this->cartAutomationService->findBy([]);
        foreach ($cartAutomations as $cartAutomation) {
            $map[$cartAutomation->getId()] = $cartAutomation;
        }

        return $map;
    }
}
