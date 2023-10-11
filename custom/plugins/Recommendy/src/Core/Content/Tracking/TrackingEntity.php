<?php declare(strict_types=1);

namespace Recommendy\Core\Content\Tracking;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

/**
    ActionId
    1 = Live Recommendation
    2 = Similarity
    3 = Bundle
    4 = Checkout Page
    5 = Add To Basket
    6 = like button click
    7 = buy all bundle items
    8 = Visit the detail page
    9 = Item has been purchased. price incl.
*/
class TrackingEntity extends Entity
{
    use EntityIdTrait;

    public const ACTION_UNKNOWN             = -1;
    public const ACTION_LIVE_RECOMMENDATION = 1;
    public const ACTION_SIMILARITY          = 2;
    public const ACTION_BUNDLE              = 3;
    public const ACTION_CHECKOUT            = 4;
    public const ACTION_BASKET              = 5;
    public const ACTION_LIKE_CLICK          = 6;
    public const ACTION_BUY_ALL_BUNDLE      = 7;
    public const ACTION_VISIT_DETAIL        = 8;
    public const ACTION_ITEM_PURCHASED      = 9;
    public const ACTION_BUY_SINGLE_BUNDLE   = 10;
    public const ACTION_OFFCANVAS           = 11;

    public const ACTIONS = [
        self::ACTION_LIVE_RECOMMENDATION => 'Live Recommendation',
        self::ACTION_SIMILARITY          => 'Similarity',
        self::ACTION_BUNDLE              => 'Bundle',
        self::ACTION_CHECKOUT            => 'Checkout Page',
        self::ACTION_BASKET              => 'Add To Basket',
        self::ACTION_LIKE_CLICK          => 'like button click',
        self::ACTION_BUY_ALL_BUNDLE      => 'buy all bundle items',
        self::ACTION_VISIT_DETAIL        => 'Visit the detail page',
        self::ACTION_ITEM_PURCHASED      => 'Item has been purchased. price incl.',
        self::ACTION_BUY_SINGLE_BUNDLE   => 'Item has been purchased by new UI',
        self::ACTION_OFFCANVAS           => 'Offcanvas Bundle'
    ];

    /**
     * @var int
     */
    protected $action;

    /**
     * @var string
     */
    protected $primaryProductId;

    /**
     * @var float
     */
    protected $price;

    /**
     * @var string
     */
    protected $sessionId;

    /**
     * @var string
     */
    protected $created;

    /**
     * @return int
     */
    public function getAction(): int
    {
        return $this->action;
    }

    /**
     * @param int $action
     */
    public function setAction(int $action): void
    {
        $this->action = array_key_exists($action, self::ACTIONS) ? $action : self::ACTION_UNKNOWN;
    }

    /**
     * @return string
     */
    public function getPrimaryProductId(): string
    {
        return $this->primaryProductId;
    }

    /**
     * @param string $primaryProductId
     */
    public function setPrimaryProductId(string $primaryProductId): void
    {
        $this->primaryProductId = $primaryProductId;
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * @param float $price
     */
    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    /**
     * @return string
     */
    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    /**
     * @param string $sessionId
     */
    public function setSessionId(string $sessionId): void
    {
        $this->sessionId = $sessionId;
    }

    /**
     * @return string
     */
    public function getCreated(): string
    {
        return $this->created;
    }

    /**
     * @param string $created
     */
    public function setCreated(string $created): void
    {
        $this->created = $created;
    }

}
