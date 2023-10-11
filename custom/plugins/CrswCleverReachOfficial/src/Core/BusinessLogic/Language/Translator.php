<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Language;

use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;

/**
 * Class Translator
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Language
 */
class Translator
{
    /**
     * @var \Crsw\CleverReachOfficial\Core\BusinessLogic\Language\Contracts\TranslationService
     */
    protected static $translationService;

    /**
     * Translates provided string.
     *
     * @param string $string String to be translated.
     * @param array $arguments List of translation arguments.
     *
     * @return string Translated string.
     */
    public static function translate($string, array $arguments = array())
    {
        return self::getTranslationService()->translate($string, $arguments);
    }

    /**
     * Retrieves translation service.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Language\Contracts\TranslationService
     */
    protected static function getTranslationService()
    {
        if (self::$translationService === null) {
            self::$translationService = ServiceRegister::getService(TranslationService::CLASS_NAME);
        }

        return self::$translationService;
    }

    /**
     * Resets translation service instance.
     */
    public static function resetInstance()
    {
        static::$translationService = null;
    }
}