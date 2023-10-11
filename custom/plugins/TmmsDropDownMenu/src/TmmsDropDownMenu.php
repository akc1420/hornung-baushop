<?php
declare(strict_types=1);

namespace Tmms\DropDownMenu;

use Shopware\Core\Framework\Plugin;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TmmsDropDownMenu extends Plugin
{
    const PLUGIN_CONFIG_VARS = [
        'dropdownMenuBoxShadow',
        'dropdownMenuPositionLeft',
        'dropdownMenuMinWidth',
        'dropdownMenuFontSize',
        'dropdownMenuPaddingTop',
        'dropdownMenuPaddingRight',
        'dropdownMenuPaddingBottom',
        'dropdownMenuPaddingLeft',
        'dropdownMenuDistanceTextToLeft',
        'dropdownMenuStickyEffectTransitionDuration',
        'dropdownMenuStickyEffectZIndex',
        'dropdownMenuStickyEffectBorderBottomWidth',
    ];

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->setParameter(
            'tmms_dropdown_domain',
            $this->getName() . '.config.'
        );
    }
}
