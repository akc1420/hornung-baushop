<?php

namespace Sisi\Search\Service;

/**
 * Class ExtSearchService
 * @package Sisi\Search\Service
 * @SuppressWarnings(PHPMD)
 */
class ExtSearchService
{
    /**
     * @param string|null $term ,
     * @param array $config
     * @return string|null
     */
    public function stripUrl($term, $config)
    {
        if (array_key_exists('strip', $config)) {
            if (!empty($config['strip'])) {
                $charaters = explode("\n", $config['strip']);
                foreach ($charaters as $charater) {
                    $term = str_replace($charater, "", $term);
                }
            }
        }
        return $term;
    }

    public function setAndOperator(array $terms, array $params, array $fields, array $systemConfig, string $match, bool &$str): array
    {
        if ($terms['ma'] != null || $terms['cat'] != null || $terms['pro'] != null) {
            $str = true;
            $return = [
                'index' => $params['index'],
                'from' => $params['from'],
                'size' => $params['size']
            ];
            $newfields = [];

            foreach ($fields as $index => $field) {
                if (array_key_exists($match, $field)) {
                    foreach ($field[$match] as $key => $item) {
                        if ($terms['pro'] !== null && $key === 'properties.option_name' || $terms['ma'] != null && $key === 'manufacturer_name') {
                        } else {
                            $newfields[] = $field;
                        }
                    }
                }
            }

            if (!empty($terms['product'])) {
                $query["bool"]['should'] = $newfields;
                $return['body']['query']['bool']['must'][] = $query;
            }

            if (!empty($terms['ma'])) {
                $Manfacture['bool']['should'][0]['match']['manufacturer_id']['query'] = $terms['ma'];
                $Manfacture['bool']['should'][1]['match']['manufacturer_name']['query'] = $terms['ma'];
                $return['body']['query']['bool']['must'][] = $Manfacture;
            }

            if (!empty($terms['cat'])) {
                $cat["path"] = "categories";
                $cat["query"]["bool"]['should'][0]['match']['categories.category_name'] = trim($terms['cat']);
                $cat["query"]["bool"]['should'][1]['match']['categories.category_id'] = trim($terms['cat']);
                $return['body']['query']['bool']['must'][]["nested"] = $cat;
            }

            if ($terms['pro'] !== null) {
                foreach ($terms['pro'] as $pro) {
                    $properties = [];
                    if (array_key_exists('properties', $systemConfig)) {
                        $properties["path"] = "properties";
                        $properties["query"]["bool"]['should'][0]['match']['properties.option_id'] = trim($pro);
                        $properties["query"]["bool"]['should'][1]['match']['properties.option_name'] = trim($pro);
                    }
                    $heandlervarianten = new VariantenService();
                    if ($heandlervarianten->conditionFunction($systemConfig)) {
                        $propertiesChild["nested"] = $this->mergeFilterQueryChildren($pro);
                        $return['body']['query']['bool']['must'][]["bool"]['should'] = [
                            0 => ["nested" => $properties],
                            1 => $propertiesChild
                        ];
                    } else {
                        $return['body']['query']['bool']['must'][]["nested"] = $properties;
                    }
                }
            }

            if (array_key_exists('highlight', $params['body'])) {
                $return['body']['highlight'] = $params['body']['highlight'];
            }
            return $return;
        }
        return $params;
    }

    private function mergeFilterQueryChildren(string $pro)
    {
        $properties["path"] = "children.properties";
        $properties["query"]["bool"]['should'][0]['match']['children.properties.option_id'] = trim($pro);
        $properties["query"]["bool"]['should'][1]['match']['children.properties.option_name'] = trim($pro);
        $propertiesArray["path"] = "children";
        $propertiesArray["query"]['nested'] = $properties;
        return $propertiesArray;
    }


    public function getHighlightFields(array $fields): array
    {
        $return = [];
        foreach ($fields as $fieldsItem) {
            if (array_key_exists('match', $fieldsItem)) {
                $key = array_key_first($fieldsItem['match']);
                $return[] = [$key => new \stdClass()];
            }
        }
        return $return;
    }

    public function strQueryFields(string $tablename, array $config): bool
    {
        $str = true;
        if ($tablename === 'category' && $config['categorien'] == '2') {
            $str = false;
        }
        if (array_key_exists('properties', $config)) {
            if ($tablename === 'properties' && $config['properties'] == '2') {
                $str = false;
            }
        }
        if ($tablename === 'manufacturer' && $config['manufacturer'] == '2') {
            $str = false;
        }

        return $str;
    }
}
