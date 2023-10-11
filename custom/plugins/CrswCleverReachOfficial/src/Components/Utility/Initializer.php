<?php


namespace Crsw\CleverReachOfficial\Components\Utility;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Order\Contracts\OrderService;
use Crsw\CleverReachOfficial\Core\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Entity\Currency\Repositories\CurrencyRepository;
use Crsw\CleverReachOfficial\Entity\Customer\Repositories\CustomerRepository;
use Crsw\CleverReachOfficial\Entity\Product\Repositories\ProductRepository;
use Crsw\CleverReachOfficial\Entity\SalesChannel\Repositories\SalesChannelRepository;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Customer\BuyerService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Customer\ContactService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Customer\SubscriberService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Language\LanguageService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Search\ProductSearchService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Tag\TagService;
use Crsw\CleverReachOfficial\Service\BusinessLogic\Uninstall\UninstallService;
use Crsw\CleverReachOfficial\Service\Infrastructure\LoggerService;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class Initializer
 * @package Crsw\CleverReachOfficial\Components\Utility
 */
class Initializer
{
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var EntityRepositoryInterface
     */
    private $entityRepository;
    /**
     * @var LoggerService
     */
    private $loggerService;
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;
    /**
     * @var RequestStack
     */
    private $requestStack;
    /**
     * @var EntityRepository
     */
    private $userRepository;
    /**
     * @var EntityRepository
     */
    private $localeRepository;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var TagService
     */
    private $tagService;
    /**
     * @var ProductSearchService
     */
    private $productSearchService;
    /**
     * @var LanguageService
     */
    private $languageService;
    /**
     * @var OrderService
     */
    private $orderService;
    /**
     * @var SubscriberService
     */
    private $subscriberService;
    /**
     * @var BuyerService
     */
    private $buyerService;
    /**
     * @var ContactService
     */
    private $contactService;
    /**
     * @var UninstallService
     */
    private $uninstallService;
    /**
     * @var CustomerRepository
     */
    private $customerRepository;
    /**
     * @var CurrencyRepository
     */
    private $currencyRepository;
    /**
     * @var ProductRepository
     */
    private $productRepository;
    /**
     * @var SalesChannelRepository
     */
    private $salesChannelRepository;
    /**
     * @var EntityRepositoryInterface
     */
    private $automationEntityRepository;

    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;

    /**
     * Initializer constructor.
     *
     * @param Connection $connection
     * @param EntityRepositoryInterface $entityRepository
     * @param LoggerService $loggerService
     * @param UrlGeneratorInterface $urlGenerator
     * @param RequestStack $requestStack
     * @param EntityRepository $userRepository
     * @param EntityRepository $localeRepository
     * @param TranslatorInterface $translator
     * @param TagService $tagService
     * @param ProductSearchService $productSearchService
     * @param LanguageService $languageService
     * @param OrderService $orderService
     * @param SubscriberService $subscriberService
     * @param BuyerService $buyerService
     * @param ContactService $contactService
     * @param UninstallService $uninstallService
     * @param CustomerRepository $customerRepository
     * @param CurrencyRepository $currencyRepository
     * @param ProductRepository $productRepository
     * @param SalesChannelRepository $salesChannelRepository
     * @param EntityRepositoryInterface $automationEntityRepository
     * @param ParameterBagInterface $parameterBag
     */
    public function __construct(
        Connection $connection,
        EntityRepositoryInterface $entityRepository,
        LoggerService $loggerService,
        UrlGeneratorInterface $urlGenerator,
        RequestStack $requestStack,
        EntityRepository $userRepository,
        EntityRepository $localeRepository,
        TranslatorInterface $translator,
        TagService $tagService,
        ProductSearchService $productSearchService,
        LanguageService $languageService,
        OrderService $orderService,
        SubscriberService $subscriberService,
        BuyerService $buyerService,
        ContactService $contactService,
        UninstallService $uninstallService,
        CustomerRepository $customerRepository,
        CurrencyRepository $currencyRepository,
        ProductRepository $productRepository,
        SalesChannelRepository $salesChannelRepository,
        EntityRepositoryInterface $automationEntityRepository,
        ParameterBagInterface $parameterBag
    ) {
        $this->connection = $connection;
        $this->entityRepository = $entityRepository;
        $this->loggerService = $loggerService;
        $this->urlGenerator = $urlGenerator;
        $this->requestStack = $requestStack;
        $this->userRepository = $userRepository;
        $this->localeRepository = $localeRepository;
        $this->translator = $translator;
        $this->tagService = $tagService;
        $this->productSearchService = $productSearchService;
        $this->languageService = $languageService;
        $this->orderService = $orderService;
        $this->subscriberService = $subscriberService;
        $this->buyerService = $buyerService;
        $this->contactService = $contactService;
        $this->uninstallService = $uninstallService;
        $this->customerRepository = $customerRepository;
        $this->currencyRepository = $currencyRepository;
        $this->productRepository = $productRepository;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->customerRepository = $customerRepository;
        $this->automationEntityRepository = $automationEntityRepository;
        $this->parameterBag = $parameterBag;
    }

