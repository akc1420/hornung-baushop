<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Utility\Random;

/**
 * Class RandomString
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Utility\Random
 */
class RandomString
{
    private static $CHAR_SET = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * Generates a random string
     *
     * @param int $length
     *
     * @return string
     */
    public static function generate($length = 32)
    {
        $result = '';

        $charSetLength = strlen(static::$CHAR_SET);
        for ($i = 0; $i < $length; $i++) {
            /** @noinspection RandomApiMigrationInspection */
            $result .= static::$CHAR_SET[rand(0, $charSetLength - 1)];
        }

        return $result;
    }
}
