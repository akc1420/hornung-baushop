<?php

namespace Recommendy\Components\Struct;

class ConfigStruct extends BaseStruct
{
    const PREFIX = 'recommendy';

    /** @var int */
    protected $recommendationAmount;
    /** @var int */
    protected $similarProductAmount;
    /** @var int */
    protected $basketRecommendationAmountMax;
    /** @var int */
    protected $detailRecommendationAmountMax;
    /** @var bool */
    protected $considerInstock;
    /** @var bool */
    protected $activeBasketSlider;
    /** @var bool */
    protected $activeOffcanvasBasket;
    /** @var bool */
    protected $displayLogo;
    /** @var bool */
    protected $enableSessionTracking;
    /** @var bool */
    protected $enableLike;

    /**
     * @return int
     */
    public function getRecommendationAmount(): int
    {
        return $this->recommendationAmount;
    }

    /**
     * @param int $recommendationAmount
     */
    public function setRecommendationAmount(int $recommendationAmount): void
    {
        $this->recommendationAmount = $recommendationAmount;
    }

    /**
     * @return int
     */
    public function getSimilarProductAmount(): int
    {
        return $this->similarProductAmount;
    }

    /**
     * @param int $similarProductAmount
     */
    public function setSimilarProductAmount(int $similarProductAmount): void
    {
        $this->similarProductAmount = $similarProductAmount;
    }

    /**
     * @return int
     */
    public function getBasketRecommendationAmountMax(): int
    {
        return $this->basketRecommendationAmountMax;
    }

    /**
     * @param int $basketRecommendationAmountMax
     */
    public function setBasketRecommendationAmountMax(int $basketRecommendationAmountMax): void
    {
        $this->basketRecommendationAmountMax = $basketRecommendationAmountMax;
    }

    /**
     * @return int
     */
    public function getDetailRecommendationAmountMax(): int
    {
        return $this->detailRecommendationAmountMax;
    }

    /**
     * @param int $detailRecommendationAmountMax
     */
    public function setDetailRecommendationAmountMax(int $detailRecommendationAmountMax): void
    {
        $this->detailRecommendationAmountMax = $detailRecommendationAmountMax;
    }

    /**
     * @return bool
     */
    public function isConsiderInstock(): bool
    {
        return $this->considerInstock;
    }

    /**
     * @param bool $considerInstock
     */
    public function setConsiderInstock(bool $considerInstock): void
    {
        $this->considerInstock = $considerInstock;
    }

    /**
     * @return bool
     */
    public function isActiveBasketSlider(): bool
    {
        return $this->activeBasketSlider;
    }

    /**
     * @return bool
     */
    public function isActiveOffcanvasBasket(): bool
    {
        return $this->activeOffcanvasBasket;
    }
    /**
     * @param bool $activeOffcanvasBasket
     */
    public function setActiveOffcanvasBasket(bool $activeOffcanvasBasket): void
    {
        $this->activeOffcanvasBasket = $activeOffcanvasBasket;
    }

    /**
     * @return bool
     */
    public function isDisplayLogo(): bool
    {
        return $this->displayLogo;
    }
    /**
     * @param bool $displayLogo
     */
    public function setDisplayLogo(bool $displayLogo): void
    {
        $this->displayLogo = $displayLogo;
    }

    /**
     * @param bool $activeBasketSlider
     */
    public function setActiveBasketSlider(bool $activeBasketSlider): void
    {
        $this->activeBasketSlider = $activeBasketSlider;
    }

    /**
     * @return bool
     */
    public function isEnableSessionTracking(): bool
    {
        return $this->enableSessionTracking;
    }

    /**
     * @param bool $enableSessionTracking
     */
    public function setEnableSessionTracking(bool $enableSessionTracking): void
    {
        $this->enableSessionTracking = $enableSessionTracking;
    }

    /**
     * @return bool
     */
    public function isEnableLike(): bool
    {
        return $this->enableLike;
    }

    /**
     * @param bool $enableLike
     */
    public function setEnableLike(bool $enableLike): void
    {
        $this->enableLike = $enableLike;
    }
}
