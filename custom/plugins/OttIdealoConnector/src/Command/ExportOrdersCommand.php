<?php declare(strict_types=1);

namespace Ott\IdealoConnector\Command;

use Ott\IdealoConnector\Service\CronjobService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportOrdersCommand extends Command
{
    private CronjobService $cronjobService;

    public function __construct(CronjobService $cronjobService, ?string $name = null)
    {
        parent::__construct($name);
        $this->cronjobService = $cronjobService;
    }

    public function configure(): void
    {
        $this->setName('ott:idealo:order:export');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $results = $this->cronjobService->transferOrderStatesToIdealo();

        foreach ($results as $salesChannelId => $result) {
            $output->writeln($result);
        }

        return Command::SUCCESS;
    }
}
