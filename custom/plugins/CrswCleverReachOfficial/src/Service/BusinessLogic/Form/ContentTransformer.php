<?php


namespace Crsw\CleverReachOfficial\Service\BusinessLogic\Form;


use DOMDocument;
use DOMElement;

/**
 * Class ContentTransformer
 *
 * @package Crsw\CleverReachOfficial\Service\BusinessLogic\Form
 */
class ContentTransformer
{
    public static function transform($formContent): string
    {
        $formContent = html_entity_decode($formContent);

        $dom = new DOMDocument();
        $dom->encoding = 'utf-8';

        @$dom->loadHTML(utf8_decode($formContent));

        self::transformButtons($dom);

        $newHtml = $dom->saveHTML();
        $newHtml = str_replace(['<br>', 'badge '], '', $newHtml);

        return $newHtml;
    }

    /**
     * Appends shopware specific classes to buttons in form html.
     *
     * @param DOMDocument $dom
     */
    protected static function transformButtons(DOMDocument $dom): void
    {
        $buttons = $dom->getElementsByTagName('button');
        foreach ($buttons as $button) {
            /** @var DOMElement $button */
            $classList = $button->getAttribute('class');
            $classList .= ' btn btn-primary';
            $button->setAttribute('class', $classList);
        }
    }
}
