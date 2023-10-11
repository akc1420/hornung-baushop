<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT15681;

use Shopware\Core\Content\Product\Aggregate\ProductReview\ProductReviewEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\Security\Components\AbstractSecurityFix;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class SecurityFix extends AbstractSecurityFix
{
    /**
     * @var EntityRepositoryInterface $productReviewRepository
     */
    private $productReviewRepository;

    public function __construct(EntityRepositoryInterface $productReviewRepository)
    {
        $this->productReviewRepository = $productReviewRepository;
    }

    public static function getTicket(): string
    {
        return 'NEXT-15681';
    }

    public function onControllerArguments(ControllerArgumentsEvent $event)
    {
        $request = $event->getRequest();

        if (!in_array($request->attributes->get('_route'), ['store-api.product-review.save', 'frontend.detail.review.save'])) {
            return;
        }

        $id = $request->get('id');

        if ($id === null || !$request->attributes->has(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT)) {
            return;
        }

        /** @var SalesChannelContext $context */
        $context = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);

        $criteria = new Criteria([$id]);

        /** @var ProductReviewEntity|null $review */
        $review = $this->productReviewRepository->search($criteria, $context->getContext())->first();

        if ($review === null) {
            return;
        }

        if ($review->getCustomerId() === $context->getCustomer()->getId()) {
            return;
        }

        throw new ConstraintViolationException(new ConstraintViolationList([new ConstraintViolation(sprintf('Cannot find product_review with id: %s', $id), '', [], '/', '/id', $id)]), $request->request->all());

    }

    public static function getMaxVersion(): ?string
    {
        return '6.4.3.1';
    }

    public static function getMinVersion(): string
    {
        return '6.3.2.0';
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER_ARGUMENTS => 'onControllerArguments'
        ];
    }
}
