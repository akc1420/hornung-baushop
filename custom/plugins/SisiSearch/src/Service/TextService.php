<?php

namespace Sisi\Search\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\Console\Output\OutputInterface;

class TextService
{
    /**
     * @param OutputInterface|null $output
     * @param string $message
     *
     *  @return void
     */
    public function write($output, $message)
    {
        if ($output !== null) {
            $output->writeln($message);
        }
    }

    public function stripOption(array $options): array
    {
        $return = [];
        foreach ($options as &$option) {
            $this->helpCondition($option, 'time', $return);
            $this->helpCondition($option, 'step', $return);
            $this->helpCondition($option, 'update', $return);
            $this->helpCondition($option, 'limit', $return);
            $this->helpCondition($option, 'offset', $return);
            $this->helpCondition($option, 'main', $return);
            $this->helpCondition($option, 'language', $return);
            $this->helpCondition($option, 'languageID', $return);
            $this->helpCondition($option, 'backend', $return);
        }
        return $return;
    }

    /**
     * @param string|null $option
     * @param string|null $index
     * @param array $return
     *
     * @return void
     */

    private function helpCondition($option, $index, &$return): void
    {
        if ($this->searchOption($option, $index)) {
            $return[$index] = $option;
        }
    }

    public function stripOption2(array $options): array
    {
        $return = [];
        foreach ($options as &$option) {
            if ($this->searchOption($option, 'language')) {
                $return['language'] = $option;
            }
            if ($this->searchOption($option, 'languageID')) {
                $return['languageID'] = $option;
            }

            if ($this->searchOption($option, 'all')) {
                $return['all'] = $option;
            }

            if ($this->searchOption($option, 'shop')) {
                $return['shop'] = $option;
            }
            if ($this->searchOption($option, 'delete')) {
                $return['delete'] = $option;
            }
        }
        return $return;
    }

    /**
     * @param string|null $option
     * @param string|null $string
     * @return bool
     */
    private function searchOption(&$option, $string)
    {
        $pos = strpos($option, $string);
        if ($pos !== false) {
            $option = str_replace($string . "=", "", $option);
            return true;
        }
        return false;
    }
}
