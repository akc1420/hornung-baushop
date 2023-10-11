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

namespace Gbmed\Form\Storefront;

use Doctrine\DBAL\Connection;
use Gbmed\Form\Framework\Captcha\FormRoutes\Collection;
use Gbmed\Form\Framework\Struct\GbmedFormStruct;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\GenericPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Page implements EventSubscriberInterface
{
    private Collection $formRoutes;
    private EntityRepository $categoryRepository;
    private SalesChannelRepository $salutationRepository;
    private SystemConfigService $systemConfigService;
    private EntityRepository $cmsPageRepository;
    private Connection $connection;

    public function __construct(
        Collection $formRoutes,
        EntityRepository $categoryRepository,
        SalesChannelRepository $salutationRepository,
        EntityRepository $cmsPageRepository,
        SystemConfigService $systemConfigService,
        Connection $connection
    ) {
        $this->formRoutes = $formRoutes;
        $this->categoryRepository = $categoryRepository;
        $this->salutationRepository = $salutationRepository;
        $this->cmsPageRepository = $cmsPageRepository;
        $this->systemConfigService = $systemConfigService;
        $this->connection = $connection;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            GenericPageLoadedEvent::class => 'onGenericPageLoadedEvent'
        ];
    }

    public function onGenericPageLoadedEvent(GenericPageLoadedEvent $event): void
    {
        $event->getPage()->addExtension('gbmedForm', $this->getFormData($event->getSalesChannelContext()));
    }

    private function getFormData(SalesChannelContext $salesChannelContext): Struct
    {
        /** @var array $config */
        $config = $this->getConfig($salesChannelContext);
        $formRoutes = [];

        foreach ($config['forms'] as $form) {
            if($route = $this->formRoutes->findRouteByName($form)){
                $formRoutes[] = $route->getRoute();
            }
        }

        if($config['cmsExtensions'] && $route = $this->formRoutes->findRouteByName('cms-extensions')){
            $formRoutes[] = $route->getRoute();
        }

        return new GbmedFormStruct([
            'isRecaptcha' => (!empty($config['sitekey']) && !empty($config['secret'])),
            'config' => $config,
            'formRoutes' => $formRoutes
        ]);
    }

    private function getConfig(SalesChannelContext $salesChannelContext): array
    {
        /** @var array $config */
        $config = $this->systemConfigService->get(
            'GbmedForm.config',
            $salesChannelContext->getSalesChannel()->getId()
        );

        $queryBuilder = $this->connection->createQueryBuilder()
            ->select(['id'])
            ->from('plugin')
            ->where('name=:name')
            ->andWhere('active=:active')
            ->setParameters([
                'name' => 'SwagCmsExtensions',
                'active' => 1
            ]);

        if (!$queryBuilder->execute()->fetch()) {
            $config['cmsExtensions'] = false;
        }

        return $config;
    }
}
