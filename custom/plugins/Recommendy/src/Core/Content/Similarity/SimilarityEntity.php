<?php declare(strict_types=1);

namespace Recommendy\Core\Content\Similarity;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class SimilarityEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $primaryProductId;

    /**
     * @var string
     */
    protected $secondaryProductId;

    /**
     * @var string
     */
    protected $shop;

    /**
     * @var float
     */
    protected $similarity;

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
     * @return string
     */
    public function getSecondaryProductId(): string
    {
        return $this->secondaryProductId;
    }

    /**
     * @param string $secondaryProductId
     */
    public function setSecondaryProductId(string $secondaryProductId): void
    {
        $this->secondaryProductId = $secondaryProductId;
    }

    /**
     * @return string
     */
    public function getShop(): string
    {
        return $this->shop;
    }

    /**
     * @param string $shop
     */
    public function setShop(string $shop): void
    {
        $this->shop = $shop;
    }

    /**
     * @return float
     */
    public function getSimilarity(): float
    {
        return $this->similarity;
    }

    /**
     * @param float $similarity
     */
    public function setSimilarity(float $similarity): void
    {
        $this->similarity = $similarity;
    }
}
