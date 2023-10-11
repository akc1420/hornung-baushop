<?php

declare(strict_types=1);

namespace Sisi\Search\Decorater;

use Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\PriceCollection as CalculatedPriceCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Checkout\Cart\Price\Struct\ReferencePriceDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductPrice\ProductPriceCollection;
use Shopware\Core\Content\Product\DataAbstractionLayer\CheapestPrice\CalculatedCheapestPrice;
use Shopware\Core\Content\Product\DataAbstractionLayer\CheapestPrice\CheapestPrice;
use Shopware\Core\Content\Product\DataAbstractionLayer\CheapestPrice\CheapestPriceContainer;
use Shopware\Core\Content\Product\SalesChannel\Price\AbstractProductPriceCalculator;
use Shopware\Core\Content\Product\SalesChannel\Price\ReferencePriceDto;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\Price;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\PriceCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\System\Unit\UnitCollection;
use Symfony\Contracts\Service\ResetInterface;
use Sisi\Search\ServicesInterfaces\InterfaceSisiProductPriceCalculator;
use Shopware\Core\Content\Product\SalesChannel\Price\ProductPriceCalculator;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 **/
class SisiProductPriceCalculator implements ResetInterface, InterfaceSisiProductPriceCalculator
{
    private EntityRepositoryInterface $unitRepository;

    private QuantityPriceCalculator $calculator;

    private ?UnitCollection $units = null;

    /**
     * @var SystemConfigService
     */
    protected $systemConfigService;

    /**
     * @var AbstractProductPriceCalculator
     */
    protected $productPriceCalculator;



    /**
     * @internal
     */
    public function __construct(EntityRepositoryInterface $unitRepository, QuantityPriceCalculator $calculator, SystemConfigService $systemConfigService, AbstractProductPriceCalculator $productPriceCalculator)
    {
        $this->unitRepository = $unitRepository;
        $this->calculator = $calculator;
        $this->systemConfigService = $systemConfigService;
        $this->productPriceCalculator = $productPriceCalculator;
    }

    public function reset(): void
    {
        $this->units = null;
    }

    public function getOrginalDecorater(): AbstractProductPriceCalculator
    {
        return  $this->productPriceCalculator;
    }

    public function getReferencePriceDto(Entity $product, SalesChannelContext $context): ReferencePriceDto
    {
        $config = $this->systemConfigService->get("SisiSearch.config", $context->getSalesChannel()->getId());
        if (array_key_exists('calculatedold', $config)) {
            if ($config['calculatedold'] === '1') {
                return ReferencePriceDto::createFromProduct($product);
            }
        }
        return  ReferencePriceDto::createFromEntity($product);
    }

    public function calculatePrice(Entity &$product, SalesChannelContext $context, UnitCollection $units): void
    {
        $price = $product->get('price');
        $taxId = $product->get('taxId');

        if ($price === null || $taxId === null) {
            return;
        }

        $reference = $this->getReferencePriceDto($product, $context);



        $definition = $this->buildDefinition($product, $price, $context, $units, $reference);



        $price = $this->calculator->calculate($definition, $context);



        $product->assign([
                             'calculatedPrice' => $price,
                         ]);
    }

    public function calculateAdvancePrices(Entity &$product, SalesChannelContext $context, UnitCollection $units): void
    {
        $prices = $product->get('prices');

        if ($prices === null) {
            return;
        }

        if (!$prices instanceof ProductPriceCollection) {
            return;
        }

        $prices = $this->filterRulePrices($prices, $context);

        if ($prices === null) {
            $product->assign(['calculatedPrices' => new CalculatedPriceCollection()]);
            return;
        }

        $prices->sortByQuantity();
        $reference = $this->getReferencePriceDto($product, $context);
        $calculated = new CalculatedPriceCollection();

        foreach ($prices as $price) {
            $quantity = $price->getQuantityEnd() ?? $price->getQuantityStart();

            $definition = $this->buildDefinition($product, $price->getPrice(), $context, $units, $reference, $quantity);

            $calculated->add($this->calculator->calculate($definition, $context));
        }
        $product->assign(['calculatedPrices' => $calculated]);
    }

