# 6.1.60
- this plugin now supports GA4 natively


    CAUTION: This is a major update, ensuring out-of-the-box compatibility to Google Analytics 4. Google announced that the old
    Universal Analytics (UA) will no more be available as per 07/01/2023. Updating to this version will deactivate the old Enhanced
    Ecommerce structure (we included an option to bring it back for a limited amount of time) and activate the new GA4-structure.
    Please check your GTM and GA4 setup immediately after updating to this plugin version. More information will soon be available
    at: https://www.codiverse.de/category/blog/

    The following events are supported by this plugin version:

    - view_item_list
    - view_item
    - view_cart
    - begin_checkout
    - select_item
    - add_to_cart
    - remove_from_cart
    - purchase
    - confirm_order (custom event for the checkout/confirm page)
    - add_payment_info

    The following events have been removed:

    - shopwareGTM.orderCompleted
    - gtmAddToCart
    - gtmRemoveFromCart

# 6.1.45
- Custom JS URLs may now also contain a filename
- Datalayer now contains the manufacturer number on detail pages

# 6.1.44
- Added new value for Enhanced Conversions: transactionCountryIso (ISO 3166-1 ALPHA-2)
- Added new value for Enhanced Conversions: transactionStateName (if availables)
- Changed: aw_feed_country (Adwords Tag) now contains 2-digit country code
- Changed: aw_feed_language (Adwords Tag) now contains 2-digit language code

# 6.1.43
- Price and Quantity in Addtocart Event should be numbers, not strings
- Addtocart and Removefromcart will now be fired regardless of SW Cookie Consent to increase third-party-compatibility. Please review your GTM settings
- Small bugfix for usercentrics code

# 6.1.42
- Optimizations for Add to cart on listing pages

# 6.1.41
- Improvement of backend description

# 6.1.40
- New: provide a custom URL for GTM.js
- new options for using Enhanced Conversion - please review plugin settings!

# 6.1.39
- Adjustments for Custom Products Plugin

# 6.1.38
- GTM base code now contains full HTTPS URL

# 6.1.37
- Fix for noscript Tag

# 6.1.36
- SW6.4.10.0 compatibility

# 6.1.35
- new plugin setting: limit amount of products in impressions-array

# 6.1.34
- New: Customer Email is now part of the finish-page (Key: transactionEmail)

# 6.1.33
- Fixed a bug that could cause a crash in fresh installations
- Fixes for noscript-template

# 6.1.32
- Bugfix for 404 pages
- Bugfix for custom products

#6.1.31
- fixed a bug leading to errors in account and checkout when remarketing was active

# 6.1.30
- ecomm_pagetype 'home' will now be used on frontpage
- Basic Tag Manager Code now included on non-standard shop pages

# 6.1.29
- Adjustments for Custom Products Plugin

# 6.1.28
- Corrected net prices in checkout

# 6.1.27
- added category info to datalayer on listing an detail pages

# 6.1.26
- new plugin option: enable UserCentrics compatibility
- GTM code will now also show up on landingpages

# 6.1.25
- Checkout exception handling

# 6.1.24
- Corrected tax calculation for EU countries in checkout

# 6.1.23
- Checkout exception handling

# 6.1.22
- A products SEO category will now be used as the official category for EE and in datalayer

# 6.1.21
- Remove From Cart Event is now also fired from offcanvas-cart

# 6.1.20
- Include Brand name in EE items
- Include coupon code on finish-page

# 6.1.19
- Bugfix for Sales Channels not using an ID

# 6.1.18
- Bugfix for PHP8 > 8.0.2

# 6.1.17
- SW6.4.0.0 compatibility

# 6.1.16
- Bugfixes in Datalayer Service

# 6.1.15
- Bugfix for Page Hiding

# 6.1.15
- Bugfix for adding promo codes

# 6.1.14
- Bugfixes

# 6.1.13
- Bugfixes for Remarketing

# 6.1.12
- AddtoCart and RemoveFromCart now use sku
- minor bugfixes

# 6.1.11
- Fix Product Number in EE Checkout
- Fix for Tax rate in Checkout
- Add Category Names to EE Checkout

# 6.1.10
- GTM Code on Newsletter Register-/Subscribe-Pages
- Bugfix for tax free countries when net price option is active

# 6.1.9
- Bugfix for carts including promo codes

# 6.1.8
- GTM code will now also appear on error pages

# 6.1.7
- remove GA cookies on preference change

# 6.1.6
- adjustments for the checkout/confirm view injection

# 6.1.5
- bugfix for dispatch methods
- bugfix for empty category trees

# 6.1.4
- bugfix for taxfree countries
- bugfix for listing pages without layout

# 6.1.3
- bugfix for shopping worlds

# 6.1.2
- listing performance update

# 6.1.1
- compatibility fix for third party plugins

# 6.1.0
- include Enhanced Ecommerce Tracking JS Events
- include Adwords Tags

# 6.0.5
- Bugfix for detailpages
- Bugfix for EE

# 6.0.4
- Bugfix for multishops

# 6.0.3
- SW6.2.x compatibility

# 6.0.2
- Enhanced Ecommerce is now available

# 6.0.1
- Remarketing is now available

# 6.0.0
- initial release
