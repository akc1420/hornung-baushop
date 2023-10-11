<?php declare(strict_types=1);

namespace Recommendy\Manager;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Context;

/**
 * Class CustomFieldManager
 * @package Recommendy\Manager
 */
class CustomFieldManager
{
    /**
     * @param string PREFIX
     */
    public const PREFIX = 'recommendy';

    /**
     * @param array SETS
     */
    public const SETS = [];

    /**
     * @var Context
     */
    private $context;

    /**
     * @var EntityRepository
     */
    private $customFieldSetRepository;

    /**
     * @var EntityRepository
     */
    private $snippetRepository;

    /**
     * @param Context $context
     * @param EntityRepository $customFieldSetRepository
     * @param EntityRepository $snippetRepository
     */
    public function __construct(
        Context $context,
        EntityRepository $customFieldSetRepository,
        EntityRepository $snippetRepository
    )
    {
        $this->context = $context;
        $this->customFieldSetRepository = $customFieldSetRepository;
        $this->snippetRepository = $snippetRepository;
    }

    public function install(): void
    {
        foreach (self::SETS as $SET) {
            try {
                $this->customFieldSetRepository->upsert([$SET], $this->context);
            } catch (\Exception $exception) {

            }
        }
    }

    public function uninstall(): void
    {
        $this->removeCustomFields();
        $this->removeSnippets();
    }

    private function removeCustomFields()
    {
        $customFieldIds = $this->getCustomFieldSetIds();

        if ($customFieldIds->getTotal() === 0) {
            return;
        }

        $ids = array_map(static function ($id) {
            return ['id' => $id];
        }, $customFieldIds->getIds());

        $this->customFieldSetRepository->delete($ids, $this->context);
    }

    /**
     * @return IdSearchResult
     */
    private function getCustomFieldSetIds(): IdSearchResult
    {
        $criteria = new Criteria();
        $criteria->addFilter(new ContainsFilter('name', self::PREFIX));

        return $this->customFieldSetRepository->searchIds($criteria, $this->context);
    }

    private function removeSnippets()
    {
        $snippetIds = $this->getSnippetIds();

        if ($snippetIds->getTotal() === 0) {
            return;
        }

        $ids = array_map(static function ($id) {
            return ['id' => $id];
        }, $snippetIds->getIds());

        $this->snippetRepository->delete($ids, $this->context);
    }

    /**
     * @return IdSearchResult
     */
    private function getSnippetIds(): IdSearchResult
    {
        $criteria = new Criteria();
        $criteria->addFilter(new ContainsFilter('translationKey', self::PREFIX));

        return $this->snippetRepository->searchIds($criteria, $this->context);
    }
}
