<?php declare(strict_types=1);

namespace Sensus\Check24Connect\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class FTPService
{
    use LocalDirAwareTrait;

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    /**
     * @var resource|null
     */
    protected $stream = NULL;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * FTPService constructor.
     * @param SystemConfigService $systemConfigService
     * @param LoggerInterface $logger
     */
    public function __construct(SystemConfigService $systemConfigService, LoggerInterface $logger)
    {
        $this->systemConfigService = $systemConfigService;
        $this->logger = $logger;
    }

    public function initConfig(SalesChannelEntity $salesChannel): bool
    {
        $tlsInactive = $this->systemConfigService->get('SensusCheck24Connect.config.FTPUnsecure', $salesChannel->getId());
        return $this->connect(
            $this->systemConfigService->get('SensusCheck24Connect.config.FTPHost', $salesChannel->getId()),
            $this->systemConfigService->get('SensusCheck24Connect.config.FTPUsername', $salesChannel->getId()),
            $this->systemConfigService->get('SensusCheck24Connect.config.FTPPassword', $salesChannel->getId()),
            $this->systemConfigService->get('SensusCheck24Connect.config.FTPPort', $salesChannel->getId()),
            ($tlsInactive ? true : false)
        );
    }

    protected function connect(string $host, string $username, string $password, int $port, bool $tlsInactive): bool
    {
        $this->close();

        if ($tlsInactive) {
            $stream = ftp_connect($host, $port);
        } else {
            $stream = ftp_ssl_connect($host, $port);
        }
        if ($stream && ftp_login($stream, $username, $password)) {
            ftp_pasv($stream, true);
            ftp_chdir($stream, '/outbound');
            $this->stream = $stream;
            return true;
        }

        $this->logger->error('Could not connect to Check24 FTP Server. Please check your FTP credentials.');
        return false;
    }

    public function close(): void
    {
        if ($this->stream) {
            ftp_close($this->stream);
            $this->stream = NULL;
        }
    }

    /**
     * gets all files in the outbound directory
     *
     * @return null|string[]
     */
    public function getFileList(): ?array
    {
        $fileList = ftp_nlist($this->stream, '.');

        if ($fileList === NULL || $fileList === FALSE) {
            $this->logger->error('Could not receive file list from Check24 FTP Server. Please check the firewall configuration of your server.');
        }

        return $fileList;
    }

    /**
     * @param string|null $lastFilename
     * @return string|null
     */
    public function getNextFile(?string $lastFilename): ?string
    {
        if ($lastFilename) {
            ftp_rename($this->stream, $lastFilename, '../backup/' . $lastFilename);
        }

        foreach ($this->getFileList() as $filename) {
            if ($filename !== 'orderapitranslator' && strpos($filename, 'ORDER.xml') !== FALSE) {

                if (!file_exists($this->getLocalDir() . '/' . $filename)) {
                    ftp_get($this->stream, $this->getLocalDir() . '/' . $filename, $filename, FTP_ASCII);

                    return $filename;
                } else {
                    ftp_rename($this->stream, $filename, '../backup/' . $filename);
                }

            }
        }

        return NULL;
    }

}