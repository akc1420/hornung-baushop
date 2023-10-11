<?php

namespace Sisi\Search\ESIndexInterfaces;

interface InterfaceProduktDataMapping
{
    public function getMapping(array $mapping): array;
}
