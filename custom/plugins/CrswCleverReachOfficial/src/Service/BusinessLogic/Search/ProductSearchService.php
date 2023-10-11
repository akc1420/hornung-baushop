<?php


namespace Crsw\CleverReachOfficial\Service\BusinessLogic\Search;

use Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\Response\SearchResult\Item;
use Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\Response\SearchResult\SearchResult;
use Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\Response\SearchResult\Settings;
use Crsw\CleverReachOfficial\Core\Infrastructure\Configuration\Configuration;
use Crsw\CleverReachOfficial\Core\Infrastructure\ServiceRegister;
use Crsw\CleverReachOfficial\Entity\Currency\Repositories\CurrencyRepository;
use Crsw\CleverReachOfficial\Entity\Media\Repositories\MediaRepository;
use Crsw\CleverReachOfficial\Entity\Product\Repositories\ProductRepository;
use Crsw\CleverReachOfficial\Entity\ProductTranslation\Repositories\ProductTranslationRepository;
use Crsw\CleverReachOfficial\Entity\SeoUrls\Repositories\SeoUrlRepository;
use Shopware\Core\Content\Media\Aggregate\MediaThumbnailSize\MediaThumbnailSizeCollection;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Media\Thumbnail\ThumbnailService;
use Shopware\Core\Content\Product\Aggregate\ProductTranslation\ProductTranslationEntity;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\Price;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class ProductSearchService
 *
 * @package Crsw\CleverReachOfficial\Service\BusinessLogic\Search
 */
class ProductSearchService
{
    public const IMAGE_SIZE = 600;

    /**
     * @var ProductRepository
     */
    private $productRepository;
    /**
     * @var ProductTranslationRepository
     */
    private $productTranslation;
    /**
     * @var CurrencyRepository
     */
    private $currencyRepository;
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;
    /**
     * @var ThumbnailService
     */
    private $thumbnailService;
    /**
     * @var EntityRepositoryInterface
     */
    private $mediaThumbnailSizeRepository;
    /**
     * @var MediaRepository
     */
    private $mediaRepository;
    /**
     * @var SeoUrlRepository
     */
    private $seoUrlRepository;

    /**
     * ProductSearchService constructor.
     *
     * @param ProductRepository $productRepository
     * @param ProductTranslationRepository $productTranslation
     * @param CurrencyRepository $currencyRepository
     * @param UrlGeneratorInterface $urlGenerator
     * @param ThumbnailService $thumbnailService
     * @param EntityRepositoryInterface $mediaThumbnailSizeRepository
     * @param MediaRepository $mediaRepository
     * @param SeoUrlRepository $seoUrlRepository
     */
    public function __construct(
        ProductRepository $productRepository,
        ProductTranslationRepository $productTranslation,
        CurrencyRepository $currencyRepository,
        UrlGeneratorInterface $urlGenerator,
        ThumbnailService $thumbnailService,
        EntityRepositoryInterface $mediaThumbnailSizeRepository,
        MediaRepository $mediaRepository,
        SeoUrlRepository $seoUrlRepository
    ) {
        $this->productRepository = $productRepository;
        $this->productTranslation = $productTranslation;
        $this->currencyRepository = $currencyRepository;
        $this->urlGenerator = $urlGenerator;
        $this->thumbnailService = $thumbnailService;
        $this->mediaThumbnailSizeRepository = $mediaThumbnailSizeRepository;
        $this->mediaRepository = $mediaRepository;
        $this->seoUrlRepository = $seoUrlRepository;
    }

