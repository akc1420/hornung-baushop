<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag;

use Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\DTO\Segment;
use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

/**
 * Class Tag
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag
 */
class Tag extends DataTransferObject
{
    const MAX_ORIGIN = 49;
    const CLASS_NAME = __CLASS__;
    /**
     * Tag source. Commonly equal to integration name.
     *
     * @var string
     */
    protected $source;
    /**
     * Tag value.
     *
     * @var string $value
     */
    protected $value;
    /**
     * Tag type.
     *
     * @var string
     */
    protected $type;

    /**
     * Tag constructor.
     *
     * @param string $source
     * @param string $value
     */
    public function __construct($source, $value)
    {
        $this->setSource($source);
        $this->setValue($value);
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param string $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

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
        $this->type = $type;
    }

    /**
     * Retrieves string representation of a Tag.
     *
     * @return string
     */
    public function __toString()
    {
        $source = static::format($this->getSource());
        $type = static::format($this->getType());
        $value = static::format($this->getValue());

        // Tag origin is limited to 50 chars by the CR API.
        // In this limit must be included a modifier character (-) for deleting tag.
        // Therefore, max origin limit is 49 characters.
        // @see https://rest.cleverreach.com/explorer/v3#!/receivers-v3/addTags_post
        $origin = substr($source . '-' . $type, 0, self::MAX_ORIGIN);
        $tag = rtrim($origin, '-.') . '.' . $value;

        return ltrim($tag, '-.');
    }

    /**
     * Creates tag from a formatted string.
     *
     * @param string $raw Formatted tag.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\DTO\Tag\Tag
     */
    public static function fromString($raw)
    {
        $type = static::parseType($raw);
        $source = static::parseSource($raw);
        $value = static::parseValue($raw);

        $tag = new self($source, $value);
        $tag->setType($type);

        return $tag;
    }

    /**
     * Retrieves array representation of a Tag.
     * @return array
     */
    public function toArray()
    {
        return array(
            'type' => $this->getType(),
            'source' => $this->getSource(),
            'value' => $this->getValue(),
        );
    }

    /**
     * Creates tag.
     *
     * @param array $data Raw tag data.
     *
     * @return Tag
     */
    public static function fromArray(array $data)
    {
        $entity = new static($data['source'], $data['value']);
        $entity->type = $data['type'];

        return $entity;
    }

    /**
     * Retrieves segment corresponding to a particular Tag.
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\DTO\Segment
     */
    public function toSegment()
    {
        return Segment::fromArray(
            array(
                'name' => $this->getType() . ': ' . $this->getValue(),
                'operator' => 'AND',
                'rules' => array(
                    array(
                        'logic' => 'contains',
                        'condition' => $this->__toString(),
                        'field' => 'tags',
                    ),
                ),
            )
        );
    }

    /**
     * Creates list of tags from a batch.
     *
     * @param array $batch Batch of raw tags.
     *
     * @return Tag[]
     */
    public static function fromBatch(array $batch)
    {
        $tags = array();

        foreach ($batch as $raw) {
            if (is_array($raw)) {
                $tags[] = static::fromArray($raw);
            } else {
                $tags[] = static::fromString($raw);
            }
        }

        return $tags;
    }

    /**
     * Parses tag type from a tag.
     *
     * @param string $tag Tag in format [SOURCE-[TYPE]:]VALUE
     *
     * @return string Tag type or EMPTY_STRING if paring fails.
     */
    protected static function parseType($tag)
    {
        $result = '';

        $origin = static::parseOrigin($tag);

        if ($origin === '') {
            return $result;
        }

        $parts = explode('-', $origin);
        if (count($parts) > 1) {
            $result = implode(array_slice($parts, 1));
        }

        return $result;
    }

    /**
     * Parses tag source.
     *
     * @param string $tag
     *
     * @return string
     */
    protected static function parseSource($tag)
    {

        $origin = static::parseOrigin($tag);
        if ($origin === '') {
            return '';
        }

        $result = $origin;
        $parts = explode('-', $origin);
        if (count($parts) > 1) {
            $result = $parts[0];
        }

        return $result;
    }

    /**
     * Parses tag value.
     *
     * @param string $tag
     *
     * @return string
     */
    protected static function parseValue($tag)
    {
        $result = $tag;
        $parts = explode('.', $tag);
        if (count($parts) > 1) {
            $result = implode(array_slice($parts, 1));
        }

        return $result;
    }

    /**
     * Parses tag origin.
     *
     * @param string $tag
     *
     * @return string
     */
    protected static function parseOrigin($tag)
    {
        $result = '';

        $parts = explode('.', $tag);
        if (count($parts) > 1) {
            $result = $parts[0];
        }

        return $result;
    }

    /**
     * Formats tag value by removing forbidden characters.
     *
     * @param $value
     *
     * @return string
     */
    protected static function format($value)
    {
        $regex = '/[^a-zA-Z0-9_\\p{L}]+/u';

        return preg_replace($regex, '_', $value);
    }
}