    /**
     * @param Entity $product
     * @param SalesChannelContext $context
     * @param UnitCollection $units
     * @return void
     */
    public function calculateCheapestPrice(&$product, $context, $units)
    {
        $cheapest = $product->get('cheapestPrice');
        if (!empty($cheapest) && $cheapest !== null) {
            $price = $product->get('price');
            if ($price === null) {
                return;
            }
            // set empty price
            if (count($cheapest->getPrice()->getElements()) === 0) {
                $emptyprice = new Price(
                    $context->getCurrencyId(),
                    0,
                    0,
                    true
                );
                $cheapest->getPrice()->add($emptyprice);
                $cheapest->setHasRange(true);
            }

            $reference = $this->getReferencePriceDto($product, $context);

            $definition = $this->buildDefinition($product, $price, $context, $units, $reference);

            $calculated = CalculatedCheapestPrice::createFrom(
                $this->calculator->calculate($definition, $context)
            );

            $prices = $product->get('calculatedPrices');

            $hasRange = $prices instanceof CalculatedPriceCollection && $prices->count() > 1;

            $calculated->setHasRange($hasRange);
        }


        $reference = ReferencePriceDto::createFromCheapestPrice($cheapest);

        $definition = $this->buildDefinition($product, $cheapest->getPrice(), $context, $units, $reference);

        $calculated = CalculatedCheapestPrice::createFrom(
            $this->calculator->calculate($definition, $context)
        );

        $calculated->setHasRange($cheapest->hasRange());

        $product->assign(['calculatedCheapestPrice' => $calculated]);
    }

    private function buildDefinition(
        Entity &$product,
        PriceCollection $prices,
        SalesChannelContext $context,
        UnitCollection $units,
        ReferencePriceDto $reference,
        int $quantity = 1
    ): QuantityPriceDefinition {

        $price = $this->getPriceValue($prices, $context);

        $taxId = $product->get('taxId');

        $definition = new QuantityPriceDefinition($price, $context->buildTaxRules($taxId), $quantity);
        $definition->setReferencePriceDefinition(
            $this->buildReferencePriceDefinition($reference, $units)
        );

        $definition->setListPrice(
            $this->getListPrice($prices, $context)
        );

        if (method_exists($definition, 'setRegulationPrice')) {
            $definition->setRegulationPrice(
                $this->getRegulationPrice($prices, $context)
            );
        }

        return $definition;
    }

    private function getPriceValue(PriceCollection $price, SalesChannelContext $context): float
    {
        /** @var Price $currency */
        $currency = $price->getCurrencyPrice($context->getCurrencyId());


        $value = $this->getPriceForTaxState($currency, $context);

        if ($currency->getCurrencyId() !== $context->getCurrency()->getId()) {
            $value *= $context->getContext()->getCurrencyFactor();
        }

        return $value;
    }

    /**
     * @param Price |null$price
     * @param SalesChannelContext $context
     * @return float
     */
    private function getPriceForTaxState($price, $context): float
    {
        if ($price === null) {
            return 0;
        }

        if ($context->getTaxState() === CartPrice::TAX_STATE_GROSS) {
            return $price->getGross();
        }

        return $price->getNet();
    }

    private function getListPrice(?PriceCollection $prices, SalesChannelContext $context): ?float
    {
        if (!$prices) {
            return null;
        }

        $price = $prices->getCurrencyPrice($context->getCurrency()->getId());
        if ($price === null || $price->getListPrice() === null) {
            return null;
        }

        $value = $this->getPriceForTaxState($price->getListPrice(), $context);

        if ($price->getCurrencyId() !== $context->getCurrency()->getId()) {
            $value *= $context->getContext()->getCurrencyFactor();
        }

        return $value;
    }

