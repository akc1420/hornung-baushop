<?php


namespace Crsw\CleverReachOfficial\Service\BusinessLogic\Translation;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Language\Contracts\TranslationService as BaseTranslationService;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Configuration\ConfigService;
use Exception;

/**
 * Class TranslationService
 *
 * @package Crsw\CleverReachOfficial\Service\BusinessLogic\Translation
 */
class TranslationService implements BaseTranslationService
{
    public const DEFAULT_LANGUAGE = 'en-GB';

    private static $supportedLanguages = ['en-GB', 'de-DE', 'de-AT', 'de-CH', 'es-ES', 'fr-FR', 'it-IT'];

    /**
     * Translates the provided string.
     *
     * @param string $string String to be translated.
     * @param array $arguments List of translation arguments.
     *
     * @return string Translated string.
     */
    public function translate($string, array $arguments = array()): string
    {
        $locale = $this->getLocale();
        $snippets = $this->getSnippets($locale);
        $translatedString = $this->translateString($string, $snippets);

        if (!$translatedString) {
            return vsprintf($string, $arguments);
        }

        return vsprintf($translatedString, $arguments);
    }

    /**
     * Returns current system language
     *
     * @return string
     */
    public function getSystemLanguage(): string
    {
        return $this->getLocale();
    }

    /**
     * @return string
     */
    private function getLocale(): string
    {
        try {
            $locale = $this->getConfigService()->getAdminLanguage();

            return in_array($locale, static::$supportedLanguages, true) ? $locale : static::DEFAULT_LANGUAGE;
        } catch (QueryFilterInvalidParamException $e) {
            Logger::logError("Failed to retrieve admin language from configuration: {$e->getMessage()}");
            return static::DEFAULT_LANGUAGE;
        }
    }

    /**
     * @param $locale
     *
     * @return mixed
     */
    private function getSnippets($locale)
    {
        $snippetsJson = file_get_contents(
            __DIR__ . '/../../../Resources/app/administration/src/module/clever-reach-official/snippet/'
            . $locale
            . '.json'
        );

        return json_decode($snippetsJson, true);
    }

    /**
     * @param $string
     * @param $snippets
     *
     * @return string
     */
    private function translateString($string, $snippets): string
    {
        $stringParts = explode('.', $string);

        try {
            foreach ($stringParts as $part) {
                $snippets = $snippets[$part];
            }
        } catch (Exception $e) {
            $snippets = '';
        }

        return $snippets;
    }

    /**
     * @return ConfigService
     */
    private function getConfigService(): ConfigService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(Configuration::class);
    }
}
