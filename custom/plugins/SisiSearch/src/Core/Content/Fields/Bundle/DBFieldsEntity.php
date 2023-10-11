<?php

declare(strict_types=1);

namespace Sisi\Search\Core\Content\Fields\Bundle;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

/**
 * Class DBFieldsEntity
 * @package Sisi\Search\Core\Content\Fields\Bundle
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD)
 * @codingStandardsIgnoreStart
 */
class DBFieldsEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $fieldtype;


    /**
     * @var string
     */
    protected $tablename;


    /**
     * @var string|null
     */
    protected $shop;


    /**
     * @var string
     */
    protected $format;


    /**
     * @var string
     */
    protected $filter1;


    /**
     * @var string
     */
    protected $filter2;

    /**
     * @var string
     */
    protected $filter3;

    /**
     * @var string
     */
    protected $stemming;


    /**
     * @var string
     */
    protected $stop;


    /**
     * @var string
     */
    protected $stemmingstop;


    /**
     * @var string
     */
    protected $tokenizer;


    /**
     * @var int
     */

    protected $edge;


    /**
     * @var int
     */

    protected $minedge;


    /**
     * @var string
     */

    protected $booster;

    /**
     * @var string
     */

    protected $pattern;

    /**
     * @var string
     */

    protected $strip;


    /**
     * @var string
     */

    protected $strip_str;


    /**
     * @var string
     */
    protected $synonym;


    /**
     * @var string
     */
    protected $fuzzy;

    /**
     * @var string
     */
    protected $maxexpansions;


    /**
     * @var string
     */
    protected $slop;


    /**
     * @var string
     */
    protected $operator;


    /**
     * @var string
     */
    protected $autosynonyms;


    /**
     * @var string
     */
    protected $minimumshouldmatch;

    /**
     * @var string
     */
    protected $prefixlength;


    /**
     * @var string
     */
    protected $lenient;

    /**
     * @var string
     */
    protected $punctuation;


    /**
     * @var string
     */
    protected $whitespace;

    /**
     * @var string
     */

    protected $exclude;


    /**
     * @var string
     */

    protected $merge;

    /**
     * @var string
     */

    protected $prefix;

    /**
     * @var string
     */

    protected $phpfilter;

    /**
     * @var string|null
     */
    protected $shoplanguage;

    /**
     * @var string
     */
    protected $onlymain;


    /**
     * @var string
     */
    protected $excludesearch;




    /**
     * @return string
     */
    public function getTablename(): string
    {
        if ($this->tablename === null) {
            $this->tablename = '';
        }
        return $this->tablename;
    }

    /**
     * @params tring $tablename
     */
    public function setTablename(string $tablename): void
    {
        $this->tablename = $tablename;
    }

    /**
     * @return string
     */
    public function getFieldtype(): string
    {
        if ($this->fieldtype === null) {
            $this->fieldtype = '';
        }
        return $this->fieldtype;
    }


    public function setFieldtype(string $fieldtype): void
    {
        $this->fieldtype = $fieldtype;
    }

    /**
     * @return string
     */
    public function getTokenizer(): string
    {
        if ($this->tokenizer === null) {
            $this->tokenizer = '';
        }
        return $this->tokenizer;
    }

    /**
     * @param string $tokenizer
     */
    public function setTokenizer(string $tokenizer): void
    {
        $this->tokenizer = $tokenizer;
    }


    public function getName(): ?string
    {
        if ($this->name === null) {
            $this->name = '';
        }
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getShop(): ?string
    {
        if ($this->shop == null) {
            $this->shop = '';
        }
        return $this->shop;
    }

    /**
     * @param string|null $shop
     */
    public function setShop(?string $shop): void
    {
        $this->shop = $shop;
    }

    /**
     * Get the value of format
     *
     * @return  string
     */
    public function getFormat()
    {
        if ($this->format === null) {
            $this->format = '';
        }
        return $this->format;
    }

    /**
     * Set the value of format
     *
     * @param string $format
     *
     * @return  self
     */
    public function setFormat(string $format)
    {
        $this->format = $format;
        return $this;
    }

    /**
     * Get the value of filter1
     *
     * @return  string
     */
    public function getFilter1()
    {
        if ($this->filter1 === null) {
            $this->filter1 = '';
        }
        return $this->filter1;
    }

    /**
     * Set the value of filter1
     *
     * @param string $filter1
     *
     * @return  self
     */
    public function setFilter1(string $filter1)
    {
        $this->filter1 = $filter1;
        return $this;
    }

    /**
     * Get the value of filter2
     *
     * @return  string
     */
    public function getFilter2()
    {
        if ($this->filter2 === null) {
            $this->filter2 = '';
        }
        return $this->filter2;
    }


    /**
     * Set the value of filter2
     *
     * @param string $filter2
     *
     * @return  self
     */
    public function setFilter2(string $filter2)
    {
        $this->filter2 = $filter2;

        return $this;
    }

    /**
     * Set the value of filter3
     *
     * @param string $filter3
     *
     * @return  self
     */
    public function setFilter3(string $filter3)
    {
        $this->filter3 = $filter3;

        return $this;
    }

    /**
     * Get the value of filter3
     *
     * @return  string
     */
    public function getFilter3()
    {
        if ($this->filter3 === null) {
            $this->filter3 = '';
        }
        return $this->filter3;
    }


    /**
     *
     * @return  string
     */
    public function getStemming()
    {
        if ($this->stemming === null) {
            $this->stemming = '';
        }
        return $this->stemming;
    }

    /**
     * Set the value of stemming
     *
     * @param string $stemming
     *
     * @return  self
     */
    public function setStemming(string $stemming)
    {
        $this->stemming = $stemming;

        return $this;
    }

    /**
     * Get the value of stop
     *
     * @return  string
     */
    public function getStop()
    {
        if ($this->stop === null) {
            $this->stop = '';
        }
        return $this->stop;
    }

    /**
     * Set the value of stop
     *
     * @param string $stop
     *
     * @return  self
     */
    public function setStop(string $stop)
    {
        $this->stop = $stop;

        return $this;
    }

    /**
     * Get the value of stemming
     *
     * @return  string
     */
    public function getStemmingstop()
    {
        if ($this->stemmingstop === null) {
            $this->stemmingstop = '';
        }
        return $this->stemmingstop;
    }

    /**
     * Set the value of stemming
     *
     * @param string $stemmingstop
     *
     * @return  self
     */
    public function setStemmingstop(string $stemmingstop)
    {
        $this->stemmingstop = $stemmingstop;

        return $this;
    }

    /**
     * @return int
     */
    public function getEdge(): int
    {
        return $this->edge;
    }

    /**
     * @param int $edge
     */
    public function setEdge(int $edge): void
    {
        $this->edge = $edge;
    }

    /**
     * @return int
     */
    public function getMinedge(): int
    {
        return $this->minedge;
    }

    /**
     * @param int $minedge
     */
    public function setMinedge(int $minedge): void
    {
        $this->minedge = $minedge;
    }

    /**
     * @return string
     */
    public function getBooster(): string
    {
        if ($this->booster === null) {
            $this->booster = '';
        }
        return $this->booster;
    }

    /**
     * @param string $booster
     */
    public function setBooster(string $booster): void
    {
        $this->booster = $booster;
    }

    /**
     * @return string
     */
    public function getPattern(): string
    {
        if ($this->pattern === null) {
            $this->pattern = '';
        }
        return $this->pattern;
    }

    /**
     * @param string $pattern
     */
    public function setPattern(string $pattern): void
    {
        $this->pattern = $pattern;
    }

    /**
     * @return string|null
     */
    public function getStrip()
    {
        if ($this->strip === null) {
            $this->strip = '';
        }
        return $this->strip;
    }

    /**
     * @param string $strip
     */
    public function setStrip(string $strip): void
    {
        $this->strip = $strip;
    }

    /**
     * @return string|null
     */
    public function getStrip_str()
    {
        if ($this->strip_str === null) {
            $this->strip_str = '';
        }
        return $this->strip_str;
    }

    /**
     * @param string $strip_str ;
     */
    public function setStrip_str(string $strip_str): void
    {
        $this->strip_str = $strip_str;;
    }

    /**
     * @return string|null
     */
    public function getSynonym()
    {
        if ($this->synonym === null) {
            $this->synonym = '';
        }
        return $this->synonym;
    }

    /**
     * @param string $synonym
     */
    public function setSynonym(string $synonym): void
    {
        $this->synonym = $synonym;
    }

    /**
     * @return string
     */
    public function getFuzzy(): string
    {
        if ($this->fuzzy === null) {
            $this->fuzzy = '';
        }
        return $this->fuzzy;
    }

    /**
     * @param string $fuzzy
     */
    public function setFuzzy(string $fuzzy): void
    {
        $this->fuzzy = $fuzzy;
    }


    /**
     * @return string
     */
    public function getMaxexpansions(): string
    {
        if ($this->maxexpansions === null) {
            $this->maxexpansions = '';
        }
        return $this->maxexpansions;
    }

    /**
     * @param string $maxexpansions
     */
    public function setMaxexpansions(string $maxexpansions): void
    {
        $this->maxexpansions = $maxexpansions;
    }

    /**
     * @return string
     */
    public function getSlop(): string
    {
        if ($this->slop === null) {
            $this->slop = '';
        }
        return $this->slop;
    }

    /**
     * @param string $slop
     */
    public function setSlop(string $slop): void
    {
        $this->slop = $slop;
    }

    /**
     * @return string
     */
    public function getOperator(): string
    {
        if ($this->operator === null) {
            $this->operator = '';
        }
        return $this->operator;
    }

    /**
     * @param string $operator
     */
    public function setOperator(string $operator): void
    {
        $this->operator = $operator;
    }

    /**
     * @return string
     */
    public function getAutosynonyms(): string
    {
        if ($this->autosynonyms === null) {
            $this->autosynonyms = '';
        }
        return $this->autosynonyms;
    }

    /**
     * @param string $autosynonyms
     */
    public function setAutosynonyms(string $autosynonyms): void
    {
        $this->autosynonyms = $autosynonyms;
    }

    /**
     * @return string
     */
    public function getMinimumshouldmatch(): string
    {
        if ($this->minimumshouldmatch === null) {
            $this->minimumshouldmatch = '';
        }
        return $this->minimumshouldmatch;
    }

    /**
     * @param string $minimumshouldmatch
     */
    public function setMinimumshouldmatch(string $minimumshouldmatch): void
    {
        $this->minimumshouldmatch = $minimumshouldmatch;
    }

    /**
     * @return string
     */
    public function getPrefixlength(): string
    {
        if ($this->prefixlength === null) {
            $this->prefixlength = '';
        }
        return $this->prefixlength;
    }

    /**
     * @param string $prefixlength
     */
    public function setPrefixlength(string $prefixlength): void
    {
        $this->prefixlength = $prefixlength;
    }

    /**
     * @return string
     */
    public function getLenient(): string
    {
        if ($this->lenient === null) {
            $this->lenient = '';
        }
        return $this->lenient;
    }

    /**
     * @param string $lenient
     */
    public function setLenient(string $lenient): void
    {
        $this->lenient = $lenient;
    }

    /**
     * @return string
     */
    public function getPunctuation(): string
    {
        if ($this->punctuation === null) {
            $this->punctuation = '';
        }
        return $this->punctuation;
    }

    /**
     * @param string $punctuation
     */
    public function setPunctuation(string $punctuation): void
    {
        $this->punctuation = $punctuation;
    }

    /**
     * @return string
     */
    public function getWhitespace(): string
    {
        if ($this->whitespace === null) {
            $this->whitespace = '';
        }
        return $this->whitespace;
    }

    /**
     * @param string $whitespace
     */
    public function setWhitespace(string $whitespace): void
    {
        $this->whitespace = $whitespace;
    }

    /**
     * @return string
     */
    public function getExclude(): string
    {
        if ($this->exclude === null) {
            $this->exclude = '';
        }
        return $this->exclude;
    }

    /**
     * @param string $exclude
     */
    public function setExclude(string $exclude): void
    {
        $this->exclude = $exclude;
    }

    /**
     * @return string
     */
    public function getMerge(): string
    {
        if ($this->merge === null) {
            $this->merge = '';
        }
        return $this->merge;
    }

    /**
     * @param string $merge
     */
    public function setMerge(string $merge): void
    {
        $this->merge = $merge;
    }

    /**
     * @return string|null
     */
    public function getPrefix(): ?string
    {
        if ($this->prefix === null) {
            $this->prefix = '';
        }
        return $this->prefix;
    }

    /**
     * @param string|null $prefix
     */
    public function setPrefix(?string $prefix): void
    {
        $this->prefix = $prefix;
    }

    /**
     * @return string
     */
    public function getPhpfilter(): string
    {
        if ($this->phpfilter === null) {
            $this->phpfilter = '';
        }
        return $this->phpfilter;
    }

    /**
     * @param string $phpfilter
     */
    public function setPhpfilter(string $phpfilter): void
    {
        $this->phpfilter = $phpfilter;
    }

    /**
     * @return string|null
     */
    public function getShoplanguage(): ?string
    {
        if ($this->shoplanguage == null) {
            $this->shoplanguage = '';
        }
        return $this->shoplanguage;
    }

    /**
     * @param string|null $shoplanguage
     */
    public function setShoplanguage(?string $shoplanguage): void
    {
        if ($this->shoplanguage == null) {
            $this->shoplanguage = '';
        }
        $this->shoplanguage = $shoplanguage;
    }
    /**
     * @return string
     */
    public function getOnlymain(): string
    {
        return $this->onlymain;
    }

    /**
     * @param string $onlymain
     */
    public function setOnlymain(string $onlymain): void
    {
        $this->onlymain = $onlymain;
    }

    /**
     * @return string
     */
    public function getExcludesearch(): ?string
    {
        return $this->excludesearch;
    }

    /**
     * @param string $excludesearch
     */
    public function setExcludesearch(?string $excludesearch): void
    {
        $this->excludesearch = $excludesearch;
    }
}
