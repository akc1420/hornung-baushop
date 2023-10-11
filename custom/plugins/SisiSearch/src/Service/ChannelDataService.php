<?php

namespace Sisi\Search\Service;

use Shopware\Core\Content\Media\Pathname\UrlGeneratorInterface;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;

/**
 * Class ContextService
 * @package Sisi\Search\Service
 */
class ChannelDataService
{
    public function getDatas(
        SalesChannelProductEntity $entitie,
        array $config,
        string $lanugageId,
        UrlGeneratorInterface $urlGenerator
    ): SalesChannelProductEntity {
        if (array_key_exists('strchannel', $config)) {
            if ($config['strchannel'] === 'yes') {
                $newEnitity = new SalesChannelProductEntity();
                $tranlation = $entitie->getTranslations();
                $name = "";
                foreach ($tranlation->getElements() as $tranlationItem) {
                    $lanugageIdEntity = (string)$tranlationItem->getLanguageId();
                    if (strtolower($lanugageIdEntity) == strtolower($lanugageId)) {
                        $name = $tranlationItem->getName();
                    }
                }
                if (empty($name)) {
                    $name = $entitie->getName();
                }
                $newEnitity->setName($name);
                $newEnitity->setId($entitie->getId());
                $newEnitity->setSortedProperties($entitie->getSortedProperties());
                $newEnitity->setProductNumber($entitie->getProductNumber());
                $this->insertNewDatas($entitie, $newEnitity);
                $this->fixMediaUrl($newEnitity, $urlGenerator, $config);
                return $newEnitity;
            }
        }
        $this->fixMediaUrl($entitie, $urlGenerator, $config);


        return $entitie;
    }

    /**
     * @SuppressWarnings(PHPMD)
     *
     */
    public function fixMediaUrl(SalesChannelProductEntity &$entitie, UrlGeneratorInterface $urlGenerator, array $config)
    {
        try {
            $cover = $entitie->getCover();
            if ($cover !== null) {
                $media = $cover->getMedia();
                $url = $urlGenerator->getAbsoluteMediaUrl($media);
                $strthumbnial = false;
                if (array_key_exists('urlImage', $config)) {
                    if (!empty($config['urlImage'])) {
                        $url = $config['urlImage'] . DIRECTORY_SEPARATOR . $urlGenerator->getRelativeMediaUrl($media);
                        $strthumbnial = true;
                    }
                }
                $media->setUrl($url);
                $thumbnailsValues = $media->getThumbnails();
                if ($thumbnailsValues !== null) {
                    $thumbnails = $thumbnailsValues->getElements();
                    foreach ($thumbnails as $thumbnail) {
                        if ($strthumbnial) {
                            $thunburl = $config['urlImage'] . DIRECTORY_SEPARATOR . $urlGenerator->getRelativeThumbnailUrl($media, $thumbnail);
                        } else {
                            $thunburl = $urlGenerator->getAbsoluteThumbnailUrl($media, $thumbnail);
                        }
                        $thumbnail->setUrl($thunburl);
                    }
                }
            }
        } catch (\Exception $ex) {
        }
    }

    private function insertNewDatas(SalesChannelProductEntity $entitie, SalesChannelProductEntity &$newEnitity): void
    {
        $nummer = $entitie->getProductNumber();
        $cover = $entitie->getCover();
        $media = $entitie->getMedia();
        if ($nummer !== null && $nummer != false) {
            $entitie->setProductNumber($nummer);
        }
        if ($cover !== null && $cover != false) {
            $newEnitity->setCover($cover);
        }
        if ($media !== null && $media != false) {
            $newEnitity->setMedia($media);
        }
    }
}
