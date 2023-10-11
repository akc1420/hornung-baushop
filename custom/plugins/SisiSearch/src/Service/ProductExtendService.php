<?php

namespace Sisi\Search\Service;

use Doctrine\DBAL\Connection;
use phpDocumentor\Reflection\Types\Boolean;
use Sisi\Search\Core\Content\Fields\Bundle\DBFieldsEntity;
use Sisi\Search\Service\ProductMoreService;

class ProductExtendService
{

    public function setEdgeSeparatorConfig(DBFieldsEntity $entity, string $tokenizer, array &$return): void
    {
        if ($entity->getPunctuation() == 'yes') {
            $return['analysis']['tokenizer'][$tokenizer]["token_chars"][] = "punctuation";
        }

        if ($entity->getWhitespace() == 'yes') {
            $return['analysis']['tokenizer'][$tokenizer]["token_chars"][] = "whitespace";
        }
    }

    public function mergeOwnStopWordsValues(bool $str, string $name, array &$filtervalues): void
    {
        if ($str) {
            $filterName = "stop_own_" . $name;
            $filtervalues[] = $filterName;
        }
    }

    /**
     * @param string|null $valuestring
     * @param string $name
     * @param array $return
     * @return bool
     */
    public function mergeOwnStopWords($valuestring, $name, &$return)
    {
        if (!empty($valuestring) && $valuestring != null) {
            $filterName = "stop_own_" . $name;
            if (strpos($valuestring, "file=") !== false) {
                $pfad = str_replace("file=", "", $valuestring);
                $valuestring = file_get_contents($pfad);
            }
            $stopswords = explode("\n", $valuestring);
            if (count($stopswords) === 1) {
                $valuestring = str_replace("\n", "", $valuestring);
                $stopswords = explode(",", $valuestring);
            }
            $stopswords = array_filter($stopswords);
            $return['analysis']['filter'][$filterName] = [
                "type" => "stop",
                "stopwords" => $stopswords
            ];
            return true;
        }
        return false;
    }


    public function mergeStopWordsValues(bool $str, string $name, array &$filtervalues): void
    {
        if ($str) {
            $filterName = "stop_" . $name;
            $filtervalues[] = $filterName;
        }
    }

    public function mergeStopWords(string $str, string $stemming, string $name, array &$return): bool
    {
        $extend2haendler = new  ProductMoreService();
        $stopswords = $extend2haendler->checkstopWort($stemming);
        if ($str == 'yes' && !empty($stopswords)) {
            $filterName = "stop_" . $name;
            $return['analysis']['filter'][$filterName] = [
                "type" => "stop",
                "stopwords" => $stopswords
            ];
            return true;
        }
        return false;
    }

    public function mergeStemmigFilterValues(bool $str, string $stemming, array &$filtervalues): void
    {
        if ($str) {
            $filterName = "stemmer_" . $stemming;
            $filtervalues[] = $filterName;
        }
    }

    public function mergeStemmigFilter(string $stemming, array &$return): bool
    {
        if (!empty($stemming) && $stemming != 'noselect') {
            $filterName = "stemmer_" . $stemming;
            $return['analysis']['filter'][$filterName] = [
                "type" => "stemmer",
                "language" => $stemming
            ];
            return true;
        }
        return false;
    }

    public function getSynonymvalue(DBFieldsEntity $entity): array
    {
        $values = explode("\n", $entity->getSynonym());
        $pos = strpos($values[0], "file=");
        // Read in Filter
        if ($pos !== false) {
            $pfad = str_replace("file=", "", $values[0]);
            return file(__DIR__ . DIRECTORY_SEPARATOR . $pfad);
        }
        return $values;
    }

    public function mergeSynonymFilter(string $filter, array $values, string $name, array &$return): string
    {
        if ($filter == 'synonym' && count($values) > 0) {
            $filter = $name . '_' . 'synonym';
            $return['analysis']['filter'][$filter] = [
                "type" => "synonym",
                "synonyms" => $values
            ];
        }
        return $filter;
    }

    /**
     * @param string $filter1
     * @param string $filter2
     * @param string $filter3
     * @param array $filtervalues
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function mergeFiltervalues(string $filter1, string $filter2, string $filter3, array &$filtervalues): void
    {
        if (!empty($filter1) && $filter1 != 'noselect' && $filter1 != 'shingle') {
            $filtervalues[] = $filter1;
        }
        if (!empty($filter2) && $filter2 != 'noselect' && $filter1 != 'shingle') {
            $filtervalues[] = $filter2;
        }
        if (!empty($filter3) && $filter3 != 'noselect' && $filter1 != 'shingle') {
            $filtervalues[] = $filter3;
        }
    }

    public function removeEmptyElemnetsFromArray(array $valuesGlobal): array
    {
        return array_filter(
            $valuesGlobal,
            function ($value) {
                if ($value !== '') {
                    return $value;
                }
            }
        );
    }

    public function getGlobalsSynom(array $config, array &$return): string
    {
        $filterGobal = '';
        if (array_key_exists("synom", $config)) {
            $valuesGlobal = explode("\n", trim($config["synom"]));
            $valuesGlobal = $this->removeEmptyElemnetsFromArray($valuesGlobal);
            if (!empty($valuesGlobal)) {
                $filterGobal = $this->mergeSynonymFilter('synonym', $valuesGlobal, "global_synonym", $return);
            }
        }
        return $filterGobal;
    }
}
