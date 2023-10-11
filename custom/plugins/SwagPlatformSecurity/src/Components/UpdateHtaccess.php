<?php declare(strict_types=1);

namespace Swag\Security\Components;

class UpdateHtaccess
{
    private const MARKER_START = '# BEGIN Shopware';
    private const MARKER_STOP = '# END Shopware';
    private const INSTRUCTIONS = '# The directives (lines) between "# BEGIN Shopware" and "# END Shopware" are dynamically generated. Any changes to the directives between these markers will be overwritten.';

    private const OLD_FILES = [
        '9ab5be8c4bbff3490f3ae367af8a30d7', // https://github.com/shopware/production/commit/bebf9adc90bf5d7b0d53a149cc5bdba328696086
        'ba812f2a64b337b032b10685ca6e2308', // https://github.com/shopware/production/commit/18ce6ffc904b8d2d237dc4ee6654c1fa9a6df719
    ];

    /**
     * Returns the version in which the changes to the `current_public_htaccess.dist` were introduced to the core
     * The htaccess will not be overwritten for the version returned and newer versions
     * Adjust this version when updating the `current_public_htaccess.dist`
     */
    public function getMaxVersion(): string
    {
        return '6.4.3.1';
    }

    public function updateHtaccess(string $publicHtaccessPath, string $currentHtaccessPath): void
    {
        if (!file_exists($publicHtaccessPath) || !file_exists($currentHtaccessPath)) {
            return;
        }

        if (\in_array(md5_file($publicHtaccessPath), self::OLD_FILES, true)) {
            $this->replaceFile($publicHtaccessPath, $currentHtaccessPath);

            return;
        }

        $content = file_get_contents($publicHtaccessPath);

        // User has deleted the markers. So we will ignore the update process
        if (strpos($content, self::MARKER_START) === false || strpos($content, self::MARKER_STOP) === false) {
            return;
        }

        $this->updateByMarkers($publicHtaccessPath, $currentHtaccessPath);
    }

    /**
     * Replace entire .htaccess from dist
     */
    private function replaceFile(string $path, string $dist): void
    {
        if (!file_exists($dist)) {
            return;
        }

        $perms = fileperms($dist);
        copy($dist, $path);

        if ($perms) {
            chmod($path, $perms | 0644);
        }
    }

    private function updateByMarkers(string $path, string $dist): void
    {
        [$pre, $_, $post] = $this->getLinesFromMarkedFile($path);
        [$_, $existing, $_] = $this->getLinesFromMarkedFile($dist);

        if (!\in_array(self::INSTRUCTIONS, $existing, true)) {
            array_unshift($existing, self::INSTRUCTIONS);
        }

        array_unshift($existing, self::MARKER_START);
        $existing[] = self::MARKER_STOP;

        $newFile = implode("\n", array_merge($pre, $existing, $post));

        $perms = fileperms($path);
        file_put_contents($path, $newFile);

        if ($perms) {
            chmod($path, $perms | 0644);
        }
    }

    private function getLinesFromMarkedFile(string $path): array
    {
        $fp = fopen($path, 'rb+');
        if (!$fp) {
            return [];
        }

        $lines = [];
        while (!feof($fp)) {
            if ($line = fgets($fp)) {
                $lines[] = rtrim($line, "\r\n");
            }
        }

        $foundStart = false;
        $foundStop = false;
        $preLines = [];
        $postLines = [];
        $existingLines = [];

        foreach ($lines as $line) {
            if (!$foundStart && strpos($line, self::MARKER_START) === 0) {
                $foundStart = true;

                continue;
            }

            if (!$foundStop && strpos($line, self::MARKER_STOP) === 0) {
                $foundStop = true;

                continue;
            }

            if (!$foundStart) {
                $preLines[] = $line;
            } elseif ($foundStop) {
                $postLines[] = $line;
            } else {
                $existingLines[] = $line;
            }
        }

        return [$preLines, $existingLines, $postLines];
    }
}