    private function getRegulationPrice(?PriceCollection $prices, SalesChannelContext $context): ?float
    {
        if (!$prices) {
            return null;
        }

        $price = $prices->getCurrencyPrice($context->getCurrency()->getId());
        if ($price === null || $price->getRegulationPrice() === null) {
            return null;
        }

        $taxPrice = $this->getPriceForTaxState($price, $context);
        $value = $this->getPriceForTaxState($price->getRegulationPrice(), $context);
        if ($taxPrice === 0.0 || $taxPrice === $value) {
            return null;
        }

        if ($price->getCurrencyId() !== $context->getCurrency()->getId()) {
            $value *= $context->getContext()->getCurrencyFactor();
        }

        return $value;
    }

    private function buildReferencePriceDefinition(
        ReferencePriceDto $definition,
        UnitCollection $units
    ): ?ReferencePriceDefinition {
        if ($definition->getPurchase() === null || $definition->getPurchase() <= 0) {
            return null;
        }
        if ($definition->getUnitId() === null) {
            return null;
        }
        if ($definition->getReference() === null || $definition->getReference() <= 0) {
            return null;
        }
        if ($definition->getPurchase() === $definition->getReference()) {
            return null;
        }

        $unit = $units->get($definition->getUnitId());
        if ($unit === null) {
            return null;
        }

        return new ReferencePriceDefinition(
            $definition->getPurchase(),
            $definition->getReference(),
            $unit->getTranslation('name')
        );
    }

    /**
     * @param CheapestPriceContainer|array $values
     * @param SalesChannelContext $context
     * @return array
     *
     * * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getPricesFromContext($values, $context)
    {
        $return = [];
        $returnValues = [];
        $index = 0;
        foreach ($context->getRuleIds() as $ruleId) {
            if ($values !== null) {
                if (array_key_exists('value', $values)) {
                    foreach ($values['value'] as $value) {
                        if (is_array($value)) {
                            foreach ($value as $key => $valueItem) {
                                if ($valueItem !== null) {
                                    if ($ruleId === $valueItem['rule_id']) {
                                        $return[$index] = $valueItem;
                                        foreach ($valueItem['price'] as $price) {
                                            $returnValues[$index] = $price['net'];
                                        }
                                        $index++;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return [
            'values' => $return,
            'netprice' => $returnValues
        ];
    }
    /**
     * @param CheapestPriceContainer|array $values
     * @param SalesChannelContext $context
     * @return array
     */
    public function findtheMinValue($values, $context)
    {
        $values = $this->getPricesFromContext($values, $context) ;
        $key = $this->getKeyOfMin($values['netprice']);
        $return = [];
        if (array_key_exists($key, $values['values'])) {
            $return = $values['values'][$key];
        }
        return $return;
    }
    private function getKeyOfMin(array $arr)
    {
        $minKey = null;
        foreach ($arr as $key => $val) {
            if (is_int($val) || is_float($val)) {
                if ($minKey === null || $val < $arr[$minKey]) {
                    $minKey = $key;
                }
            }
        }
        return $minKey;
    }

    /**
     * @param ProductPriceCollection $rules
     * @param SalesChannelContext $context
     * @return ProductPriceCollection|null
     */
    public function filterRulePrices($rules, $context)
    {
        foreach ($context->getRuleIds() as $ruleId) {
            $filtered = $rules->filterByRuleId($ruleId);
            if (\count($filtered) > 0) {
                return $filtered;
            }
        }
        return null;
    }

    public function getUnits(SalesChannelContext $context): UnitCollection
    {
        if ($this->units !== null) {
            return $this->units;
        }

        $criteria = new Criteria();
        $criteria->setTitle('product-price-calculator::units');

        /** @var UnitCollection $units */
        $units = $this->unitRepository
            ->search($criteria, $context->getContext())
            ->getEntities();

        return $this->units = $units;
    }
}
