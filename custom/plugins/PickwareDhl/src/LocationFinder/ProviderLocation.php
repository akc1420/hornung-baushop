<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareDhl\LocationFinder;

use Shopware\Core\Framework\Struct\Struct;

class ProviderLocation extends Struct
{
    protected array $idsByProvider;
    protected string $type;
    protected string $number;
    protected string $title;
    protected string $name;
    protected array $place;
    protected array $openingHoursByDay;
    protected array $serviceTypes;

    private function __construct(string $type)
    {
        $this->type = $type;
    }

    public static function createPackstationLocation(string $id): self
    {
        $self = new self('packstation');
        $self->setTitle(sprintf('Packstation %s', $id));
        $self->setNumber($id);

        return $self;
    }

    public static function createPaketshopLocation(string $id): self
    {
        $self = new self('paketshop');
        $self->setTitle(sprintf('DHL Paketshop %s', $id));
        $self->setNumber($id);

        return $self;
    }

    public static function createPostofficeLocation(string $id): self
    {
        $self = new self('post-office');
        $self->setTitle(sprintf('Postfiliale %s', $id));
        $self->setNumber($id);

        return $self;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getIdsByProvider(): array
    {
        return $this->idsByProvider;
    }

    public function setIdsByProvider(array $idsByProvider): void
    {
        $this->idsByProvider = $idsByProvider;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function setNumber(string $number): void
    {
        $this->number = $number;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getPlace(): array
    {
        return $this->place;
    }

    public function setPlace(array $place): void
    {
        $this->place = $place;
    }

    public function getOpeningHoursByDay(): array
    {
        return $this->openingHoursByDay;
    }

    public function setOpeningHoursByDay(array $openingHoursByDay): void
    {
        $this->openingHoursByDay = $openingHoursByDay;
    }

    public function getServiceTypes(): array
    {
        return $this->serviceTypes;
    }

    public function setServiceTypes(array $serviceTypes): void
    {
        $this->serviceTypes = $serviceTypes;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
