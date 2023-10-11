<?php declare(strict_types=1);

namespace Kzph\Simplenote\Core\Content\KzphSimplenoteNote;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class KzphSimplenoteNoteEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $entityId;

    /**
     * @var string|null
     */
    protected $entityType;

    /**
     * @var string|null
     */
    protected $username;

    /**
     * @var string|null
     */
    protected $note;

    /**
     * @var int|null
     */
    protected $replicateInOrder;
  
    /**
     * @var int|null
     */
    protected $done;

    /**
     * @var int|null
     */
    protected $showDesktop;

    /**
     * @var int|null
     */
    protected $showMessage;

    /**
     * @var \DateTimeInterface
     */
    protected $createdAt;

    /**
     * @var \DateTimeInterface|null
     */
    protected $updatedAt;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getEntityId(): string
    {
        return $this->entityId;
    }

    public function setEntityId(string $entityId): void
    {
        $this->entityId = $entityId;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function setEntityType(string $entityType): void
    {
        $this->entityType = $entityType;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): void
    {
        $this->username = $username;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): void
    {
        $this->note = $note;
    }

    public function getReplicateInOrder(): ?int
    {
        return $this->replicateInOrder;
    }

    public function setReplicateInOrder(?int $replicateInOrder): void
    {
        $this->replicateInOrder = $replicateInOrder;
    }

    public function getDone(): ?int
    {
        return $this->done;
    }

    public function setDone(?int $done): void
    {
        $this->done = $done;
    }

    public function getShowDesktop(): ?int
    {
        return $this->showDesktop;
    }

    public function setShowDesktop(?int $showDesktop): void
    {
        $this->showDesktop = $showDesktop;
    }

    public function getShowMessage(): ?int
    {
        return $this->showMessage;
    }

    public function setShowMessage(?int $showMessage): void
    {
        $this->showMessage = $showMessage;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
