<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Survey\DTO;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

/**
 * Class LocaleContent
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Survey\DTO
 */
class LocaleContent extends DataTransferObject
{
    /**
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $language;
    /**
     * @var string
     */
    protected $title;
    /**
     * @var string
     */
    protected $question;
    /**
     * @var string
     */
    protected $url;
    /**
     * @var string
     */
    protected $promotoren;
    /**
     * @var string
     */
    protected $indifferente;
    /**
     * @var string
     */
    protected $detraktoren;
    /**
     * @var string
     */
    protected $text;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getQuestion()
    {
        return $this->question;
    }

    /**
     * @param string $question
     */
    public function setQuestion($question)
    {
        $this->question = $question;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getPromotoren()
    {
        return $this->promotoren;
    }

    /**
     * @param string $promotoren
     */
    public function setPromotoren($promotoren)
    {
        $this->promotoren = $promotoren;
    }

    /**
     * @return string
     */
    public function getIndifferente()
    {
        return $this->indifferente;
    }

    /**
     * @param string $indifferente
     */
    public function setIndifferente($indifferente)
    {
        $this->indifferente = $indifferente;
    }

    /**
     * @return string
     */
    public function getDetraktoren()
    {
        return $this->detraktoren;
    }

    /**
     * @param string $detraktoren
     */
    public function setDetraktoren($detraktoren)
    {
        $this->detraktoren = $detraktoren;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }


    /**
     * @inheritDoc
     * @return array
     */
    public function toArray()
    {
        return array(
            'name' => $this->name,
            'language' => $this->language,
            'title' => $this->title,
            'question' => $this->question,
            'url' => $this->url,
            'promotoren' => $this->promotoren,
            'indifferente' => $this->indifferente,
            'detraktoren' => $this->detraktoren,
            'text' => $this->text,
        );
    }

    /**
     * @inheritDoc
     * @param array $data
     *
     * @return LocaleContent
     */
    public static function fromArray(array $data)
    {
        $content = new static();
        $content->name = static::getDataValue($data, 'name');
        $content->language = static::getDataValue($data, 'language');
        $content->title = static::getDataValue($data, 'title');
        $content->question = static::getDataValue($data, 'question');
        $content->url = static::getDataValue($data, 'url');
        $content->promotoren = static::getDataValue($data, 'promotoren');
        $content->indifferente = static::getDataValue($data, 'indifferente');
        $content->detraktoren = static::getDataValue($data, 'detraktoren');
        $content->text = static::getDataValue($data, 'text');

        return $content;
    }
}