    /**
     * Registers Shopware services.
     */
    public function registerServices(): void
    {
        ServiceRegister::registerService(
            Connection::class,
            function () {
                return $this->connection;
            }
        );

        ServiceRegister::registerService(
            EntityRepositoryInterface::class,
            function () {
                return $this->entityRepository;
            }
        );

        ServiceRegister::registerService(
            ShopLoggerAdapter::class,
            function () {
                return $this->loggerService;
            }
        );

        ServiceRegister::registerService(
            UrlGeneratorInterface::class,
            function () {
                return $this->urlGenerator;
            }
        );

        ServiceRegister::registerService(
            RequestStack::class,
            function () {
                return $this->requestStack;
            }
        );


        ServiceRegister::registerService(
            'User' . EntityRepository::class,
            function () {
                return $this->userRepository;
            }
        );

        ServiceRegister::registerService(
            'Locale' . EntityRepository::class,
            function () {
                return $this->localeRepository;
            }
        );

        ServiceRegister::registerService(
            TranslatorInterface::class,
            function () {
                return $this->translator;
            }
        );

        ServiceRegister::registerService(
            TagService::class,
            function () {
                return $this->tagService;
            }
        );

        ServiceRegister::registerService(
            ProductSearchService::class,
            function () {
                return $this->productSearchService;
            }
        );

        ServiceRegister::registerService(
            LanguageService::class,
            function () {
                return $this->languageService;
            }
        );

        ServiceRegister::registerService(
            OrderService::class,
            function () {
                return $this->orderService;
            }
        );

        ServiceRegister::registerService(
            SubscriberService::class,
            function () {
                return $this->subscriberService;
            }
        );

        ServiceRegister::registerService(
            BuyerService::class,
            function () {
                return $this->buyerService;
            }
        );

        ServiceRegister::registerService(
            ContactService::class,
            function () {
                return $this->contactService;
            }
        );

        ServiceRegister::registerService(
            UninstallService::class,
            function () {
                return $this->uninstallService;
            }
        );

        ServiceRegister::registerService(
            CustomerRepository::class,
            function () {
                return $this->customerRepository;
            }
        );

        ServiceRegister::registerService(
            CurrencyRepository::class,
            function () {
                return $this->currencyRepository;
            }
        );

        ServiceRegister::registerService(
            ProductRepository::class,
            function () {
                return $this->productRepository;
            }
        );

        ServiceRegister::registerService(
            SalesChannelRepository::class,
            function () {
                return $this->salesChannelRepository;
            }
        );

        ServiceRegister::registerService(
            'Automation' . EntityRepositoryInterface::class,
            function () {
                return $this->automationEntityRepository;
            }
        );

        ServiceRegister::registerService(
            ParameterBagInterface::class,
            function () {
                return $this->parameterBag;
            }
        );
    }
}
