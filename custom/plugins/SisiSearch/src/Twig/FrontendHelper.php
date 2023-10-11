<?php

namespace Sisi\Search\Twig;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * A twig extension that will add an "evaluate" filter, for dynamic evaluation.
 */
class FrontendHelper extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('sisiFrontendhelperMerker', [$this, 'sisiFrontendhelperMerker']),
            new TwigFilter('sisiRemoveAfterBR', [$this, 'sisiRemoveAfterBR']),
        ];
    }


    /**
     * This function will evaluate $string through the $environment, and return its results.
     * @param string|null $string
     * @param array $config
     * @param string |null $productname
     *
     * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity )
     *
     * @return string
     */
    public function sisiRemoveAfterBR($string, $config, $productname): string
    {
        $return = '';
        if (!empty($string)) {
            $values = explode("|", $string);
            $strname = true;


            if (!array_key_exists('displayhighlight', $config)) {
                $config['displayhighlight'] = '1';
            }
            if ($config['displayhighlight'] === '2') {
                $return = $values[0];
            }

            if (count($values)  === 1) {
                return  strip_tags($values[0]);
            }
            if ($config['displayhighlight'] === '3' || $config['displayhighlight'] === '1') {
                $index = 0;
                foreach ($values as $key => $value) {
                    if (strpos($value, '<em>') !== false) {
                        if ($index === 0) {
                            $return .= $value;
                            $strname = false;
                        } else {
                            $return .= " " . $value;
                        }
                    }
                    $index++;
                }
            }
            if ($strname && !empty($productname) && is_string($productname)) {
                $productnamevalues = explode("|", $productname);
                $return = $productnamevalues[0] . " " . $return;
            }

            return strip_tags($return);
        } else {
            return "";
        }
    }

    /**
     * This function will evaluate $string through the $environment, and return its results.
     * @param array $merker
     * @param string|null $name
     * @return bool
     */
    public function sisiFrontendhelperMerker($merker, $name): bool
    {
        if (in_array($name, $merker)) {
            return false;
        }
        return true;
    }

    /**
     * Returns the name of this extension.
     *
     * @return string
     */
    public function getName()
    {
        return 'sisiFrontendhelper';
    }
}
