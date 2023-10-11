## 1.13.0

* Fix definition class for `ShippingMethodExtension`.
* Exclude digital products from parcel hydration.
* Add interface `Pickware\ShippingBundle\Carrier\CarrierAdapterRegistryInterface` for `CarrierAdapterRegistry`.
* Fix saving new `ShippingMethods` with direct selection of `ShippingMethodConfig`.


## 1.12.0

* Add the option to configure the dimensions of a parcel.
* Remove disabled config values from shipmentConfigs for batch label creation.


## 1.11.0

* Replaces country text fields in the config and the customs information by dropdown selects


## 1.10.2

* Fixes `HttpLogger` and the `HttpSanitizing` classes to be backwards compatible.

## 1.10.1

* Corrects json schemas to only accept valid uuidv4.

## 1.10.0

* Discounts and promotions will get proportionally distributed on the prices of line items by their weighted share.
* Deprecates the `HttpSanitizing` classes in favor of the moved classes in the Http Package.

## 1.9.0

* Adds ShipmentController endpoints `createReturnShipmentBlueprintForOrder` and `createReturnShipmentForOrder`.
* Adds `ReturnShipmentsRegistrationCapability` to allow carriers to create return labels.
* Adds button to create a return label to the order page in the administration.
* Adds `ReturnShipmentCancellationCapability` to allow carriers to cancel return labels.


## 1.8.3

* Sorts the shipment documents by order number and document type in the batch creation of shipping labels (order list view).


## 1.8.2

* Fix shipment blueprint creation with orders that have no order delivery.


## 1.8.1

* Sorts the shipment documents by order number in the batch creation of shipping labels (order list view).


## 1.8.0

* Adds ShipmentController endpoints `createShipmentBlueprintsForOrders` and `createShipmentsForOrders`.
* Adds button to create multiple shipping labels to `sw-bulk-edit-order` Administration component.


## 1.7.9

* Fix `CarrierAdapterException` to being backwards compatible in the constructor arguments.
* Fix fallback weight not set when parcel packing was skipped in `ShipmentService`.


## 1.7.8

* Update dependencies.


## 1.7.7

* Update dependencies.


## 1.7.6

* Update dependencies.


## 1.7.5

* Fix missing address addition for split addresses.
* Fixes bundle registration in Shopware version 6.4.15.0 and above.


## 1.7.4

* Update dependencies.


## 1.7.3

* Update dependencies.


## 1.7.2

* Fix float comparison.


## 1.7.1

* Update dependencies.


## 1.7.0

* CarrierAdapterExceptions are now implementing and using the JsonApiErrorSerializable interface.


## 1.6.6

* Revert usage of OrderConfiguration and use OrderDeliveryCollection and OrderTransactionCollection.


## 1.6.5

* Update dependencies.


## 1.6.4

* Update dependencies.


## 1.6.3

* Update dependencies.


## 1.6.2

* Update dependencies.


## 1.6.1

**Requirements:**

* The bundle now requires at least Shopware version 6.4.5.0.


## 1.6.0

* Add command `pickware-shipping:shipping-method-config:import`.


## 1.5.0

* Catches exceptions and returns an "HTTP: Bad Request" error response in the ShipmentController when the ShipmentService throws a `Pickware\ShippingBundle\Config\ConfigException`.
* Adds optional parameter `array $shipmentPayload` to `src/Shipment/ShipmentService::ShipmentsOperationResultSet()`.
* Add optional parameter `configuration` (class `src/Shipment/ShipmentBlueprintCreationConfiguration`) when creating shipment blueprints.

**Requirements:**

* The bundle now requires at least Shopware version 6.4.4.0.


## 1.4.6

* Update dependencies.


## 1.4.5

* Update composer configuration


## 1.4.4

* Update dependencies.


## 1.4.3

* Internal refactoring.
* Update dependencies.


## 1.4.2

* Shipping settings regarding shipping carriers can be saved again in SW 6.4.9.0.
* Update dependencies.


## 1.4.1

* Update code style.
* Fixes the error message in the Administration when the creation of a shipment blueprint failed.


## 1.4.0

* Add support for Composer 2.2.
* Carrier icons will now be displayed wherever carrier selection is possible.
* The property `customerReference` was added to the shipment blueprint.
* If more than one parcel is processed for a shipment order each existing customer reference of a parcel will have the parcel number appended.


## 1.3.2

* Update dependencies.


## 1.3.1

* Run acceptance tests in dedicated database.


## 1.3.0

* Add a client for REST API requests from the shipping adapters.
* The `ShipmentService` now dispatches a corresponding event when creating a shipment blueprint for an order.


## 1.2.2

* Update dependencies.


## 1.2.1

* Fix label creation for active carrier options that are not displayed.


## 1.2.0

* Introduce new interfaces:
  * `Pickware\ShippingBundle\ParcelPacking\BinPacking\WeightBasedBinPacker`
  * `Pickware\ShippingBundle\ParcelPacking\ParcelPacker`
* Add new services that can modified to alter the parcel packing:
  * `pickware_shipping.parcel_packer`
  * `pickware_shipping.weight_based_bin_packer`


## 1.1.2

* Fix migration to ensure proper carrier config change.


## 1.1.1

* Fix general label creation issues.


## 1.1.0

* Display carrier icon next to the carrier name in the list of generated labels (order detail view).
* After cancelling a shipping label the tracking codes stored in the related order deliveries will be updated.


## 1.0.9

* Update `pickware/document-bundle` to 2.0.8.
* Update `pickware/money-bundle` to 2.0.6.


## 1.0.8

* Fix config renderer field translations to use a proper fallback locale in the shipment modal.


## 1.0.7

* Allow Monolog 2 as dependency.


## 1.0.6

* Update `pickware/dal-bundle` to 3.4.0.
* Update `pickware/document-bundle` to 2.0.5.
* Update `pickware/http-utils` to 2.1.0.


## 1.0.5

* Adjust composer version constraints to improve compatibility with Shopware 6.4.0.0 and above.


## 1.0.4

* Update dependencies.


## 1.0.3

* Fix a migration that failed because of a foreign key error.


## 1.0.2

* Remove nullability of `pickware_shipping_shipment.carrier_technical_name`.
* Improve snippets and hints.


## 1.0.1

* Fix association prefix of document extensions.
* Add missing dependencies.


## 1.0.0

**Initial release**
