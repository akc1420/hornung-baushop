<?php

namespace Sisi\Search\Service;

use Sisi\Search\Core\Content\Fields\Bundle\DBFieldsEntity;
use Elasticsearch\Client;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class QueryService
{

    public function mergeFields(
        int &$index,
        array &$fields,
        string $match,
        string $search,
        DBFieldsEntity $row,
        string $name,
        array $terms = []
    ): void {
        $fields[$index] = $this->checkIsOnlyMain($row, $search, $match, $name, $terms);
        $index++;
    }

    private function checkIsOnlyMain(DBFieldsEntity $row, string $search, string $match, string $name, array $terms)
    {
        $lenient = $row->getLenient();
        $prefixLength = $row->getPrefixlength();
        $autosynonyms = $row->getAutosynonyms();
        $minimumshouldmatch = $row->getMinimumshouldmatch();
        $operator = $row->getOperator();
        $str = $this->checkisFilterQuery($terms);
        $sonderValues = $row->getPhpfilter();
        $sonderValuesArray = explode("\n", $sonderValues);
        if (count($sonderValuesArray) > 0) {
            foreach ($sonderValuesArray as $sonderValueitem) {
                $search  = str_replace($sonderValueitem, "", $search);
            }
        }
        if ($row->getOnlymain() === 'yes' && $str) {
            $return['nested']['path'] = $name . "nest";
            $return['nested']["query"]["bool"]['must'][0][$match][$name . "nest." . $name]["query"] = $search;
            $return["nested"]["query"]["bool"]['must'][1][$match][$name . "nest.onlymain"]["query"]  = '1';
            $this->mergeFieldsFirstpart($return['nested']["query"]["bool"]['must'][0][$match][$name . "nest." . $name], $row, $match);
            $this->mergeFieldsSecondpart($return['nested']["query"]["bool"]['must'][0][$match][$name . "nest." . $name], $autosynonyms, $lenient, $prefixLength, $match);
            $this->mergeFieldsthreepart($return['nested']["query"]["bool"]['must'][0][$match][$name . "nest." . $name], $minimumshouldmatch, $operator, $match);
        } else {
            $return = $this->meregeFieldsStart($row, $search, $match, $name);
        }

        return $return;
    }

    private function meregeFieldsStart(DBFieldsEntity $row, string $search, string $match, string $name)
    {
        $lenient = $row->getLenient();
        $prefixLength = $row->getPrefixlength();
        $autosynonyms = $row->getAutosynonyms();
        $minimumshouldmatch = $row->getMinimumshouldmatch();
        $operator = $row->getOperator();
        $return[$match][$name]["query"] = $search;
        $this->mergeFieldsFirstpart($return[$match][$name], $row, $match);
        $this->mergeFieldsSecondpart($return[$match][$name], $autosynonyms, $lenient, $prefixLength, $match);
        $this->mergeFieldsthreepart($return[$match][$name], $minimumshouldmatch, $operator, $match);
        return $return;
    }

    private function checkisFilterQuery(array $terms): bool
    {
        $str = true;
        if (array_key_exists('pro', $terms)) {
            if (!empty($terms['pro'])) {
                $str = false;
            }
        }
        if (array_key_exists('cat', $terms)) {
            if (!empty($terms['cat'])) {
                $str = false;
            }
        }
        if (array_key_exists('ma', $terms)) {
            if (!empty($terms['ma'])) {
                $str = false;
            }
        }

        if (array_key_exists('ra', $terms)) {
            if (!empty($terms['ra'])) {
                $str = false;
            }
        }
        return $str;
    }

    private function mergeFieldsFirstpart(
        array &$fields,
        DBFieldsEntity $row,
        string $match
    ): void {
        $boost = $row->getBooster();
        $fuzzy = $row->getFuzzy();
        $max = $row->getMaxexpansions();
        $slop = $row->getSlop();
        if (!empty($boost)) {
            $fields["boost"] = $boost;
        }
        if (!empty($fuzzy) && $match === 'match') {
            $fields["fuzziness"] = $fuzzy;
        }
        if (!empty($max) && $match === 'match') {
            $fields["max_expansions"] = $max;
        }
        if (!empty($slop) && ($match === 'match_phrase_prefix' || $match === 'match_phrase')) {
            $fields["slop"] = $slop;
        }
    }

    private function mergeFieldsSecondpart(
        array &$fields,
        string $autosynonyms,
        string $lenient,
        string $prefixLength,
        string $match
    ): void {
        if (!empty($autosynonyms) && $autosynonyms === 'no' && $match === 'match') {
            $fields["auto_generate_synonyms_phrase_query"] = false;
        }

        if (!empty($lenient) && $lenient == 'yes' && $match === 'match') {
            $fields["lenient"] = true;
        }

        if (!empty($prefixLength) && $match === 'match') {
            $fields["prefix_length"] = $prefixLength;
        }
    }

    private function mergeFieldsthreepart(
        array &$fields,
        string $minimumshouldmatch,
        string $operator,
        string $match
    ): void {
        if (!empty($operator) && $operator === 'and' && $match === 'match') {
            $fields["operator"] = $operator;
        }
        if (!empty($minimumshouldmatch) && $match === 'match') {
            $fields["minimum_should_match"] = $minimumshouldmatch;
        }
    }

    public function getTheKindOfMatch(array $config): string
    {
        if (array_key_exists('querykind', $config)) {
            if ($config['querykind'] === 'phrase_prefix') {
                return "match_phrase_prefix";
            }
            if ($config['querykind'] === 'phrase') {
                return "match_phrase";
            }
        }
        return "match";
    }

    public function getQuery(array $index, array $fields, array $config, int $from = null, int $size = null): array
    {
        $params = $this->bestFields($index, $fields, $config);
        $heandlerExQuery = new QueryExService();


        if ($from != null) {
            $params["from"] = $from;
        } else {
            $params["from"] = 0;
        }

        if ($size != null) {
            $params["size"] = $size;
        }
        if (array_key_exists('tiebreaker', $config)) {
            if (array_key_exists('querykind', $config)) {
                if (($config['querykind'] === 'best_fields')) {
                    $this->setTiebrake($params, $config);
                }
            } else {
                $this->setTiebrake($params, $config);
            }
        }
        if (array_key_exists('minScore', $config)) {
            if (!empty($config['minScore'])) {
                $params['body']['min_score'] = $config['minScore'];
            }
        }
        $heandlerExQuery->addFragnetsize($params, $config);


        return $params;
    }

    private function setTiebrake(array &$params, array $config): void
    {
        if (!empty($config['tiebreaker'])) {
            $params['body']['query']['dis_max']['tie_breaker'] = $config['tiebreaker'];
        }
    }

    public function bestFields(array $index, array $fields, array $config): array
    {
        $heandler = new ExtSearchService();
        $heandlerExQuery = new QueryExService();
        return [
            'index' => $index['index'],
            'body' => [
                'query' => $heandlerExQuery->getKindOfQuery($fields, $config),
                'highlight' => [
                    'pre_tags' => ["<em>"], // not required
                    'post_tags' => ["</em>"], // not required
                    'fields' => $heandler->getHighlightFields($fields),
                    'require_field_match' => false
                ]
            ]
        ];
    }
    /**
     * @SuppressWarnings("PMD.CyclomaticComplexity")
     */
    public function mergeCroosFields(array $fields, array $config): array
    {
        $query = '';
        $fiedsvalues = [];
        foreach ($fields as $key => $field) {
            foreach ($field as $index => $match) {
                if ($index !== 'nested') {
                    foreach ($match as $index2 => $fieldname) {
                        $fiedsvalues[$key] = $index2;
                        if ($key == 0) {
                            $query = $fieldname['query'];
                        }
                        if (is_array($fieldname)) {
                            if (array_key_exists('boost', $fieldname)) {
                                $fiedsvalues[$key] .= "^" . $fieldname['boost'];
                            }
                        }
                    }
                }
            }
        }
        $return = [
            "query" => $query,
            "type" => "cross_fields",
            "fields" => $fiedsvalues
        ];
        if (array_key_exists('minishouldmatch', $config)) {
            if (!empty($config['minishouldmatch'])) {
                $return['minimum_should_match'] = $config['minishouldmatch'];
            }
        }
        return $return;
    }
}
