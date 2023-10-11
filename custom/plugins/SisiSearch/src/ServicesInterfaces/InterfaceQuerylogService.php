<?php

namespace Sisi\Search\ServicesInterfaces;

use Elasticsearch\Client;
use Symfony\Component\Console\Output\OutputInterface;

interface InterfaceQuerylogService
{
    public function createMapping(): array;

    public function createSettings(): array;
}
