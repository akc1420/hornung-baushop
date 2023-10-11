<?php

namespace Sisi\Search\Service;

class QueryExService
{
    public function addFragnetsize(array &$params, array $config): void
    {
        if (array_key_exists('fragmentsize', $config)) {
            $framisize = (int)$config['fragmentsize'];
            if (!empty($config['fragmentsize']) && $framisize > 0) {
                $params['body']['highlight']['fragment_size'] = $framisize;
            }
        }
    }

    public function getKindOfQuery(array $fields, array $config): array
    {
        if ($config['querykind'] == 'most_fields') {
            $return['bool'] = ['should' => $fields];
            if (array_key_exists('minishouldmatch', $config)) {
                if (!empty($config['minishouldmatch'])) {
                    $return['bool']['minimum_should_match'] = $config['minishouldmatch'];
                }
            }
            return $return;
        }
        if ($config['querykind'] == 'cross_fields') {
            return $this->mergeCrossQuery($fields, $config);
        }

        if ($config['querykind'] === 'and_bool_query') {
            $newfield = $this->splitfieldTo($fields);
            $return['bool']['must'][0]['bool']['should'] = $fields;
            $return['bool']['must'][1] = $newfield;
            return $return;
        }
        $return['dis_max'] = ['queries' => $fields];
        return $return;
    }

    private function mergeCrossQuery(array $fields, array $config): array
    {
        $heandlerQuery = new QueryService();
        $return['bool']['should'][]['multi_match'] = $heandlerQuery->mergeCroosFields($fields, $config);
        $nested = [];
        foreach ($fields as $field) {
            foreach ($field as $index => $fieldItem) {
                if ($index === 'nested') {
                    $nested[] = $fieldItem;
                }
            }
        }
        if (count($nested) > 0) {
            foreach ($nested as $nestedItem) {
                $return['bool']['should'][]['nested'] = $nestedItem;
            }
        }
        return $return;
    }

    public function splitfieldTo(array $fields): array
    {
        $productName = [];
        $value = 'product_name';
        foreach ($fields as $field) {
            foreach ($field['match'] as $key => $match) {
                if (($key === $value)) {
                    $productName["match"][$value] = $match;
                }
            }
        }
        return $productName;
    }
}
