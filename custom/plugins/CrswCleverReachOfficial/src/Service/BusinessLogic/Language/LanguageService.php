<?php


namespace Crsw\CleverReachOfficial\Service\BusinessLogic\Language;

use Crsw\CleverReachOfficial\Entity\Language\Repositories\LanguageRepository;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\Language\LanguageCollection;

/**
 * Class LanguageService
 *
 * @package Crsw\CleverReachOfficial\Service\BusinessLogic\Language
 */
class LanguageService
{
    /**
     * @var LanguageRepository
     */
    private $languageRepository;

    /**
     * LanguageService constructor.
     *
     * @param LanguageRepository $languageRepository
     */
    public function __construct(LanguageRepository $languageRepository)
    {
        $this->languageRepository = $languageRepository;
    }

    /**
     * Gets languages.
     *
     * @param Context $context
     *
     * @return LanguageCollection
     */
    public function getLanguages(Context $context): LanguageCollection
    {
        return $this->languageRepository->getLanguages($context);
    }
}