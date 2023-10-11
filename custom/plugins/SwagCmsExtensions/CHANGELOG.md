# 2.6.0
- CMS-210 - Strictly typed properties & PHP 8 compatibility
- CMS-255 - Fixed submitting of custom forms when using a non-default languages
- CMS-391 - Fix submitting of custom form with CSRF mode set to ajax
- NEXT-24238 - Fix compatibility problems with Publisher plugin

# 2.5.0
- CMS-244 - Fix duplication of pages, sections or blocks with Custom Forms

# 2.4.0
- CMS-276 - Compatibility with Shopware 6.5.0
- CMS-342 - Header & Footer are now displayed again, when sending mails via Custom Forms
- CMS-350 - Added Missing arrows for slider elements

# 2.3.2
- CMS-333 - Quickview: Product is not displayed
- CMS-334 - Specified Composer plugin description

# 2.3.1
- CMS-276 - Bootstrap5: Replace jQuery
- NEXT-19245 - Add rule awareness and timeRange restriction for Dynamic Access
- NEXT-20495 - Add rule assignment ACLs

# 2.3.0
- CMS-250 - Compatibility with Symfony 5.3.0
- CMS-252 - Removes legacy duplicated code for section duplication
- CMS-253 - Fixed duplication of sections, when using scroll navigation
- CMS-261 - Fixes the variant switch in the Quickview
- NEXT-16846 - Changed rule assignment to new logic

# 2.2.1
- CMS-234 - Fixed submitting of custom forms, when using entity select on non-default languages

# 2.2.0
- CMS-58 - Implement Quickview feature for search listings
- CMS-180 - Fixed invalid display and saving in form builder, when content language is not default
- CMS-187 - Add Captcha support for custom forms
- CMS-194 - Custom Form field can now be saved correctly again on create
- CMS-194 - Storefront custom forms with selects using custom values can now be submitted again
- CMS-195 - Added additional support for moving block cross section in CMS's navigator
- CMS-195 - Removed the duplication button for custom forms in CMS's block navigator
- CMS-201 - Fixed CMS Extension entities, when used with versioning

# 2.1.0
- CMS-107 - Changed quickview for already existing cms templates on install per default to "disabled"
- CMS-108 - Scroll Navigation Point names do not break on invalid names anymore
- CMS-128 - Show scroll up button in storefront on all viewports when scroll navigation is active
- CMS-145 - Solved a problem when duplicating sections 
- CMS-146 - Added quickview option to cross selling slider
- CMS-155 - Plugin is valid for the `dal:validate` console command

# 2.0.1
- CMS-151 - Hide unsupported Custom Products configuration display in quickview

# 2.0.0
- CMS-52 - Removed unnecessary overhead from CMS data tables
- CMS-118 - Add compatibility with Shopware 6.4

# 1.8.3
- CMS-132 - Fix deactivation with custom forms

# 1.8.2
- CMS-130 - Only use feature flags to toggle admin features

# 1.8.1
- CMS-130 - Fix FormBuilder editor

# 1.8.0
- CMS-63 - Implemented form builder
- CMS-114 - Add compatibility with Shopware 6.3.5.1

# 1.7.2
- CMS-56 - Adds explaining tooltip to BlockRule rule selection 
- CMS-82 - Fixes "Add to Cart" button showing for Customized Products with required options in QuickView

# 1.7.1
- CMS-51 - Fixed error that rules could not be deleted

# 1.7.0
- CMS-8 - Implemented CMS Block rule functionality
- CMS-16 - Improved QuickView product loading

# 1.6.0
- CMS-27 - Added ACL privileges to the shopping world experiences module

# 1.5.3
- CMS-38 - Fixes wrong storefront filtering behaviour

# 1.5.2
- CMS-18 - Content behind a configured, collapsed Scroll Navigation sidebar are accessible again 
- CMS-20 - Multiple clicks do not open multiple QuickViews anymore

# 1.5.1
- CMS-5 - The navigation menu popup name is now displayed correctly in the Internet Explorer
- CMS-7 - Fixed scroll buttons when animated scrolling is disabled
- CMS-13 - Optimized scroll navigation for Shopware version 6.3.1.0 and improved behavior on initial set up without animated scrolling

# 1.5.0
- PT-11676 - Add error visualization for section settings
- PT-11919 - Shopware 6.3 compatibility
- PT-11935 - Removed SnippetFiles to use generic core logic
- PT-11952 - Fix animated scrolling activation via Administration

# 1.4.0
- PT-11317 - Fix switching variants in QuickView
- PT-11462 - Add automated e2e testing
- PT-11711 - Implements animated scrolling

# 1.3.1
- PT-11655 - Re-adjusted anchor position of a navigation point

# 1.3.0
- PT-11604 - Shopware 6.2 compatibility
- PT-11604 - Added Psalm integration

# 1.2.0
- PT-11314 - Hide options and "Add to Cart" button for Customize Products in QuickView
- PT-11432 - Implements scroll navigation
- PT-11447 - Quickview Images are not cut off anymore, if they are too big

# 1.1.2
- PT-11216 - Enable the QuickView configuration for cms blocks as well as product relevant slots
- PT-11314 - Hide options and "Add to Cart" button for Customize Products in QuickView
- PT-11502 - Fix displaying of product variant configurator in the QuickView

# 1.1.1
- PT-11442 - Fixed an error which occurred when loading the QuickView on shop pages without product listing

# 1.1.0
- PT-11143 - Modified the product box template, so that a QuickView is only loaded, when the corresponding product is active
- PT-11195 - Add variant switch to the QuickView

# 1.0.1
- PT-11135 - Add a detail-page button to the QuickView permanently

# 1.0.0
- Initial release of the CMS extensions plugin including the QuickView feature for Shopware 6
