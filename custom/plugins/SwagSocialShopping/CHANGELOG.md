# 3.1.3
- SOC-200 - Added deprecations for v3.2.0:
  - `SwagSocialShopping\Component\MessageQueue\SocialShoppingValidationHandler` - Class will no longer extend the `Shopware\Core\Framework\MessageQueue\Handler\AbstractMessageHandler`
  - `SwagSocialShopping\Component\MessageQueue\SocialShoppingValidationHandler::handle` - Method name will be changed to `__invoke`
  - `SwagSocialShopping\DataAbstractionLayer\SalesChannelTypeRepositoryDecorator` - Class will be removed
  - `SwagSocialShopping\Component\Network\NetworkRegistry::getNetworks` - Return type annotation will be changed to iterable&NetworkInterface[]
  - `src/Resources/app/administration/src/extension/sw-sales-channel-list/index::getNetworkIconMapping` - Method will be removed
  - `src/Resources/app/administration/src/extension/sw-sales-channel-menu/index::getNetworkIconMapping` - Method will be removed

# 3.1.2
- SOC-174 - Fixed an issue where updates of orders with referral codes would show an error

# 3.1.1
- SOC-173 - Added missing ACL configuration

# 3.1.0
- SOC-145 - Added possibility to identify which orders or customers came from social shopping sales channels

# 3.0.0
- SOC-52 - Typed all properties
- SOC-71 - Social Shopping Templates are now editable
- SOC-24 - Added the available association `product.options.group` to the template variable sidebar

# 2.4.0
- SOC-110 - Added support for the new core feature of marking a sales channel as favorite
- SOC-86 - The icon of facebook sales channels is now properly displayed
- SOC-121 - Display Social Shopping Icons in sales channel list

# 2.3.1
- SOC-70 - Fixed a potential issue on Dev-Systems
- SOC-77 - Added additional images to export templates

# 2.3.0
- SOC-51 - Deprecated not strictly typed protected properties. These will be strictly typed in v3.0.0
- SOC-54 - Fixes an issue with the export failing if a product has no image assigned

# 2.2.0
- SOC-48 - Google Shopping product exports now include listing prices

# 2.1.0
- SOC-41 - Facebook Productexports now include listing prices
- SOC-45 - Plugin is valid for the `dal:validate` console command
- SOC-46 - The google product category is not marked as required anymore

# 2.0.0
- SOC-39 - Shopware 6.4 compatibility

# 1.3.5
- SOC-34 - Add help text for export generation interval

# 1.3.4
- NEXT-12156 - Improves loading of products in Pinterest settings
- SOC-36 - Improve entity definition

# 1.3.3
- SOC-8 - Fixes wrong tabs showing in Sales Channel detail page when creating a Sales Channel
- SOC-17 - Fixes an issue where the prices of variants and products with advanced prices might be incorrect

# 1.3.2
- SOC-7 - Feeds are now only generated when Sales Channel is active
- SOC-8 - Fixes wrong tabs showing in Sales Channel administration
- SOC-11 - Fixes an issue with Google Shopping if a product has no category assigned
- SOC-27 - Fixes an issue where the Plugin could not be installed on system languages other than English and German
- SOC-29 - Fixes deletion of products with export errors

# 1.3.1
- SOC-21 - Added ACL privileges on plugin installation

# 1.3.0
- SOC-21 - Added ACL privileges

# 1.2.0
- PT-11921 - Added compatibility for Shopware 6.3

# 1.1.1
- PT-11683 - Fix export of products without manufacturer

# 1.1.0
- PT-11188 - Implemented integration tab into Sales Channel configuration
- PT-11603 - Added compatibility for Shopware 6.2

# 1.0.3
- PT-11261 - The Social Shopping sales channel type is no longer visible if the plugin is uninstalled or deactivated

# 1.0.2
- PT-11189 - Removed duplicated product validation button

# 1.0.1
- PT-11122 - Replace plugin icon
- PT-11187 - Fix displaying of snippets
- PT-11199 - Improve install process of the plugin

# 1.0.0
- Initial release of the social shopping plugin for Shopware 6
