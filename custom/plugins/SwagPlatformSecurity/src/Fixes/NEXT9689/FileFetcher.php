<?php declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT9689;

use Shopware\Core\Content\Media\Exception\IllegalUrlException;
use Shopware\Core\Content\Media\Exception\MissingFileExtensionException;
use Shopware\Core\Content\Media\Exception\UploadException;
use Shopware\Core\Content\Media\File\FileUrlValidatorInterface;
use Shopware\Core\Content\Media\File\MediaFile;
use Swag\Security\Components\State;
use Symfony\Component\HttpFoundation\Request;

class FileFetcher extends \Shopware\Core\Content\Media\File\FileFetcher
{
    private const ALLOWED_PROTOCOLS = ['http', 'https', 'ftp', 'sftp'];

    /**
     * @var array
     */
    private $constructorArgs;

    /**
     * @var State
     */
    private $state;

    /**
     * @var FileUrlValidatorInterface
     */
    private $fileUrlValidator;

    public function __construct(array $constructorArgs, State $state, FileUrlValidatorInterface $fileUrlValidator)
    {
        $this->constructorArgs = $constructorArgs;
        $this->state = $state;
        $this->fileUrlValidator = $fileUrlValidator;

        // @codeCoverageIgnoreStart
        if (method_exists(get_parent_class($this), '__construct')) {
            parent::__construct(... $constructorArgs);
        }
        // @codeCoverageIgnoreEnd
    }

    public function fetchFileFromURL(Request $request, string $fileName): MediaFile
    {
        if (!$this->state->isActive('NEXT-9689')) {
            return parent::fetchFileFromURL($request, $fileName);
        }

        $url = $this->getUrlFromRequest($request);
        if (!$this->fileUrlValidator->isValid($url)) {
            throw new IllegalUrlException($url);
        }

        $extension = $this->getExtensionFromRequest($request);

        $inputStream = $this->openSourceFromUrl($url);
        $destStream = $this->openDestinationStream($fileName);

        try {
            $writtenBytes = $this->copyStreams($inputStream, $destStream);
        } finally {
            fclose($inputStream);
            fclose($destStream);
        }

        return new MediaFile(
            $fileName,
            mime_content_type($fileName),
            $extension,
            $writtenBytes
        );
    }

    /**
     * @throws UploadException
     *
     * @return resource
     */
    private function openSourceFromUrl(string $url)
    {
        $streamContext = stream_context_create([
            'http' => [
                'follow_location' => 0,
                'max_redirects' => 0,
            ],
        ]);
        $inputStream = @fopen($url, 'rb', false, $streamContext);

        if ($inputStream === false) {
            throw new UploadException("Could not open source stream from {$url}");
        }

        return $inputStream;
    }

    /**
     * @throws UploadException
     *
     * @return resource
     */
    private function openDestinationStream(string $filename)
    {
        $inputStream = @fopen($filename, 'wb');

        if ($inputStream === false) {
            throw new UploadException("Could not open Stream to write upload data: ${filename}");
        }

        return $inputStream;
    }

    /**
     * @param resource|string $sourceStream
     * @param resource        $destStream
     */
    private function copyStreams($sourceStream, $destStream): int
    {
        $writtenBytes = stream_copy_to_stream($sourceStream, $destStream);

        if ($writtenBytes === false) {
            throw new UploadException('Error while copying media from source');
        }

        return $writtenBytes;
    }

    /**
     * @throws MissingFileExtensionException
     */
    private function getExtensionFromRequest(Request $request): string
    {
        $extension = $request->query->get('extension');
        if ($extension === null) {
            throw new MissingFileExtensionException();
        }

        return $extension;
    }

    /**
     * @throws UploadException
     */
    private function getUrlFromRequest(Request $request): string
    {
        $url = $request->request->get('url');

        if ($url === null) {
            throw new UploadException('You must provide a valid url.');
        }

        if (!$this->isUrlValid($url)) {
            throw new UploadException('malformed url: ' . $url);
        }

        return $url;
    }

    private function isUrlValid(string $url): bool
    {
        return (bool) filter_var($url, FILTER_VALIDATE_URL) && $this->isProtocolAllowed($url);
    }

    private function isProtocolAllowed(string $url): bool
    {
        $fragments = explode(':', $url);
        if (count($fragments) > 1) {
            return in_array($fragments[0], self::ALLOWED_PROTOCOLS, true);
        }

        return false;
    }
}
