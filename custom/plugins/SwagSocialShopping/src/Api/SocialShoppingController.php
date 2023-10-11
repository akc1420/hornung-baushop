<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagSocialShopping\Api;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\Annotation\Acl;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use SwagSocialShopping\Component\DataFeed\DataFeedHandler;
use SwagSocialShopping\Component\MessageQueue\SocialShoppingValidation;
use SwagSocialShopping\Component\Network\NetworkInterface;
use SwagSocialShopping\Component\Network\NetworkRegistryInterface;
use SwagSocialShopping\Component\Validation\NetworkProductValidator;
use SwagSocialShopping\DataAbstractionLayer\Entity\SocialShoppingSalesChannelEntity;
use SwagSocialShopping\Exception\SocialShoppingSalesChannelNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"api"})
 */
class SocialShoppingController extends AbstractController
{
    private NetworkRegistryInterface $networkRegistry;

    private MessageBusInterface $messageBus;

    private EntityRepositoryInterface $socialShoppingSalesChannelRepository;

    private NetworkProductValidator $networkProductValidator;

    /**
     * @var DataFeedHandler
     */
    private $dataFeedHandler;

    public function __construct(
        NetworkRegistryInterface $networkRegistry,
        MessageBusInterface $messageBus,
        EntityRepositoryInterface $socialShoppingSalesChannelRepository,
        NetworkProductValidator $networkProductValidator,
        DataFeedHandler $dataFeedHandler
    ) {
        $this->networkRegistry = $networkRegistry;
        $this->messageBus = $messageBus;
        $this->socialShoppingSalesChannelRepository = $socialShoppingSalesChannelRepository;
        $this->networkProductValidator = $networkProductValidator;
        $this->dataFeedHandler = $dataFeedHandler;
    }

    /**
     * @Route("/api/_action/social-shopping/networks", name="api.action.social_shopping.networks", methods={"GET"})
     * @Acl({"sales_channel.viewer"})
     */
    public function getNetworks(): JsonResponse
    {
        $networks = [];

        foreach ($this->networkRegistry->getNetworks() as $network) {
            if (!($network instanceof NetworkInterface)) {
                continue;
            }

            $networks[$network->getName()] = \get_class($network);
        }

        return new JsonResponse($networks);
    }

    /**
     * @Route("/api/_action/social-shopping/validate", name="api.action.social_shopping.validate", methods={"POST"})
     * @Acl({"sales_channel.viewer"})
     */
    public function validate(RequestDataBag $dataBag, Context $context): Response
    {
        $socialShoppingSalesChannelId = $dataBag->get('social_shopping_sales_channel_id');
        if ($socialShoppingSalesChannelId === null) {
            throw new MissingRequestParameterException('social_shopping_sales_channel_id');
        }

        $socialShoppingSalesChannel = $this->socialShoppingSalesChannelRepository->search(
            new Criteria([$socialShoppingSalesChannelId]),
            $context
        )->get($socialShoppingSalesChannelId);

        if (!($socialShoppingSalesChannel instanceof SocialShoppingSalesChannelEntity)) {
            throw new SocialShoppingSalesChannelNotFoundException((string) $socialShoppingSalesChannelId);
        }

        $this->networkProductValidator->clearErrors($socialShoppingSalesChannel->getSalesChannelId(), $context);

        $this->setValidating($socialShoppingSalesChannelId, $context);

        $this->messageBus->dispatch(
            new SocialShoppingValidation($socialShoppingSalesChannelId)
        );

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/api/_action/social-shopping/reset", name="api.action.social_shopping.reset", methods={"POST"})
     * @Acl({"sales_channel.viewer"})
     */
    public function reset(RequestDataBag $dataBag, Context $context): Response
    {
        $socialShoppingSalesChannelId = $dataBag->get('social_shopping_sales_channel_id');
        if ($socialShoppingSalesChannelId === null) {
            throw new MissingRequestParameterException('social_shopping_sales_channel_id');
        }

        $socialShoppingSalesChannel = $this->socialShoppingSalesChannelRepository->search(
            new Criteria([$socialShoppingSalesChannelId]),
            $context
        )->get($socialShoppingSalesChannelId);

        if (!($socialShoppingSalesChannel instanceof SocialShoppingSalesChannelEntity)) {
            throw new SocialShoppingSalesChannelNotFoundException((string) $socialShoppingSalesChannelId);
        }

        $this->dataFeedHandler->createDataFeedForSalesChannelId($socialShoppingSalesChannelId, $context);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    private function setValidating(string $socialShoppingSalesChannelId, Context $context): void
    {
        $this->socialShoppingSalesChannelRepository->update(
            [
                [
                    'id' => $socialShoppingSalesChannelId,
                    'isValidating' => true,
                ],
            ],
            $context
        );
    }
}
