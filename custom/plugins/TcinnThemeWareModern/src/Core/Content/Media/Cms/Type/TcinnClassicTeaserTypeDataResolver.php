<?php declare(strict_types=1);

namespace TcinnThemeWareModern\Core\Content\Media\Cms\Type;

use Shopware\Core\Content\Media\Cms\ImageCmsElementResolver;

class TcinnClassicTeaserTypeDataResolver extends ImageCmsElementResolver
{
    public function getType(): string
    {
        return 'twt-classic-teaser';
    }
}