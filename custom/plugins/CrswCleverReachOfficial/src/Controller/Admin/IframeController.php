<?php

namespace Crsw\CleverReachOfficial\Controller\Admin;

use Crsw\CleverReachOfficial\Components\Utility\Bootstrap;
use Crsw\CleverReachOfficial\Components\Utility\Initializer;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Logger;
use Crsw\CleverReachOfficial\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Authorization\AuthorizationService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Configuration\ConfigService;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Api\Response\JsonApiResponse;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class IframeController
 *
 * @package Crsw\CleverReachOfficial\Controller\Admin
 */
class IframeController extends AbstractController
{
    /**
     * @var AuthorizationService
     */
    private $authService;
    /**
     * @var EntityRepository
     */
    private $userRepository;
    /**
     * @var EntityRepository
     */
    private $localeRepository;

    /**
     * IframeController constructor.
     *
     * @param Initializer $initializer
     * @param AuthorizationService $authService
     * @param EntityRepository $userRepository
     * @param EntityRepository $localeRepository
     */
    public function __construct(
        Initializer $initializer,
        AuthorizationService $authService,
        EntityRepository $userRepository,
        EntityRepository $localeRepository
    ) {
        Bootstrap::init();
        $initializer->registerServices();

        $this->authService = $authService;
        $this->userRepository = $userRepository;
        $this->localeRepository = $localeRepository;
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(path="api/v{version}/cleverreach/iframe/url/{type}",
     *     name="api.cleverreach.iframe.url", methods={"GET", "POST"})
     * @Route(path="/api/cleverreach/iframe/url/{type}", name="api.cleverreach.iframe.url.new", methods={"GET", "POST"})
     *
     * @param string $type
     * @param Request $request
     *
     * @return JsonApiResponse
     */
    public function getAuthUrl(string $type, Request $request): JsonApiResponse
    {
        $lang = $this->getAdminLanguage($request);
        $this->saveAdminLanguage($lang);

        $authUrl = $this->authService->getAuthIframeUrl(
            $this->formatLanguageCodeForCleverReach($lang),
            $type === 'refresh' ?: false
        );

        return new JsonApiResponse(['authUrl' => $authUrl]);
    }

    /**
     * @param string $shopwareCode
     *
     * @return string
     */
    private function formatLanguageCodeForCleverReach(string $shopwareCode): string
    {
        $codeArray = explode('-', $shopwareCode);

        return array_key_exists(0, $codeArray) ? $codeArray[0] : 'en';
    }

    /**
     * @param Request $request
     * @return string
     */
    private function getAdminLanguage(Request $request): string
    {
        $contextSource = $request->get('sw-context')->getSource();

        if (!($contextSource instanceof AdminApiSource)) {
            return 'en-GB';
        }

        $userId = $contextSource->getUserId();
        $localeId = $this->userRepository->search(new Criteria([$userId]), $request->get('sw-context'))
            ->first()->getLocaleId();

        return $this->localeRepository->search(new Criteria([$localeId]), $request->get('sw-context'))
            ->first()->getCode();
    }

    /**
     * @param string|null $locale
     */
    private function saveAdminLanguage(?string $locale): void
    {
        if (!$locale) {
            return;
        }

        try {
            $this->getConfigService()->saveAdminLanguage($locale);
        } catch (QueryFilterInvalidParamException $e) {
            Logger::logError($e->getMessage());
        }
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
