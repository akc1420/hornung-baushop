<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagSocialShopping\Component\MessageQueue;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\ProductStream\Service\ProductStreamBuilderInterface;
use Shopware\Core\Framework\Adapter\Translation\Translator;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\SalesChannelRepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\MessageQueue\Handler\AbstractMessageHandler;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceInterface;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceParameters;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use SwagSocialShopping\Component\Validation\NetworkProductValidator;
use SwagSocialShopping\DataAbstractionLayer\Entity\SocialShoppingSalesChannelEntity;
use SwagSocialShopping\Exception\NoDomainAssignedException;
use SwagSocialShopping\Exception\NoLanguageAssignedException;
use SwagSocialShopping\Exception\NoProductStreamAssignedException;
use SwagSocialShopping\Exception\SocialShoppingSalesChannelNotFoundException;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @deprecated tag:v3.2.0 - Class will no longer extend the AbstractMessageHandler
 */
class SocialShoppingValidationHandler extends AbstractMessageHandler
{
    private EntityRepositoryInterface $socialShoppingSalesChannelRepository;

    private SalesChannelContextServiceInterface $salesChannelContextService;

    private Translator $translator;

    private ProductStreamBuilderInterface $productStreamBuilder;

    private SalesChannelRepositoryInterface $salesChannelProductRepository;

    private NetworkProductValidator $networkProductValidator;

    private MessageBusInterface $messageBus;

    private LoggerInterface $logger;

    private SalesChannelContextPersister $contextPersister;

    private Connection $connection;

    private int $readBufferSize;

    public function __construct(
        EntityRepositoryInterface $socialShoppingSalesChannelRepository,
        SalesChannelContextServiceInterface $salesChannelContextService,
        Translator $translator,
        ProductStreamBuilderInterface $productStreamBuilder,
        SalesChannelRepositoryInterface $salesChannelProductRepository,
        NetworkProductValidator $networkProductValidator,
        MessageBusInterface $messageBus,
        LoggerInterface $logger,
        SalesChannelContextPersister $contextPersister,
        Connection $connection,
        int $readBufferSize
    ) {
        $this->socialShoppingSalesChannelRepository = $socialShoppingSalesChannelRepository;
        $this->salesChannelContextService = $salesChannelContextService;
        $this->translator = $translator;
        $this->productStreamBuilder = $productStreamBuilder;
        $this->salesChannelProductRepository = $salesChannelProductRepository;
        $this->networkProductValidator = $networkProductValidator;
        $this->messageBus = $messageBus;
        $this->logger = $logger;
        $this->contextPersister = $contextPersister;
        $this->connection = $connection;
        $this->readBufferSize = $readBufferSize;
    }

    public static function getHandledMessages(): iterable
    {
        return [SocialShoppingValidation::class];
    }

