import DropdownMenuStickyEffectPlugin from './dropdown-menu-sticky-effect-plugin/dropdown-menu-sticky-effect-plugin.plugin';
import DropdownMenuRightPlugin from './dropdown-menu-right-plugin/dropdown-menu-right-plugin.plugin';

const PluginManager = window.PluginManager;

PluginManager.register('TmmsDropdownMenuStickyEffectPlugin', DropdownMenuStickyEffectPlugin, DropdownMenuStickyEffectPlugin.options.elementSelector, {
    positionDropdownMenuStickyEffectIsActive: 120,
    notActiveViewportsDropdownMenuStickyEffectIsActiveString: "'XS', 'SM', 'MD'",
    dropdownMenuMultiLineOpenLastChildToLeft: false,
    dropdownMenuNumberMainNavigationMenuItemsOpenToLeft: 1,
    dropdownMenuMinimumNumberMainNavigationMenuItemsOpenToLeft: 3
});

PluginManager.register('TmmsDropdownMenuRightPlugin', DropdownMenuRightPlugin, DropdownMenuRightPlugin.options.elementSelector, {
    dropdownMenuMultiLineOpenLastChildToLeft: false,
    dropdownMenuNumberMainNavigationMenuItemsOpenToLeft: 1,
    dropdownMenuMinimumNumberMainNavigationMenuItemsOpenToLeft: 3
});
