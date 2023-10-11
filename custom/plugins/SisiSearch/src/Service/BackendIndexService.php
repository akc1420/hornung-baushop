<?php

namespace Sisi\Search\Service;

use Psr\Container\ContainerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class BackendIndexService
{
    /**
     *
     * @var integer
     */
    private $pid;


    /**
     *
     * @var string
     */
    private $command;

    /**
     * Process constructor.
     *
     */
    public function __construct(string $pfad = '')
    {
        $this->command = $pfad . "/";
    }


    public function startIndex(array $result, array $config): int
    {
        $memory = '';
        $nohup = 'nohup ';
        $phpPad = $this->getPhpPfad($config);
        $command = $memory . $this->command .
            'bin/console sisi-Produkt-Stepindex:start shopID=' . escapeshellarg($result['shopID']);
        $output = [];

        $command .= $this->mergeIndexCommand($result, 'languageID');

        if (!empty($result['limit']) && $result['limit'] > 0) {
            $command .= ' limit=' . escapeshellarg($result['limit']);
        }

        $command .= $this->mergeIndexCommand($result, 'main');
        $command .= $this->updaterIndexer($result);
        $command .= $this->mergeIndexCommand($result, 'time');
        $command .= ' backend="1" ';
        $command .= ' > ' . $this->command . 'var/log/sisi.log 2>&1 & echo $!';

        if (!empty($result['memory']) && is_numeric($result['memory'])) {
            $memory = ' -d memory_limit=' . escapeshellarg($result['memory'] . 'M') . ' ';
        }

        if (!empty($phpPad)) {
            $memory = $phpPad . $memory;
        }

        $command = $nohup . $memory . $command;

        exec($command, $output);

        if (array_key_exists(0, $output)) {
            $this->pid = (int)$output[0];
        }
        return $this->pid;
    }

    private function updaterIndexer(array $result): string
    {
        if (array_key_exists('update', $result)) {
            if ($result['update'] === '1' || $result['update'] === '2') {
                return $this->mergeIndexCommand($result, 'update');
            }
        }
        return "";
    }

    private function getPhpPfad(array $config): string
    {
        $phpPad = 'php ';
        if (array_key_exists('phpPad', $config)) {
            if (!empty($config['phpPad'])) {
                $phpPad = $config['phpPad'] . ' ';
            }
        }
        return $phpPad;
    }

    private function mergeIndexCommand(array $result, string $key): string
    {
        $command = '';
        if (!empty($result[$key])) {
            $command .= ' ' . $key . '=' . escapeshellarg($result[$key]);
        }
        return $command;
    }

    public function delete(array $result, array $config): int
    {
        $phpPad = '';
        if (array_key_exists('phpPad', $config)) {
            $phpPad = $config['phpPad'] . ' ';
        }
        $command = 'nohup ' . $phpPad . $this->command . 'bin/console sisi-Produkt-index:delete ';

        if (!empty($result['languageID'])) {
            $command .= ' languageID=' . escapeshellarg($result['languageID']);
        }

        if (!empty($result['all']) && $result['all'] > 0) {
            $command .= ' all=' . escapeshellarg($result['all']);
        }

        if (!empty($result['shopID'])) {
            $command .= ' shopID=' . escapeshellarg($result['shopID']);
        }

        $command .= ' > ' . $this->command . 'var/log/sisi.log 2>&1 & echo $!';

        exec($command, $output);

        if (array_key_exists(0, $output)) {
            $this->pid = (int)$output[0];
        }
        return $this->pid;
    }

    public function inaktive(array $result, array $config): int
    {
        $phpPad = '';
        if (array_key_exists('phpPad', $config)) {
            $phpPad = $config['phpPad'] . ' ';
        }


        $command = 'nohup ' . $phpPad . $this->command . 'bin/console  sisi-Produkt-inaktive:delete';

        if (!empty($result['languageID'])) {
            $command .= ' languageID=' . escapeshellarg($result['languageID']);
        }

        if (!empty($result['shopID'])) {
            $command .= ' shopID=' . escapeshellarg($result['shopID']);
        }

        $command .= ' > ' . $this->command . 'var/log/sisi.log 2>&1 & echo $!';

        exec($command, $output);

        if (array_key_exists(0, $output)) {
            $this->pid = (int)$output[0];
        }
        return $this->pid;
    }

    /**
     * @param string|int $pid
     * @return string
     */
    public function status($pid)
    {
        $command = 'ps -p ' . $pid;
        return shell_exec($command);
    }

    public function getLog(): array
    {
        $pfad = $this->command . 'var/log/sisi.log';
        return file($pfad);
    }

    public function getChannel(ContainerInterface $container): array
    {
        $saleschannel = $container->get('sales_channel.repository');
        $criteriaChannel = new Criteria();
        $contexhaendler = new ContextService();
        $context = $contexhaendler->getContext();
        return $saleschannel->search($criteriaChannel, $context)->getEntities()->getElements();
    }

    public function getLanguages(ContainerInterface $container): array
    {
        $language = $container->get('language.repository');
        $criteriaChannel = new Criteria();
        $contexhaendler = new ContextService();
        $context = $contexhaendler->getContext();
        return $language->search($criteriaChannel, $context)->getEntities()->getElements();
    }
}
