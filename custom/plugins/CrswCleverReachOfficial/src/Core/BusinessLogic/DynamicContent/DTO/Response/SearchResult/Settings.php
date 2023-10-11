<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\Response\SearchResult;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

/**
 * Class Settings
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\Response\Filter\SearchResult
 */
class Settings extends DataTransferObject
{

    const RSS = 'rss';
    const PRODUCT = 'product';
    const CONTENT = 'content';
    /**
     * @var string
     */
    protected $type;
    /**
     * @var bool
     */
    protected $linkEditable;
    /**
     * @var bool
     */
    protected $linkTextEditable;
    /**
     * @var bool
     */
    protected $imageSizeEditable;

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $allowedTypes = array(static::PRODUCT, static::CONTENT, static::RSS);
        if (!in_array($type, $allowedTypes, true)) {
            throw new \InvalidArgumentException("$type is not allowed. Allowed types: " . implode(', ', $allowedTypes));
        }

        $this->type = $type;
    }

    /**
     * @return bool
     */
    public function isLinkEditable()
    {
        return $this->linkEditable;
    }

    /**
     * @param bool $linkEditable
     */
    public function setLinkEditable($linkEditable)
    {
        $this->linkEditable = $linkEditable;
    }

    /**
     * @return bool
     */
    public function isLinkTextEditable()
    {
        return $this->linkTextEditable;
    }

    /**
     * @param bool $linkTextEditable
     */
    public function setLinkTextEditable($linkTextEditable)
    {
        $this->linkTextEditable = $linkTextEditable;
    }

    /**
     * @return bool
     */
    public function isImageSizeEditable()
    {
        return $this->imageSizeEditable;
    }

    /**
     * @param bool $imageSizeEditable
     */
    public function setImageSizeEditable($imageSizeEditable)
    {
        $this->imageSizeEditable = $imageSizeEditable;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array(
            'type' => $this->type,
            'link_editable' => (bool)$this->linkEditable,
            'link_text_editable' => (bool)$this->linkTextEditable,
            'image_size_editable' => (bool)$this->imageSizeEditable,
        );
    }
}