    /**
     * Search products.
     *
     * @param string $searchTerm
     * @param string $language
     * @param Context $context
     *
     * @return SearchResult
     */
    public function searchProducts(string $searchTerm, string $language, Context $context): SearchResult
    {
        $result = new SearchResult();
        $settings = new Settings();
        $settings->setType(Settings::PRODUCT);
        $result->setSettings($settings);

        $productsByNumber = $this->productRepository->search($searchTerm, $context);
        $productsByName = $this->getProductsByName($searchTerm, $language, $context);

        $products = array_merge(
            $productsByNumber ? $productsByNumber->getElements() : [],
            $productsByName ? $productsByName->getElements() : []
        );

        $products = array_slice($products, 0, 100);

        foreach ($products as $product) {
            $result->addItem($this->formatProduct($product, $language, $context));
        }

        return $result;
    }

    /**
     * @param string $searchTerm
     * @param string $language
     * @param Context $context
     *
     * @return ProductCollection|null
     */
    private function getProductsByName(string $searchTerm, string $language, Context $context): ?ProductCollection
    {
        $productTranslations = $this->productTranslation->getProductsTranslationsByName(
            $searchTerm,
            $language,
            $context
        );

        if (!$productTranslations->getElements()) {
            return null;
        }

        $productIds = array_map(static function ($value) {
            return $value->getProductId();
        }, $productTranslations->getElements());

        return $this->productRepository->getProductsById($productIds, $context);
    }

    /**
     * @param ProductEntity $productEntity
     * @param string $languageId
     * @param Context $context
     *
     * @return Item
     */
    private function formatProduct(ProductEntity $productEntity, string $languageId, Context $context): Item
    {
        $productTranslations = $productEntity->getTranslations();
        $translation = null;

        if ($productTranslations) {
            $translation = $productTranslations->filterByLanguageId($languageId)->first();
        }

        if (!$productEntity->getParentId()) {
            $title = $translation ? $translation->getName() : $productEntity->getName();
            return $this->formatItem($productEntity, $translation, $title, $context);
        }

        $parent = $this->productRepository->getProductById(
            $productEntity->getParentId(),
            $context
        );

        $parentTranslations = $parent ? $parent->getTranslations() : null;
        $parentTranslation = $parentTranslations ? $parentTranslations->filterByLanguageId($languageId)->first() : null;
        $title = $this->getTitleForVariantProduct($productEntity, $languageId, $parentTranslation, $parent);

        return $this->formatItem($productEntity, $parentTranslation, $title, $context, $parent);
    }

    /**
     * @param ProductEntity $productEntity
     * @param ProductTranslationEntity|null $translation
     * @param string $title
     * @param Context $context
     * @param ProductEntity|null $parentProduct
     * @return Item
     */
    private function formatItem(
        ProductEntity $productEntity,
        ?ProductTranslationEntity $translation,
        string $title,
        Context $context,
        ProductEntity $parentProduct = null
    ): Item {
        $item = new Item($productEntity->getId());

        $item->setTitle($title);
        $item->setDescription($translation ? $translation->getDescription() : $productEntity->getDescription());
        $parentPrice = $parentProduct ?
            ($parentProduct->getPrice() ? $parentProduct->getPrice()->first(): null):null;
        $price = $productEntity->getPrice() ? $productEntity->getPrice()->first() : $parentPrice;
        $item->setPrice($this->getPrice($price, $context));
        $item->setUrl($this->getProductUrl($productEntity, $context));
        $item->setImage($this->getImageUrl($productEntity, $context, $parentProduct));

        return $item;
    }

    /**
     * @param Price|null $price
     *
     * @param Context $context
     * @return string
     */
    private function getPrice(?Price $price, Context $context): string
    {
        $priceFormatted = '';
        if ($price) {
            $priceFormatted = $price->getGross();
            $currency = $this->currencyRepository->getCurrencyById($price->getCurrencyId(), $context);
            if ($currency) {
                $priceFormatted = $currency->getIsoCode() . ' ' . $priceFormatted;
            }
        }

        return (string)$priceFormatted;
    }

