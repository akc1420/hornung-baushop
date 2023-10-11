# v1.1.16
- Fehler behoben wenn Produkttitel in Idealoübertragung fehlt
- Logging erweitert

# v1.1.15
- explizites Laden der Warenkorb und Checkout Regeln
- Bugfix mit leerer Zeitänderung

# v1.1.14
- Kompatibilität zu Magnalister Plugin wiederhergestellt

# v1.1.13
- Ausweichlösung implementiert falls Anrede nicht mehr vorhanden

# v1.1.12
- verwenden von SalesChannelContext in events
- Paypal transaction id in swag custom field speichern
- Pluginkonfig zum Deaktivieren der Scheduled Task hinzugefügt
- Pluginkonfig zum Manipulieren des importierten Bestellzeit hinzugefügt

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
- Custom field hinzugefügt für payment transactionId

# v1.1.6
- Veraltete Versions Klasse mit neuer InstalledVersions getauscht

# v1.1.5
- Legacy Code aktualisiert um Kompatibilität zu SW6.4.7.0 zu erreichen

# v1.1.4
- DeepLinkCode in Bestellungen hinzugefügt

# v1.1.3
- ProductNummer in LineItem Payload hinzugefügt, da es seit SW 6.4.5 ein Pflichtfeld ist

# v1.1.2
- Fehlerbehebung in API Test Button, fehlerhafte Authentifizierung mit korrekten Daten

# v1.1.1
- Bestellstatus pro SalesChannel übermitteln

# v1.1.0
- Shopware 6.4 Kompatibilität

# v1.0.7
- Fehlerbehebung Konfigurationsvererbung
- Fehlendes Parent Produkt für Steuerregeln hinzugefügt

# v1.0.6
- Erweiterung des Versandarten Mappings

# v1.0.5
- Parameter für Order Endpoints erweitert

# v1.0.4
- IdealoOrderLineItemSavedEvent & IdealoOrderLineItemStockUpdatedEvent entfernt
  Für Eventsubscription LineItemWrittenEvent nutzen
- Neue Konfiguration Fallback Sprache für headless Channels ohne hinterlegte Sprache
- Fehlende OrderDeliveryPositions hinzugefügt

# v1.0.2
- Fehlende Variablen Bestellbestätigungsmail hinzugefügt
- Fehlende Verbindung beim Laden von Produkt Steuerregeln hinzugefügt

# v1.0.1
- Fehlender Name in OrderLineItem in Bestellübersicht behoben
- Fehlerhafte Steuerberechnung korrigiert
