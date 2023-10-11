<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Language\Contracts;

/**
 * Interface TranslationService
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Language\Contracts
 */
interface TranslationService
{
    /**
     * Class name.
     */
    const CLASS_NAME = __CLASS__;

    /**
     * Translates the provided string.
     *
     * @param string $string String to be translated.
     * @param array $arguments List of translation arguments.
     *
     * @return string Translated string.
     */
    public function translate($string, array $arguments = array());

    /**
     * Returns current system language
     *
     * @return string
     */
    public function getSystemLanguage();

}