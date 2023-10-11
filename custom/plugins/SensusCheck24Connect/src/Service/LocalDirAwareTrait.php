<?php declare(strict_types=1);

namespace Sensus\Check24Connect\Service;

trait LocalDirAwareTrait
{
    /**
     * gets the path to the directory for fetched order documents
     *
     * @return string
     */
    protected function getLocalDir(): string
    {
        if (!is_dir(dirname(dirname(dirname(__FILE__))) . '/downloadedOrders'))
        {
            mkdir(dirname(dirname(dirname(__FILE__))) . '/downloadedOrders');
        }

        return realpath(dirname(dirname(dirname(__FILE__))) . '/downloadedOrders');
    }
}