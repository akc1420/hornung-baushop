# SwagPlatformSecurity

## Add a new Fix

* Create a new class which extends `Swag\Security\Components\AbstractSecurityFix`
* This class needs to be listed in the `\Swag\Security\Components\State::KNOWN_ISSUES`
* Adjust the snippets for the administration `src/Resources/app/administration/src/module/sw-settings-security/snippet/de-DE.json`
* Register the class in the DI with tag `kernel.event_subscriber` when you have overridden `getSubscribedEvents` and add tag `swag.security.fix` with argument `ticket` pointing to the created class
* Register js fixes in `/src/Resources/app/administration/src/main.js` `import './fixes/nextxxxx';`

### DI Tag `swag.security.fix`

When the given ticket to the tag is inactive, all services will be removed.

## How can I check is my fix active?

For PHP use `\Swag\Security\Components\State` and call `isActive` method with your Ticket number.

For Admin use `swagSecurityState` service like so

```javascript
let swagSecurityState = Shopware.Service('swagSecurityState');

swagSecurityState.isActive('NEXT-9241')
```
## How to publish a js fix

* Be sure you have registered your entrypoint in  `/src/Resources/app/administration/src/main.js` 
* Install the plugin on your platform dev
* run ``./psh.phar administration:build`
* The file `/Resources/public/administration/js/swag-platform-security.js` should have changed (search for your issue key)

### Commit without build files
Do not commit the file `/Resources/public/administration/js/swag-platform-security.js` since the pipelines builds the js files.

### Faking the Shopware version

To fake the Shopware version add e.g. `SHOPWARE_FAKE_VERSION=6.4.20.1` to the `.env` file of your project.

## Versioning & Compatibility

| Branch | Plugin version | Shopware versions |
|--|----------------|-------------------|
| trunk | 2.x            | 6.5 - 6.x         |
| 1.x | 1.x            | 6.1 - 6.4         |

for more infos see this [ADR](./adr/2023-03-28-new-adding-a-new-fix-strategy.md)

