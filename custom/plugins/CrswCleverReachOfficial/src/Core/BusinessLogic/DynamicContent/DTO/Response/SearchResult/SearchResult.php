<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\Response\SearchResult;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

/**
 * Class SearchResult
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\Response\Filter\SearchResult
 */
class SearchResult extends DataTransferObject
{
    /**
     * @var Settings
     */
    protected $settings;
    /**
     * @var Item[]
     */
    protected $items = array();

    /**
     * @return Settings
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @param Settings $settings
     */
    public function setSettings($settings)
    {
        $this->settings = $settings;
    }

    /**
     * @return Item[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param Item[] $items
     */
    public function setItems($items)
    {
        $this->items = $items;
    }

    /**
     * @param Item $item
     */
    public function addItem(Item $item)
    {
        $this->items[$item->getId()] = $item;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        $items = array();
        foreach ($this->items as $item) {
            $items[] = $item->toArray();
        }

        return array(
            'settings' => $this->settings->toArray(),
            'items' => $items,
        );
    }
}
