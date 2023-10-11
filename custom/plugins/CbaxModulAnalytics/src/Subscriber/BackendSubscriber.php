<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Subscriber;

use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Content\Product\Events\ProductSearchResultEvent;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Shopware\Storefront\Page\Search\SearchPageLoadedEvent;
use Shopware\Storefront\Page\LandingPage\LandingPageLoadedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Defaults;
use Doctrine\DBAL\Connection;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Shopware\Storefront\Page\Navigation\NavigationPageLoadedEvent;

class BackendSubscriber implements EventSubscriberInterface
{
    const MODUL_NAME = 'CbaxModulAnalytics';

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    /**
     * @var
     */
    private $config = null;

    /**
     * @var EntityRepositoryInterface
     */
    private $searchRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var Connection
     */
    private $connection;

    const DEFAULT_DEVICES = [
        'desktop',
        'tablet',
        'mobile'
    ];

    public function __construct(
        SystemConfigService $systemConfigService,
        EntityRepositoryInterface $searchRepository,
        EntityRepositoryInterface $orderRepository,
        Connection $connection
    )
    {
        $this->systemConfigService = $systemConfigService;
        $this->orderRepository = $orderRepository;
        $this->searchRepository = $searchRepository;
        $this->connection = $connection;
    }

    public static function getSubscribedEvents(): array
    {
        return[
            ProductSearchResultEvent::class => ['onProductSearch', -10],
            ProductPageLoadedEvent::class => ['onProductPageLoaded', -10],
            NavigationPageLoadedEvent::class => ['onNavigationPageLoaded', -10],
            LandingPageLoadedEvent::class => ['onLandingPageLoaded', -10],
            SearchPageLoadedEvent::class => ['onSearchPageLoaded', -10],
            CheckoutFinishPageLoadedEvent::class => ['onOrderFinished', -10]
        ];
    }