    /**
     * Returns product url
     *
     * @param ProductEntity $productEntity
     * @param Context $context
     *
     * @return string
     */
    private function getProductUrl(ProductEntity $productEntity, Context $context): string
    {
        $seoUrl = $this->seoUrlRepository->getProductSeoUrl($productEntity->getId(), $context);

        if (!$seoUrl) {
            return $this->urlGenerator->generate(
                'frontend.detail.component',
                ['productId' => $productEntity->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        }

        return $this->getConfigService()->getSystemUrl(). '/' . $seoUrl->getSeoPathInfo();
    }

    /**
     * Returns image url
     *
     * @param ProductEntity $productEntity
     *
     * @param Context $context
     * @param ProductEntity|null $parentProduct
     * @return string
     */
    private function getImageUrl(ProductEntity $productEntity, Context $context, ProductEntity $parentProduct = null): string
    {
        $productMediaCollection = $productEntity->getMedia();

        if ((!$productMediaCollection || !$productMediaCollection->first()) && $parentProduct) {
            $productMediaCollection = $parentProduct->getMedia();
        }

        if (!$productMediaCollection || !$productMediaCollection->first()) {
            return '';
        }

        $media = $productMediaCollection->first()->getMedia();

        if (!$media) {
            return '';
        }

        return $this->createSmallThumbnail($media, $context);
    }

    /**
     * @param MediaEntity $media
     *
     * @return string
     */
    private function findSmallThumbnail(MediaEntity $media): string
    {
        foreach ($media->getThumbnails() as $thumbnail) {
            if ($thumbnail->getHeight() <= self::IMAGE_SIZE && $thumbnail->getWidth() <= self::IMAGE_SIZE) {
                return $thumbnail->getUrl();
            }
        }

        return '';
    }

    /**
     * @param MediaEntity $media
     * @param Context $context
     *
     * @return string
     */
    private function createSmallThumbnail(MediaEntity $media, Context $context): string
    {
        $mediaFolder = $media->getMediaFolder();

        if (!$mediaFolder) {
            return '';
        }

        $config = $mediaFolder->getConfiguration();

        if (!$config) {
            return '';
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('width', self::IMAGE_SIZE));
        $thumbnailSize = $this->mediaThumbnailSizeRepository->search($criteria, $context)->getEntities();

        if (!$thumbnailSize->first()) {
            $this->mediaThumbnailSizeRepository->create([[
                'width' => self::IMAGE_SIZE,
                'height' => self::IMAGE_SIZE
            ]], $context);

            /** @var MediaThumbnailSizeCollection $thumbnailSize */
            $thumbnailSize = $this->mediaThumbnailSizeRepository->search($criteria, $context)->getEntities();
        }

        $config->setMediaThumbnailSizes($thumbnailSize);
        $mediaFolder->setConfiguration($config);
        $this->thumbnailService->updateThumbnails($media, $context);

        $newMedia = $this->mediaRepository->getMediaById($media->getId(), $context);

        return $this->findSmallThumbnail($newMedia);
    }

    /**
     * Returns title for variant product
     *
     * @param ProductEntity $productEntity
     * @param string $languageId
     * @param ProductTranslationEntity|null $parentTranslation
     * @param ProductEntity|null $parent
     *
     * @return string
     */
    private function getTitleForVariantProduct(
        ProductEntity $productEntity,
        string $languageId,
        ?ProductTranslationEntity $parentTranslation,
        ?ProductEntity $parent
    ): string {
        if (!$parent) {
            return  '';
        }

        $title = $parentTranslation ? $parentTranslation->getName() : $parent->getName();
        $options = $productEntity->getOptions();

        foreach ($options as $option) {
            $title .= ' ';
            $optionsTranslations = $option->getTranslations();
            $optionsTranslation = $optionsTranslations ? $optionsTranslations->filterByLanguageId($languageId) : null;
            $title .= $optionsTranslation ? $optionsTranslation->get('name') : $option->getName();
        }

        return (string)$title;
    }

    /**
     * @return Configuration
     */
    private function getConfigService(): Configuration
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(Configuration::class);
    }
}
