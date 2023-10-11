<?php

namespace Sisi\Search\Service;

use Symfony\Component\Console\Output\OutputInterface;

class ProgressService
{

    /**
     * @param int $percentage
     * @param int $numDecimalPlaces
     * @param OutputInterface | null $output
     */
    public function showProgressBar($percentage, $numDecimalPlaces, $output): void
    {
        if ($output !== null) {
            $percentageStringLength = 4;
            if ($numDecimalPlaces > 0) {
                $percentageStringLength += ($numDecimalPlaces + 1);
            }
            $percentageString = number_format($percentage, $numDecimalPlaces) . '%';
            $percentageString = str_pad($percentageString, $percentageStringLength, " ", STR_PAD_LEFT);
            $percentageStringLength += 3; // add 2 for () and a space before bar starts.
            $terminalWidth = `tput cols`;
            $terminalWidth = (int)$terminalWidth;
            $barWidth = $terminalWidth - ($percentageStringLength) - 2; // subtract 2 for [] around bar
            $numBars = (int)round(($percentage) / 100 * ($barWidth));
            $numEmptyBars = $barWidth - $numBars;
            $barsString = '[' . str_repeat("=", ($numBars)) . str_repeat(" ", ($numEmptyBars)) . ']';
            $outputstring = "($percentageString) " . $barsString . "\r";
            $output->writeln($outputstring);
        }
    }
}
