<?php

namespace Sisi\Search\Service;

use Doctrine\DBAL\Connection;
use phpDocumentor\Reflection\Types\Boolean;

class ProductMoreService
{

    /**
     * @var array
     */
    private $values = [
        '_arabic_' => 'arabic',
        '_armenian_' => 'armenian',
        '_basque_' => 'basque',
        '_bengali_' => 'bengali',
        '_brazilian_' => 'brazilian',
        '_catalan_' => 'catalan',
        '_czech_' => 'czech',
        '_dutch_' => 'dutch',
        '_estonian_' => 'estonian',
        '_galician_' => 'galician',
        '_greek_' => 'greek',
        '_hindi_' => 'hindi',
        '_indonesian_' => 'indonesian',
        '_irish_' => 'irish',
        '_latvian_' => 'latvian',
        '_lithuanian_' => 'lithuanian',
        '_romanian_' => 'romanian',
        '_sorani_' => 'sorani',
        '_turkish_' => 'turkish',
        '_english_' => [
            'english',
            'light_english',
            'lovins',
            'minimal_english',
            'porter2',
            'possessive_english',
            'en'
        ],
        '_french_' => [
            'french',
            'light_french',
            'minimal_french'

        ],
        '_german_' => [
            'german',
            'light_german',
            'german2',
            'minimal_german',
            'de'
        ],
        '_norwegian_' => [
            'norwegia',
            'light_norwegian',
            'minimal_norwegian',
            'light_nynorsk',
            'minimal_nynorsk',
            'nynorsk'
        ],
        '_portuguese_' => [
            'portuguese',
            'light_portuguese',
            'minimal_portuguese',
            'portuguese_rslp'
        ],
        '_italian_' => [
            'light_italian',
            'italian'

        ],
        '_russian_' => [
            'russian',
            'light_russian'
        ],
        '_spanish_' => [
            'light_spanish',
            'spanish'
        ],
        '_swedish_' => [
            'swedish',
            'light_swedish'
        ]
    ];

    /**
     * @param string $stemming
     * @return int|string
     */
    public function checkstopWort(string $stemming)
    {
        foreach ($this->values as $key => $val) {
            if (is_array($val)) {
                if (in_array($stemming, $val)) {
                    return $key;
                }
            } else {
                if ($stemming == $val) {
                    return $key;
                }
            }
        }
        return '';
    }
}