    public function onLandingPageLoaded(LandingPageLoadedEvent $event)
    {
        if (empty($_SERVER)) return;
        if (empty($_SERVER['HTTP_USER_AGENT'])) return;
        $httpUserAgent = $_SERVER['HTTP_USER_AGENT'];
        if ($this->botDetected($httpUserAgent)) return;

        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelID();
        $this->config = $this->config ?? ($this->systemConfigService->get(self::MODUL_NAME . '.config', $salesChannelId) ?? []);
        if (empty($this->config['recordVisitors'])) return;

        $salesChannelIdBytes = Uuid::fromHexToBytes($salesChannelId);
        $deviceType = $this->getDeviceType($httpUserAgent);
        $date = (new \DateTime())->format(Defaults::STORAGE_DATE_FORMAT);
        $createdAt = (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        /// Visitors and Page Impressions
        $this->handleVisitorCount($event,$salesChannelIdBytes,$httpUserAgent,$deviceType,$date,$createdAt);
        ////////////////////////
    }

    public function onSearchPageLoaded(SearchPageLoadedEvent $event)
    {
        if (empty($_SERVER)) return;
        if (empty($_SERVER['HTTP_USER_AGENT'])) return;
        $httpUserAgent = $_SERVER['HTTP_USER_AGENT'];
        if ($this->botDetected($httpUserAgent)) return;

        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelID();
        $this->config = $this->config ?? ($this->systemConfigService->get(self::MODUL_NAME . '.config', $salesChannelId) ?? []);
        if (empty($this->config['recordVisitors'])) return;

        $salesChannelIdBytes = Uuid::fromHexToBytes($salesChannelId);
        $deviceType = $this->getDeviceType($httpUserAgent);
        $date = (new \DateTime())->format(Defaults::STORAGE_DATE_FORMAT);
        $createdAt = (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        /// Visitors and Page Impressions
        $this->handleVisitorCount($event,$salesChannelIdBytes,$httpUserAgent,$deviceType,$date,$createdAt);
        ////////////////////////
    }

    public function onNavigationPageLoaded(NavigationPageLoadedEvent $event)
    {
        if (empty($_SERVER)) return;
        if (empty($_SERVER['HTTP_USER_AGENT'])) return;
        $httpUserAgent = $_SERVER['HTTP_USER_AGENT'];
        if ($this->botDetected($httpUserAgent)) return;

        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelID();
        $this->config = $this->config ?? ($this->systemConfigService->get(self::MODUL_NAME . '.config', $salesChannelId) ?? []);
        if (empty($this->config['recordVisitors'])) return;

        $salesChannelIdBytes = Uuid::fromHexToBytes($salesChannelId);
        $deviceType = $this->getDeviceType($httpUserAgent);
        $date = (new \DateTime())->format(Defaults::STORAGE_DATE_FORMAT);
        $createdAt = (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);
        $customerGroupIdBytes = $this->getCustomerGroupId($event->getSalesChannelContext());

        /// Visitors and Page Impressions
        $this->handleVisitorCount($event,$salesChannelIdBytes,$httpUserAgent,$deviceType,$date,$createdAt);
        ////////////////////////

        $categoryId = $event->getPage()->getNavigationId();
        if (empty($categoryId)) return;

        $randomId = Uuid::randomBytes();
        try {
            $this->connection->executeUpdate('
                INSERT INTO `cbax_analytics_category_impressions`
                    (`id`, `category_id`, `sales_channel_id`, `customer_group_id`, `date`, `impressions`, `device_type`, `created_at`)
                VALUES
                    (:id, :category_id, :sales_channel_id, :customer_group_id, :date, :impressions, :device_type, :created_at)
                    ON DUPLICATE KEY UPDATE impressions=impressions+1;',
                [
                    'id' => $randomId,
                    'category_id' => Uuid::fromHexToBytes($categoryId),
                    'sales_channel_id' => $salesChannelIdBytes,
                    'customer_group_id' => $customerGroupIdBytes,
                    'date' => $date,
                    'impressions' => 1,
                    'device_type' => $deviceType,
                    'created_at' => $createdAt
                ]
            );

        } catch(\Exception $e) {

        }
    }

    public function onProductPageLoaded(ProductPageLoadedEvent $event)
    {
        if (empty($_SERVER)) return;
        if (empty($_SERVER['HTTP_USER_AGENT'])) return;
        $httpUserAgent = $_SERVER['HTTP_USER_AGENT'];
        if ($this->botDetected($httpUserAgent)) return;

        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelID();
        $this->config = $this->config ?? ($this->systemConfigService->get(self::MODUL_NAME . '.config', $salesChannelId) ?? []);
        if (empty($this->config['recordVisitors'])) return;

        $page = $event->getPage();
        if (empty($page->getProduct())) return;
        $salesChannelIdBytes = Uuid::fromHexToBytes($salesChannelId);
        $productId = $page->getProduct()->getId();
        $date = (new \DateTime())->format(Defaults::STORAGE_DATE_FORMAT);
        $deviceType = $this->getDeviceType($httpUserAgent);
        $createdAt = (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);
        $customerGroupIdBytes = $this->getCustomerGroupId($event->getSalesChannelContext());

        /// Visitors and Page Impressions
        $this->handleVisitorCount($event,$salesChannelIdBytes,$httpUserAgent,$deviceType,$date,$createdAt);
        ////////////////////////

        $randomId = Uuid::randomBytes();
        try {
            $this->connection->executeUpdate('
                INSERT INTO `cbax_analytics_product_impressions`
                    (`id`, `product_id`, `sales_channel_id`, `customer_group_id`, `date`, `impressions`, `device_type`, `created_at`)
                VALUES
                    (:id, :product_id, :sales_channel_id, :customer_group_id, :date, :impressions, :device_type, :created_at)
                    ON DUPLICATE KEY UPDATE impressions=impressions+1;',
                [
                    'id' => $randomId,
                    'product_id' => Uuid::fromHexToBytes($productId),
                    'sales_channel_id' => $salesChannelIdBytes,
                    'customer_group_id' => $customerGroupIdBytes,
                    'date' => $date,
                    'impressions' => 1,
                    'device_type' => $deviceType,
                    'created_at' => $createdAt
                ]
            );
        } catch(\Exception $e) {

        }

        $manufacturer = $page->getProduct()->getManufacturer();
        if (empty($manufacturer)) return;

        $manufacturerId = $manufacturer->getId();

        $randomId = Uuid::randomBytes();
        try {
            $this->connection->executeUpdate('
                INSERT INTO `cbax_analytics_manufacturer_impressions`
                    (`id`, `manufacturer_id`, `sales_channel_id`, `customer_group_id`, `date`, `impressions`, `device_type`, `created_at`)
                VALUES
                    (:id, :manufacturer_id, :sales_channel_id, :customer_group_id, :date, :impressions, :device_type, :created_at)
                    ON DUPLICATE KEY UPDATE impressions=impressions+1;',
                [
                    'id' => $randomId,
                    'manufacturer_id' => Uuid::fromHexToBytes($manufacturerId),
                    'sales_channel_id' => $salesChannelIdBytes,
                    'customer_group_id' => $customerGroupIdBytes,
                    'date' => $date,
                    'impressions' => 1,
                    'device_type' => $deviceType,
                    'created_at' => $createdAt
                ]
            );
        } catch(\Exception $e) {

        }
    }

    public function onOrderFinished(CheckoutFinishPageLoadedEvent $event)
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();
        $this->config = $this->config ?? ($this->systemConfigService->get(self::MODUL_NAME . '.config', $salesChannelId) ?? []);

        if (empty($this->config['recordAdditionalOrderData'])) return;
        if (empty($_SERVER)) return;
        if (empty($_SERVER['HTTP_USER_AGENT'])) return;

        $order = $event->getPage()->getOrder();
        if (empty($order)) return;

        $httpUserAgent = $_SERVER['HTTP_USER_AGENT'];
        $customFields = $order->getCustomFields() ?? [];
        $context = $event->getContext();

        $customFields['cbaxStatistics'] = [
            'device' => $this->getDeviceType($httpUserAgent),
            'os' => $this->getOS($httpUserAgent),
            'browser' => $this->getBrowser($httpUserAgent)
        ];

        $data = [
            [
                'id' => $order->getId(),
                'customFields' => $customFields
            ]
        ];

        $this->orderRepository->update($data, $context);
    }

	public function onProductSearch(ProductSearchResultEvent $event)
    {
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelID();
        $this->config = $this->config ?? ($this->systemConfigService->get(self::MODUL_NAME . '.config', $salesChannelId) ?? []);
        if (empty($this->config['recordSearch'])) return;

        $requestUri = $event->getRequest()->attributes->get('sw-original-request-uri');
        if (empty($requestUri)) return;
        if (str_starts_with($requestUri, '/widgets')) return;

        $searchUriArray = explode('=', $requestUri);
        $searchTerm = count($searchUriArray) > 1 ? strtolower(urldecode ($searchUriArray[1])) : '';
        if (empty($searchTerm)) return;

        $results = $event->getResult()->getTotal();
        $context = $event->getContext();

        $this->searchRepository->create([
            [
                'searchTerm' => $searchTerm,
                'results' => $results,
                'salesChannelId' => $salesChannelId
            ]
        ], $context);
    }

    private function botDetected($httpUserAgent)
    {
        return is_string($httpUserAgent) && preg_match('/bot|crawl|slurp|spider|mediapartners/i', $httpUserAgent);
    }

    private function handleVisitorCount($event, $salesChannelIdBytes, $httpUserAgent, $deviceType, $date, $createdAt)
    {
        $request = $event->getRequest();
        $referer = $this->getDomainString($request->headers->get('referer'));
        $host = $this->getDomainString($request->getHttpHost());
        $visitorHash = hash('md5', $request->getClientIp() . $httpUserAgent);
        $isNewVisitor = false;

        $sql = "
        SELECT `id` FROM `cbax_analytics_pool` WHERE `date` = ? AND `remote_address` = ? AND `sales_channel_id` = ?;
        ";
        try {
            $poolResult = $this->connection->fetchOne($sql, [$date,$visitorHash,$salesChannelIdBytes]);
        } catch (\Exception $e) {

        }

        if (empty($poolResult)) {
            $randomId = Uuid::randomBytes();
            try {
                $this->connection->executeUpdate('
                INSERT IGNORE INTO `cbax_analytics_pool`
                    (`id`, `date`, `remote_address`, `sales_channel_id`, `created_at`)
                VALUES
                    (:id, :date, :remote_address, :sales_channel_id, :created_at);',
                    [
                        'id' => $randomId,
                        'date' => $date,
                        'remote_address' => $visitorHash,
                        'sales_channel_id' => $salesChannelIdBytes,
                        'created_at' => $createdAt
                    ]
                );
                $isNewVisitor = true;
            } catch(\Exception $e) {

            }
        }

        if ($isNewVisitor)
        {
            $randomId = Uuid::randomBytes();
            try {
                $this->connection->executeUpdate('
                INSERT INTO `cbax_analytics_visitors`
                    (`id`, `sales_channel_id`, `date`,`page_impressions`, `unique_visits`, `device_type`, `created_at`)
                VALUES
                    (:id, :sales_channel_id, :date, :page_impressions, :unique_visits, :device_type, :created_at)
                    ON DUPLICATE KEY UPDATE page_impressions=page_impressions+1, unique_visits=unique_visits+1;',
                    [
                        'id' => $randomId,
                        'sales_channel_id' => $salesChannelIdBytes,
                        'date' => $date,
                        'page_impressions' => 1,
                        'unique_visits' => 1,
                        'device_type' => $deviceType,
                        'created_at' => $createdAt
                    ]
                );
            } catch(\Exception $e) {

            }

        } else {

            try {
                $this->connection->executeUpdate('
                UPDATE `cbax_analytics_visitors` SET page_impressions=page_impressions+1
                WHERE `sales_channel_id`=? AND `date`=? AND `device_type`=?;',

                    [$salesChannelIdBytes, $date, $deviceType]

                );
            } catch(\Exception $e) {

            }
        }

        if (!empty($referer) && $referer != $host)
        {
            $randomId = Uuid::randomBytes();
            try {
                $this->connection->executeUpdate('
                INSERT INTO `cbax_analytics_referer`
                    (`id`, `date`,`referer`, `sales_channel_id`, `counted`, `device_type`, `created_at`)
                VALUES
                    (:id, :date, :referer, :sales_channel_id, :counted, :device_type, :created_at)
                    ON DUPLICATE KEY UPDATE counted=counted+1;',
                    [
                        'id' => $randomId,
                        'date' => $date,
                        'referer' => $referer,
                        'sales_channel_id' => $salesChannelIdBytes,
                        'counted' => 1,
                        'device_type' => $deviceType,
                        'created_at' => $createdAt
                    ]
                );
            } catch(\Exception $e) {

            }
        }
    }

    private function getCustomerGroupId($salesChannelContext)
    {
        $customerId = $salesChannelContext->getCustomerId();

        if (!empty($customerId) && !empty($salesChannelContext->getCurrentCustomerGroup()))
        {
            return !empty($salesChannelContext->getCurrentCustomerGroup()->getId()) ?
                Uuid::fromHexToBytes($salesChannelContext->getCurrentCustomerGroup()->getId()) :
                null;

        } else {

            return null;
        }
    }

    private function getDomainString($url)
    {
        if (empty($url)) {
            return '';
        }

        $domainStr = str_replace(['http://', 'https://', 'www.'], '', $url);
        $domainArr = explode('/', $domainStr);

        return $domainArr[0];
    }

    private function getDeviceType($httpUserAgent)
    {
        $httpUserAgent = (string)$httpUserAgent;

        if (!empty($_COOKIE) && !empty($_COOKIE['x-ua-device']))
        {
            $deviceType = strtolower($_COOKIE['x-ua-device']);
            if (in_array($deviceType, self::DEFAULT_DEVICES))
            {
                return $deviceType;
            }
        }

        $os = $this->getOS($httpUserAgent);
        $mobileOS = ['Windows Phone 10','Windows Phone 8.1','Windows Phone 8','BlackBerry','Mobile'];
        $tabletOS = ['Android','iOS'];

        if (preg_match('/mobile|phone|ipod/i', $httpUserAgent) || in_array($os, $mobileOS))
        {
            return 'mobile';
        }

        if (preg_match('/tablet|ipad/i', $httpUserAgent) || in_array($os, $tabletOS))
        {
            return 'tablet';
        }
        return 'desktop';
    }

    private function getOS($httpUserAgent)
    {
        $httpUserAgent = (string)$httpUserAgent;

        foreach (self::OS as $key => $value) {
            if (preg_match($key, $httpUserAgent)) {
                return $value;
            }
        }

        return 'Not Detected';
    }

    private function getBrowser($httpUserAgent)
    {
        $httpUserAgent = (string)$httpUserAgent;

        foreach (self::BROWSER as $key => $value) {
            if (preg_match($key, $httpUserAgent)) {
                return $value;
            }
        }

        return 'Not Detected';
    }

    const OS = [
        '/windows nt 11/i'      =>  'Windows 11',
        '/windows nt 10/i'      =>  'Windows 10',
        '/windows phone 10/i'   =>  'Windows Phone 10',
        '/windows phone 8.1/i'  =>  'Windows Phone 8.1',
        '/windows phone 8/i'    =>  'Windows Phone 8',
        '/windows nt 6.3/i'     =>  'Windows 8.1',
        '/windows nt 6.2/i'     =>  'Windows 8',
        '/windows nt 6.1/i'     =>  'Windows 7',
        '/windows nt 6.0/i'     =>  'Windows Vista',
        '/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
        '/windows nt 5.1/i'     =>  'Windows XP',
        '/windows xp/i'         =>  'Windows XP',
        '/windows nt 5.0/i'     =>  'Windows 2000',
        '/windows me/i'         =>  'Windows ME',
        '/win98/i'              =>  'Windows 98',
        '/win95/i'              =>  'Windows 95',
        '/win16/i'              =>  'Windows 3.11',
        '/macintosh|mac os x/i' =>  'Mac OS X',
        '/mac_powerpc/i'        =>  'Mac OS 9',
        '/iphone/i'             =>  'iOS',
        '/ipod/i'               =>  'iOS',
        '/ipad/i'               =>  'iOS',
        '/android/i'            =>  'Android',
        '/linux/i'              =>  'Linux',
        '/ubuntu/i'             =>  'Ubuntu',
        '/blackberry/i'         =>  'BlackBerry',
        '/webos/i'              =>  'Mobile'
    ];

    const BROWSER = [
        '/firefox/i'    =>  'Firefox',
        '/msie/i'       =>  'Internet Explorer',
        '/edge/i'       =>  'Edge',
        '/edg/i'        =>  'Edge',
        '/opera/i'      =>  'Opera',
        '/chrome/i'     =>  'Chrome',
        '/safari/i'     =>  'Safari',
        '/mobile/i'     =>  'Handheld Browser',
        '/netscape/i'   =>  'Netscape',
        '/maxthon/i'    =>  'Maxthon',
        '/konqueror/i'  =>  'Konqueror'
    ];
}

