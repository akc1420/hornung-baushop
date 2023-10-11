<?php

namespace Sisi\Search\Service;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Sisi\SisiEsContentSearch6\ESindexing\InsertQueryDecorator;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RatingService
{
    public function weHaveRating(array $systemConfig): bool
    {
        if (array_key_exists('ratingFilteron', $systemConfig)) {
            if ($systemConfig['ratingFilteron'] === 'yes') {
                return true;
            }
        }
        return false;
    }
}
