<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT15673;

use Shopware\Core\Content\Media\File\FileUrlValidatorInterface;
use Swag\Security\Components\AbstractSecurityFix;

class SecurityFix extends AbstractSecurityFix implements FileUrlValidatorInterface
{
    /**
     * @var FileUrlValidatorInterface
     */
    private $inner;

    public function __construct(FileUrlValidatorInterface $inner)
    {
        $this->inner = $inner;
    }

    public static function getTicket(): string
    {
        return 'NEXT-15673';
    }

    public static function getMinVersion(): string
    {
        return '6.2.0';
    }

    public static function getMaxVersion(): ?string
    {
        return '6.4.3.1';
    }

    public function isValid(string $source): bool
    {
        if (!$this->inner->isValid($source)) {
            return false;
        }

        $host = parse_url($source, \PHP_URL_HOST);
        $ip = gethostbyname($host);

        // Potentially IPv6
        $ip = trim($ip, '[]');

        if (!filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV6)) {
            return true;
        }

        // Convert IPv6 to packed format and back so we can check if there is a IPv4 representation of the IP
        $packedIp = inet_pton($ip);
        if (!$packedIp) {
            return false;
        }
        $convertedIp = inet_ntop($packedIp);
        if (!$convertedIp) {
            return false;
        }
        $convertedIp = explode(':', $convertedIp);
        $ipv4 = array_pop($convertedIp);

        // Additionally filter IPv4 representation of the IP
        if (filter_var($ipv4, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4)) {
            return $this->inner->isValid('https://' . $ipv4);
        }

        return true;
    }
}
