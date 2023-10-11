<?php
declare(strict_types=1);

namespace Tmms\DropDownMenu\Subscriber;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Event\BeforeSendResponseEvent;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\SystemConfig\Event\SystemConfigChangedEvent;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Theme\ThemeCollection;
use Shopware\Storefront\Theme\ThemeService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SystemConfigSubscriber implements EventSubscriberInterface
{
    private bool $pluginConfigSaved = false;

    private SystemConfigService $systemConfig;

    private ThemeService $themeService;

    private EntityRepositoryInterface $salesChannelRepository;

    private string $pluginDomain;

    public function __construct(
        SystemConfigService $systemConfig,
        ThemeService $themeService,
        EntityRepositoryInterface $salesChannelRepository,
        string $pluginDomain
    ) {
        $this->systemConfig = $systemConfig;
        $this->pluginDomain = $pluginDomain;
        $this->themeService = $themeService;
        $this->salesChannelRepository = $salesChannelRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SystemConfigChangedEvent::class => 'onSaveTheme',
            BeforeSendResponseEvent::class => 'compileTheme',
        ];
    }

    public function onSaveTheme(SystemConfigChangedEvent $event): void
    {
        $isPluginConfigKey = mb_strpos($event->getKey(), $this->pluginDomain);

        if (!$this->pluginConfigSaved && $isPluginConfigKey === 0) {
            $this->pluginConfigSaved = true;
        }
    }

    public function compileTheme(BeforeSendResponseEvent $event): void
    {
        if (!$this->pluginConfigSaved) {
            return;
        }

        $context = Context::createDefaultContext();
        $salesChannels = $this->getSalesChannels($context);

        foreach ($salesChannels as $salesChannel) {
            $isThemeCompileActive = $this->systemConfig
                ->get($this->pluginDomain . 'dropdownMenuCompileThemeOnSave', $salesChannel->getId());

            if (!$isThemeCompileActive) {
                continue;
            }

            /** @var ThemeCollection|null $themes */
            $themes = $salesChannel->getExtensionOfType('themes', ThemeCollection::class);

            if (!$themes || !$theme = $themes->first()) {
                continue;
            }

            $this->themeService->compileTheme($salesChannel->getId(), $theme->getId(), $context, null, false);
        }
    }

    private function getSalesChannels(Context $context): SalesChannelCollection
    {
        $criteria = new Criteria();
        $criteria->addAssociation('themes');

        /** @var SalesChannelCollection $result */
        $result = $this->salesChannelRepository->search($criteria, $context)->getEntities();

        return $result;
    }
}
