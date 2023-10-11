# 3.1.3
- SOC-200 - Deprecations wurden für v3.2.0 hinzugefügt:
    - `SwagSocialShopping\Component\MessageQueue\SocialShoppingValidationHandler` - Klasse wird nicht mehr den `Shopware\Core\Framework\MessageQueue\Handler\AbstractMessageHandler` erweitern
    - `SwagSocialShopping\Component\MessageQueue\SocialShoppingValidationHandler::handle` - Methodenname wird in `__invoke` umbenannt
    - `SwagSocialShopping\DataAbstractionLayer\SalesChannelTypeRepositoryDecorator` - Klasse wird gelöscht
    - `SwagSocialShopping\Component\Network\NetworkRegistry::getNetworks` - Anmerkung des Rückgabewertes wird zu `iterable&NetworkInterface[]` geändert
    - `src/Resources/app/administration/src/extension/sw-sales-channel-list/index::getNetworkIconMapping` - Methode wird entfernt
    - `src/Resources/app/administration/src/extension/sw-sales-channel-menu/index::getNetworkIconMapping` - Methode wird entfernt

# 3.1.2
- SOC-174 - Behebt ein Problem durch welches beim Updaten von Bestellungen mit Einstiegs-Codes ein Fehler geworfen wurde

# 3.1.1
- SOC-173 - Fehlende ACL-Konfiguration hinzugefügt

# 3.1.0
- SOC-145 - Möglichkeit hinzugefügt, um zu erkennen welche Bestellungen oder Kunden aus Social Shopping Verkaufskanälen stammen

# 3.0.0
- SOC-52 - Alle Klassenattribute wurden typisiert
- SOC-71 - Social Shopping Templates können nun bearbeitet werden
- SOC-24 - Die verfügbare Assoziation `product.options.group` wurde den Variablen in der Template Sidebar hinzugefügt

# 2.4.0
- SOC-110 - Kompatibilität für das neue Core Feature für das Markieren eines Sales Channels als Favorit hinzugefügt
- SOC-86 - Das Icon von Facebook Verkaufskanälen wird nun korrekt angezeigt
- SOC-121 - Social Shopping Icons werden in der Liste der Verkaufskanäle angezeigt

# 2.3.1
- SOC-70 - Behebt ein potentielles Problem auf Entwickler-Systemen
- SOC-77 - Zusätzliche Bilder zu Export-Templates hinzugefügt

# 2.3.0
- SOC-51 - Nicht stark typisierte geschützte Variablen wurden deprecated. Diese werden mit v3.0.0 stark typisiert
- SOC-54 - Behebt ein Problem mit dem Export, wenn ein Produkt kein Bild zugewiesen hat

# 2.2.0
- SOC-48 - Produktexporte von Google Shopping enthalten nun den Streichpreis

# 2.1.0
- SOC-41 - Facebook Produktexporte enthalten nun den Streichpreis
- SOC-45 - Das Plugin ist jetzt valide für den Konsolenbefehl `dal:validate`
- SOC-46 - Die Google-Produktkategorie ist nicht mehr als erforderlich markiert

# 2.0.0
- SOC-39 - Shopware 6.4 Kompatibilität

# 1.3.5
- SOC-34 - Hilfetext für Export-Intervall hinzugefügt

# 1.3.4
- NEXT-12156 - Laden von Produkten in Pinterest-Einstellungen verbessert
- SOC-36 - Entity-Definition verbessert

# 1.3.3
- SOC-8 - Korrigiert Darstellung falscher Tabs auf der Verkaufskanal-Detailseite, wenn ein neuer Verkaufskanal hinzugefügt wird
- SOC-17 - Behebt ein Problem, bei dem Preise von Varianten und Produkten mit erweiterten Preisen inkorrekt sind

# 1.3.2
- SOC-7 - Feeds werden nun nur generiert, wenn der Verkaufskanal aktiv ist.
- SOC-8 - Korrigiert Darstellung falscher Tabs in der Verkaufskanal-Administration
- SOC-11 - Behebt ein Problem mit Google Shopping, wenn ein Produkt keine Kategorie zugewiesen hat
- SOC-27 - Behebt ein Problem, bei dem das Plug-in nicht bei Systemsprachen außer Deutsch und Englisch installiert werden kann
- SOC-29 - Behebt die Löschung von Produkten mit Exportfehlern

# 1.3.1
- SOC-21 - ACL-Privilegien bei der Plugin-Installation hinzugefügt

# 1.3.0
- SOC-21 - ACL-Privilegien hinzugefügt

# 1.2.0
- PT-11921 - Kompatibilität für Shopware 6.3 hinzugefügt

# 1.1.1
- PT-11683 - Exportieren von Produkten ohne Hersteller korrigiert

# 1.1.0
- PT-11188 - Implementierung eines Tabs für die Integration der Verkaufskanäle
- PT-11603 - Kompatibilität für Shopware 6.2 hinzugefügt

# 1.0.3
- PT-11261 - Der Social Shopping Verkaufskanal ist nun nicht mehr sichtbar, wenn das Plugin deinstalliert oder deaktiviert ist

# 1.0.2
- PT-11189 - Doppelter Produkt-Validierungs-Button entfernt

# 1.0.1
- PT-11122 - Plugin Icon ausgetauscht
- PT-11187 - Darstellung der Textbausteine korrigiert
- PT-11199 - Installationsprozess des Plugins verbessert

# 1.0.0
- Erste Veröffentlichung des Social-Shopping Plugins für Shopware 6
