<?php

namespace Sisi\Search\ESindexing;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Sisi\Search\ESIndexInterfaces\InterfaceCreateCriteria;

class CreateCriteria implements InterfaceCreateCriteria
{
    public function getCriteria(Criteria &$criteria): void
    {
        $criteria->addAssociation('cover');
        $criteria->addAssociation('cover.media.thumbnails');
        $criteria->addAssociation('manufacturer');
        $criteria->addAssociation('manufacturer.translations');
        $criteria->addAssociation('categories');
        $criteria->addAssociation('categories.translations');
        $criteria->addAssociation('translations');
        $criteria->addAssociation('properties');
        $criteria->addAssociation('properties.group');
        $criteria->addAssociation('properties.group.translations');
        $criteria->addAssociation('properties.translations');
        $criteria->addAssociation('searchKeywords');
        $criteria->addAssociation('streams.categories');
        $criteria->addAssociation('streams.categories.translations');
        $criteria->addAssociation('price');
        $criteria->addAssociation('prices');
    }
}