    /**
     * @param SocialShoppingValidation $message
     *
     * @deprecated tag:v3.2.0 - Function will be replaced by __invoke
     */
    public function handle($message): void
    {
        $contextToken = Uuid::randomHex();
        $socialShoppingSalesChannel = null;

        try {
            $socialShoppingSalesChannel = $this->getSocialShoppingSalesChannel(
                $message->getSocialShoppingSalesChannelId()
            );

            if ($socialShoppingSalesChannel === null) {
                throw new SocialShoppingSalesChannelNotFoundException($message->getSocialShoppingSalesChannelId());
            }

            $salesChannelDomain = $socialShoppingSalesChannel->getSalesChannelDomain();

            if ($salesChannelDomain === null) {
                throw new NoDomainAssignedException(
                    \get_class($socialShoppingSalesChannel),
                    $socialShoppingSalesChannel->getId()
                );
            }

            $this->contextPersister->save(
                $contextToken,
                [
                    SalesChannelContextService::CURRENCY_ID => $socialShoppingSalesChannel->getCurrencyId(),
                ],
                $salesChannelDomain->getSalesChannelId()
            );

            $context = $this->salesChannelContextService->get(
                new SalesChannelContextServiceParameters(
                    $salesChannelDomain->getSalesChannelId(),
                    $contextToken,
                    $salesChannelDomain->getLanguageId(),
                    $socialShoppingSalesChannel->getCurrencyId()
                )
            );

            $salesChannelDomainLanguage = $salesChannelDomain->getLanguage();

            if ($salesChannelDomainLanguage === null) {
                throw new NoLanguageAssignedException(
                    \get_class($salesChannelDomain),
                    $salesChannelDomain->getId()
                );
            }

            $this->translator->injectSettings(
                $salesChannelDomain->getSalesChannelId(),
                $salesChannelDomain->getLanguageId(),
                $salesChannelDomainLanguage->getLocaleId(),
                $context->getContext()
            );

            $productStreamId = $socialShoppingSalesChannel->getProductStreamId();
            if ($productStreamId === '' || $productStreamId === null) {
                throw new NoProductStreamAssignedException(
                    \get_class($socialShoppingSalesChannel),
                    $socialShoppingSalesChannel->getId()
                );
            }

            $criteria = $this->getCriteria(
                $productStreamId,
                $message->getOffset(),
                $context->getContext()
            );

            $iterator = new SalesChannelRepositoryIterator($this->salesChannelProductRepository, $context, $criteria);
            $productResult = $iterator->fetch();

            $this->connection->delete('sales_channel_api_context', ['token' => $contextToken]);

            if ($productResult !== null) {
                $this->networkProductValidator->executeValidators(
                    $productResult->getEntities(),
                    $socialShoppingSalesChannel,
                    $message->getOffset() === 0
                );

                if ($message->getOffset() + $this->readBufferSize <= $iterator->getTotal()) {
                    $this->messageBus->dispatch(
                        new SocialShoppingValidation(
                            $message->getSocialShoppingSalesChannelId(),
                            $message->getOffset() + $this->readBufferSize
                        )
                    );

                    $this->translator->resetInjection();

                    return;
                }
            }
        } catch (\Exception $exception) {
            $this->logger->error(
                $exception->getMessage(),
                [
                    'exception' => $exception,
                    'trace' => $exception->getTraceAsString(),
                    'socialShoppingSalesChannel' => $message->getSocialShoppingSalesChannelId(),
                    'offset' => $message->getOffset(),
                ]
            );

            throw $exception;
        } finally {
            $this->translator->resetInjection();

            $this->connection->delete('sales_channel_api_context', ['token' => $contextToken]);

            if ($socialShoppingSalesChannel !== null) {
                $this->finishValidation($message->getSocialShoppingSalesChannelId());
            }
        }
    }

    private function getCriteria(string $productStreamId, int $offset, Context $context): Criteria
    {
        $filters = $this->productStreamBuilder->buildFilters(
            $productStreamId,
            $context
        );

        $criteria = new Criteria();
        $criteria
            ->addFilter(...$filters)
            ->setOffset($offset)
            ->setLimit($this->readBufferSize)
            ->addAssociation('categories')
            ->addAssociation('cover')
            ->addAssociation('manufacturer')
            ->addAssociation('media')
            ->addAssociation('prices')
            ->addAssociation('properties.group');

        return $criteria;
    }

    private function getSocialShoppingSalesChannel(
        string $socialShoppingSalesChannelId
    ): ?SocialShoppingSalesChannelEntity {
        $context = Context::createDefaultContext();

        $criteria = new Criteria([$socialShoppingSalesChannelId]);
        $criteria->addAssociation('salesChannelDomain.language');

        return $this->socialShoppingSalesChannelRepository->search($criteria, $context)->get(
            $socialShoppingSalesChannelId
        );
    }

    private function finishValidation(string $socialShoppingSalesChannelId): void
    {
        $this->socialShoppingSalesChannelRepository->update(
            [
                [
                    'id' => $socialShoppingSalesChannelId,
                    'isValidating' => false,
                    'lastValidation' => new \DateTime(),
                ],
            ],
            Context::createDefaultContext()
        );
    }
}
