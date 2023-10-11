<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Language;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Language\Contracts\TranslationService as BaseService;
use Crsw\CleverReachOfficial\Core\Infrastructure\Singleton;

/**
 * Class TranslationService
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Language
 */
abstract class TranslationService extends Singleton implements BaseService
{
    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;

    /**
     * Translates the provided string.
     *
     * @param string $string String to be translated.
     * @param array $arguments List of translation arguments.
     *
     * @return string Translated string.
     */
    public function translate($string, array $arguments = array())
    {
        return vsprintf($string, $arguments);
    }
}
