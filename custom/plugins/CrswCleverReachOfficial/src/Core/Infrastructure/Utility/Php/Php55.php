<?php

namespace Crsw\CleverReachOfficial\Core\Infrastructure\Utility\Php;

/**
 * Used from symfony/polyfill-php55.
 *
 * @internal
 */
class Php55
{
    /**
     * Return the values from a single column in the input array.
     *
     * @link https://secure.php.net/manual/en/function.array-column.php
     *
     * @param array $input <p>A multi-dimensional array (record set) from which to pull a column of values.</p>
     * @param mixed $columnKey <p>The column of values to return. This value may be the integer key of the
     *  column you wish to retrieve, or it may be the string key name for an associative array.
     *  It may also be NULL to return complete arrays (useful together with index_key to reindex the array).</p>
     * @param mixed $indexKey [optional] <p>The column to use as the index/keys for the returned array.
     *  This value may be the integer key of the column, or it may be the string key name.</p>
     *
     * @return array Returns an array of values representing a single column from the input array.
     */
    public static function arrayColumn(array $input, $columnKey, $indexKey = null)
    {
        if (function_exists('array_column')) {
            return array_column($input, $columnKey, $indexKey);
        }

        return self::getArrayColumn($input, $columnKey, $indexKey);
    }

    /**
     * Return the values from a single column in the input array.
     *
     * @link https://secure.php.net/manual/en/function.array-column.php
     *
     * @param array $input <p>A multi-dimensional array (record set) from which to pull a column of values.</p>
     * @param mixed $columnKey <p>The column of values to return. This value may be the integer key of the
     *  column you wish to retrieve, or it may be the string key name for an associative array.
     *  It may also be NULL to return complete arrays (useful together with index_key to reindex the array).</p>
     * @param mixed $indexKey [optional] <p>The column to use as the index/keys for the returned array.
     *  This value may be the integer key of the column, or it may be the string key name.</p>
     *
     * @return array Returns an array of values representing a single column from the input array.
     */
    private static function getArrayColumn(array $input, $columnKey, $indexKey = null)
    {
        $output = array();

        foreach ($input as $row) {
            $key = $value = null;
            $keySet = $valueSet = false;

            if (null !== $indexKey && \array_key_exists($indexKey, $row)) {
                $keySet = true;
                $key = (string)$row[$indexKey];
            }

            if (null === $columnKey) {
                $valueSet = true;
                $value = $row;
            } elseif (\is_array($row) && \array_key_exists($columnKey, $row)) {
                $valueSet = true;
                $value = $row[$columnKey];
            }

            if ($valueSet) {
                if ($keySet) {
                    $output[$key] = $value;
                } else {
                    $output[] = $value;
                }
            }
        }

        return $output;
    }
}
