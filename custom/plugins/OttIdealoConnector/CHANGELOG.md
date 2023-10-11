# v1.1.16
- fixed bug by missing product title in idealo transmission
- extended logging

# v1.1.15
- explicit loading of cart rules
- fixed bug with empty time modification

# v1.1.14
- restored compatibility to magnalister plugin 

# v1.1.13
- implement fallback if salutation is not provided anymore

# v1.1.12
- use SalesChannelContext in events
- save Paypal transaction id in swag custom field
- add plugin config to deactivate scheduled task
- add plugin config to manipulate imported ordertime

# v1.1.11
- optimize token handling for multi shop configuration
- add missing LineItem PriceDefinition added in 6.4.9.0

# v1.1.10
- trigger state change event

# v1.1.9
- fixed nulled string notice

# v1.1.8
- hotfix missing context

# v1.1.7
- add custom field for payment transactionId

# v1.1.6
- replace deprecated Versions class with new InstalledVersions

# v1.1.5
- updated legacy code to enable compatibility to SW6.4.7.0

# v1.1.4
- add DeepLinkCode to orders

# v1.1.3
- add productNumber to LineItem payload, cause this is mandatory since SW 6.4.5

# v1.1.2
- fixed bug in api test button, failing with valid authentication data

# v1.1.1
- send order state by salesChannel

# v1.1.0
- Shopware 6.4 compatibility

# v1.0.7
- fixed configuration inheritance problem
- fixed fetching parent product for inherited tax

# v1.0.6
- Extension of dispatch type mapping

# v1.0.5
- Extended parameters for order endpoints

# v1.0.4
- Removed IdealoOrderLineItemSavedEvent & IdealoOrderLineItemStockUpdatedEvent
  Use LineItemWrittenEvent instead to subscribe
- Added new config value fallbackLanguage for headless channels without language
- Fixed missing OrderDeliveryPositions

# v1.0.2
- Added missing variables for order confirmation mail
- Added missing associations for loading product tax rules

# v1.0.1
- Fixed missing name in OrderLineItem in order overview
- Fixed tax calculation for created order
