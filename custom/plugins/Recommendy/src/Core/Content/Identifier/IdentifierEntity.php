<?php declare(strict_types=1);

namespace Recommendy\Core\Content\Identifier;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class IdentifierEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $primaryProductId;

    /**
     * @var string
     */
    protected $identifier;

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
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     */
    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

}
