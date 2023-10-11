<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagSocialShopping\EventListener;

use Shopware\Core\Content\ProductStream\ProductStreamDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\DeleteCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Validation\PreWriteValidationEvent;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\WriteConstraintViolationException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;

class ProductStreamEventListener implements EventSubscriberInterface
{
    private EntityRepositoryInterface $socialShoppingRepository;

    public function __construct(EntityRepositoryInterface $socialShoppingRepository)
    {
        $this->socialShoppingRepository = $socialShoppingRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PreWriteValidationEvent::class => 'preValidate',
        ];
    }

    public function preValidate(PreWriteValidationEvent $event): void
    {
        $violations = new ConstraintViolationList();

        foreach ($event->getCommands() as $command) {
            if (!($command instanceof DeleteCommand) || $command->getDefinition()->getClass() !== ProductStreamDefinition::class) {
                continue;
            }

            $byteId = $command->getPrimaryKey()['id'];
            $hexId = \mb_strtolower(Uuid::fromBytesToHex($byteId));

            if (!$this->isProductStreamUsedBySocialShopping($hexId, $event->getContext())) {
                continue;
            }

            $violations->add(
                $this->buildViolation(
                    'The product stream {{ id }} is used by one or more social shopping sales channels.',
                    ['{{ id }}' => $hexId]
                )
            );
        }

        if ($violations->count() > 0) {
            $event->getExceptions()->add(new WriteConstraintViolationException($violations));
        }
    }

    private function isProductStreamUsedBySocialShopping(string $productStreamId, Context $context): bool
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productStreamId', $productStreamId));
        $criteria->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_EXACT);

        return $this->socialShoppingRepository->searchIds($criteria, $context)->getTotal() > 0;
    }

    private function buildViolation(string $messageTemplate, array $parameters): ConstraintViolationInterface
    {
        return new ConstraintViolation(
            \str_replace(\array_keys($parameters), \array_values($parameters), $messageTemplate),
            $messageTemplate,
            $parameters,
            null,
            null,
            null
        );
    }
}
