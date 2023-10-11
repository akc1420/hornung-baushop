<?php

declare(strict_types=1);

namespace salty\Sw6PerformanceAnalysis\Analyzer;

use salty\Sw6PerformanceAnalysis\Struct\Result;
use salty\Sw6PerformanceAnalysis\Struct\ResultCollection;
use salty\Sw6PerformanceAnalysis\Struct\Status;

abstract class Analyzer
{
    abstract public function analyze(ResultCollection $collection): ResultCollection;

    /**
     * @param bool|float|int|string $value
     */
    protected function getResult(
        ResultCollection $collection,
        string $name,
        $value,
        array $requirements
    ): void {
        $status = $this->compareValues($name, $value, $requirements);

        $data = [
            'name'   => $name,
            'value'  => $value,
            'status' => $status,
        ];

        $data   = array_merge_recursive($data, $requirements[$name]);
        $result = (new Result())->assign($data);

        $collection->add($result);
    }

    /**
     * @param int|string $value
     */
    private function compareValues(
        string $name,
        &$value,
        array $requirements
    ): Status {
        $type = Status::STATUS_INFO;

        //determine type of value
        if (is_string($requirements[$name]['suggestedValue'])) {
            $value = (string) $value;
        }

        if (is_int($requirements[$name]['suggestedValue'])) {
            $value = (int) $value;
        }

        //compare values
        if (array_key_exists('minVersion', $requirements[$name])) {
            $type = $this->checkVersionIsGreaterThan((string) $value, $requirements[$name]);
        }

        if (array_key_exists('maxValue', $requirements[$name])) {
            $type = $this->checkValueIsLessOrEqual((int) $value, $requirements[$name]);
        }

        if (array_key_exists('minValue', $requirements[$name])) {
            $type = $this->checkValueIsGreaterOrEqual((int) $value, $requirements[$name]);
        }

        if (array_key_exists('expected', $requirements[$name])) {
            $type = $value === $requirements[$name]['expected'] ? Status::STATUS_PASSED : Status::STATUS_FAILED;
        }

        if (array_key_exists('isNot', $requirements[$name])) {
            $type = $value !== $requirements[$name]['isNot'] ? Status::STATUS_PASSED : Status::STATUS_FAILED;
        }

        //check invalid values afterwards
        if (array_key_exists('invalidValues', $requirements[$name]) && in_array($value, $requirements[$name]['invalidValues'], false)) {
            return new Status(Status::STATUS_FAILED);
        }

        return new Status($type);
    }

    private function checkValueIsGreaterOrEqual(int $value, array $requirements): string
    {
        if ($value >= $requirements['suggestedValue']) {
            return Status::STATUS_PASSED;
        }

        if ($value < $requirements['minValue']) {
            return Status::STATUS_FAILED;
        }

        return Status::STATUS_PASSED_WITH_WARNING;
    }

    private function checkValueIsLessOrEqual(int $value, array $requirements): string
    {
        if ($value <= $requirements['suggestedValue']) {
            return Status::STATUS_PASSED;
        }

        if ($value > $requirements['maxValue']) {
            return Status::STATUS_FAILED;
        }

        return Status::STATUS_PASSED_WITH_WARNING;
    }

    private function checkVersionIsGreaterThan(string $value, array $requirements): string
    {
        if (version_compare($value, $requirements['suggestedValue'], '>=')) {
            return Status::STATUS_PASSED;
        }

        if (version_compare($value, $requirements['minVersion'], '<')) {
            return Status::STATUS_FAILED;
        }

        return Status::STATUS_PASSED_WITH_WARNING;
    }
}
