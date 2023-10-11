<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

//use Shopware\Core\Framework\Context;
use Doctrine\DBAL\Connection;
use Cbax\ModulAnalytics\Components\Base;
use Shopware\Core\Framework\Uuid\Uuid;

class LexiconImpressions
{
    private $config;
    private $base;
    private $connection;

    public function __construct(
        $config,
        Base $base,
        Connection $connection
    )
    {
        $this->config = $config;
        $this->base = $base;
        $this->connection = $connection;
    }

    public function getLexiconImpressions($parameters, $context)
    {
        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);

        $qb = $this->connection->createQueryBuilder();
        $query = $qb
            ->select([
                'DISTINCT HEX(lexicon.id) as id',
                'lexicon.impressions as count',
                'clet.title as title',
                'altclet.title as altTitle'
            ])
            ->from('`cbax_lexicon_entry`', 'lexicon')
            ->leftJoin('lexicon', '`cbax_lexicon_entry_translation`', 'clet',
                'lexicon.id = clet.cbax_lexicon_entry_id AND clet.language_id = UNHEX(:language)')
            ->leftJoin('lexicon', '`cbax_lexicon_entry_translation`', 'altclet',
                'lexicon.id = altclet.cbax_lexicon_entry_id AND altclet.language_id = UNHEX(:altLanguage)')
            ->setParameters([
                'language' => $languageId,
                'altLanguage' => $context->getLanguageId()
            ])
            ->orderBy('count', 'DESC');
        
        if (!empty($parameters['salesChannelIds']))
        {
            array_walk($parameters['salesChannelIds'],
                function (&$value) { $value = Uuid::fromHexToBytes($value); }
            );

            $query->leftJoin('lexicon', '`cbax_lexicon_sales_channel`', 'clsc',
                    'lexicon.id = clsc.cbax_lexicon_entry_id AND clsc.sales_channel_id IN (:salesChannels)')
                ->andWhere('clsc.id IS NOT NULL')
                ->setParameter('salesChannels', $parameters['salesChannelIds'], Connection::PARAM_STR_ARRAY);
        }

        $data = $query->execute()->fetchAll();

        $sortedData = [];

        foreach($data as $entry)
        {
            $sortedData[] = [
                'id' => $entry['id'],
                'name' => $entry['title'] ?? $entry['altTitle'],
                'count' => (int)$entry['count']
            ];
        }

        if ($parameters['format'] === 'csv') {
            return $this->base->exportCSV($sortedData, $parameters['labels']);
        }

        $overall = array_sum(array_column($data, 'count'));

        $seriesData = $this->base->limitData($sortedData, $this->config['chartLimit']);
        $gridData   = $this->base->limitData($sortedData, $this->config['gridLimit']);

        return ['gridData' => $gridData, 'seriesData' => $seriesData, 'overall' => $overall];
    }
}


