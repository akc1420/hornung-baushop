<?php declare(strict_types=1);
/**
 * gb media
 * All Rights Reserved.
 *
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * The content of this file is proprietary and confidential.
 *
 * @category       Shopware
 * @package        Shopware_Plugins
 * @subpackage     GbmedForm
 * @copyright      Copyright (c) 2020, gb media
 * @license        proprietary
 * @author         Giuseppe Bottino
 * @link           http://www.gb-media.biz
 */

namespace Gbmed\Form\Resources\snippet\storefront\de_DE;

use Shopware\Core\System\Snippet\Files\SnippetFileInterface;

class SnippetFile_de_DE implements SnippetFileInterface
{
    public const ISO = 'de-DE';
    public const DIR = __DIR__;

    public function getIso(): string
    {
        return static::ISO;
    }

    public function getName(): string
    {
        return 'storefront.' . $this->getIso();
    }

    public function getPath(): string
    {
        return static::DIR. '/' . $this->getName() . '.json';
    }

    public function getAuthor(): string
    {
        return 'gb media';
    }

    public function isBase(): bool
    {
        return true;
    }
}
