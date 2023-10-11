# 2.6.0
- CMS-210 - Strikt typisierte Klassenvariablen & PHP 8 Kompatibilität
- CMS-255 - Behebt benutzerdefinierte Formularübermittlung bei Verwendung von nicht standardmäßigen Sprachen
- CMS-391 - Senden eines benutzerdefinierten Formulars verwendet nun den Ajax-CSRF-Modus
- NEXT-24238 - Behebt Kompatibilitätsprobleme mit dem Publisher-Plugin

# 2.5.0
- CMS-244 - Behebt einen Fehler bei der Duplizierung von Seiten, Sektionen oder Blöcken mit eigenen Formularen

# 2.4.0
- CMS-276 - Kompatibilität mit Shopware 6.5.0
- CMS-342 - Header & Footer werden wieder angezeigt, wenn eine Mail via Custom Forms gesendet wird
- CMS-350 - Fehlende Pfeile für Slider-Elemente hinzugefügt

# 2.3.2
- CMS-333 - Quickview für Produkte werden nicht angezeigt 
- CMS-334 - Composer-Plugin-Beschreibung spezifiziert

# 2.3.1
- CMS-276 - Bootstrap5: jQuery ersetzen
- NEXT-19245 - Regelbeachtung und Zeitspannen-Restriktionen für Dynamic Access hinzugefügt
- NEXT-20495 - ACL-Rechte für Regelzuweisungen hinzugefügt

# 2.3.0
- CMS-250 - Kompatibilität mit Symfony 5.3.0
- CMS-252 - Entfernt veraltete Code-Duplikate für die Duplizierung von Sektionen 
- CMS-253 - Duplizieren von Sektionen mit ScrollNavigation korrigiert
- CMS-261 - Behebt ein Problem beim Wechsel der Varianten in der Quickview
- NEXT-16846 - Anpassung der Regel-Zuordnung an die neue Logik

# 2.2.1
- CMS-234 - Behebt ein Problem beim Abschicken von benutzerdefinierten Formularen, wenn Entity-Auswahlfelder in Nicht-Standardsprachen benutzt wurden

# 2.2.0
- CMS-58 - Quickview-Feature für Suchergebnisse implementiert
- CMS-180 - Behebt ein Problem, bei dem Formbuilder-Eigenschaften nicht korrekt dargestellt oder gespeichert werden konnten, wenn die Content-Sprache nicht standard ist
- CMS-187 - Captcha-Unterstützung für benutzerdefinierte Formulare implementiert
- CMS-194 - Formbuilder-Felder können wieder beim initial Anlegen korrekt gespeichert werden
- CMS-194 - Benutzerdefinierte Formulare, die Auswahlfelder mit neu-definierten Inhalten benutzen, können wieder abgeschickt werden
- CMS-195 - Zusätzliche Unterstützung für das Verschieben von CMS-Blöcken im Navigator implementiert
- CMS-195 - Duplizieren-Schaltfläche für benutzerdefinierte Formulare im Block-Navigator entfernt
- CMS-201 - CMS-Extension-Entitäten bei Verwendung mit Versionierung gefixt

# 2.1.0
- CMS-107 - Die Quickview ist nun für bereits vorhandene CMS-Layouts bei Installation standardmäßig ausgeschaltet
- CMS-108 - Scroll-Navigation-Punktnamen funktionieren jetzt mit beliebigen Namen
- CMS-128 - Der Hochscroll-Button wird in der Storefront nun auf allen Viewport angezeigt, wenn die ScrollNavigation aktiv ist
- CMS-145 - Behebt ein Problem beim Duplizieren von Sektionen
- CMS-146 - Quickview-Einstellungen für den Cross-Selling-Slider implementiert
- CMS-155 - Das Plugin ist jetzt valide für den Konsolenbefehl `dal:validate`

# 2.0.1
- CMS-151 - Nicht-unterstützte Konfigurationsdarstellung von Custom Products in der Quickview ausgeblendet

# 2.0.0
- CMS-52 - Nicht benötigter Overhead von CMS-Datentabellen entfernt
- CMS-118 - Kompatibilität mit Shopware 6.4 hinzugefügt

# 1.8.3
- CMS-132 - Behebt ein Problem mit dem deaktivieren, wenn custom forms aktiv sind

# 1.8.2
- CMS-130 - Features flags werden nur noch verwendet um Administrations-Eigenschaften zu steuern

# 1.8.1
- CMS-130 - Behebt ein Problem mit dem Formbuilder-Editor

# 1.8.0
- CMS-63 - Formularbaukasten implementiert
- CMS-114 - Kompatibilität mit Shopware 6.3.5.1 hinzugefügt

# 1.7.2
- CMS-56 - Erläuterndes Tooltip zur BlockRule-Regelauswahl hinzugefügt
- CMS-82 - Korrigiert Anzeige des Warenkorb-Button bei Custom Products mit Pflichtoptionen in der QuickView

# 1.7.1
- CMS-51 - Fehler behoben der das Löschen von Regeln verhindert hat

# 1.7.0
- CMS-8 - Regeln für CMS-Blocks wurden implementiert
- CMS-16 - Laden der Produkte in der QuickView verbessert

# 1.6.0
- CMS-27 - ACL-Privilegien zum Erlebniswelten-Modul hinzugefügt

# 1.5.3
- CMS-38 - Fehlerhafte Storefront-Filterung behoben

# 1.5.2
- CMS-18 - Inhalte hinter einer konfigurierten, eingeklappten Scroll Navigation sind wieder zugänglich
- CMS-20 - Die QuickView öffnet sich nicht mehr mehrfach bei mehrmaligen Klicken

# 1.5.1
- CMS-5 - Der Name eines Navigationselementes wird nun auch im Internet Explorer korrekt angezeigt
- CMS-7 - Es wurde ein Problem behoben, bei dem die Scrollbuttons nicht ohne aktiviertes animiertes Scrolling funktionierten
- CMS-13 - Scroll Navigation für Version Shopware 6.3.1.0 optimiert und Verbesserung der initialen Einstellungen ohne animiertes Scrollen

# 1.5.0
- PT-11676 - Fehler-Visualisierung für Sektionseinstellungen implementiert
- PT-11919 - Kompatibilität für Shopware 6.3
- PT-11935 - SnippetFiles entfernt um generische Core-Logik zu verwenden
- PT-11952 - Aktivierung des animierten Scrollens via Administration korrigiert

# 1.4.0
- PT-11317 - Variantenwechsel in der QuickView korrigiert
- PT-11462 - Automatisierte e2e-Tests hinzugefügt
- PT-11711 - Animiertes Scrollen implementiert

# 1.3.1
- PT-11655 - Anker-Position eines Navigationspunktes angepasst

# 1.3.0
- PT-11604 - Kompatibilität für Shopware 6.2
- PT-11604 - Psalm-Integration hinzugefügt

# 1.2.0
- PT-11314 - Optionen und Warenkorb-Button für Customized Products in QuickView entfernt
- PT-11432 - Scroll Navigation implementiert
- PT-11447 - Bilder in der Quickview werden nun nicht mehr abgeschnitten, wenn sie zu groß sind

# 1.1.2
- PT-11216 - QuickView-Konfiguration für CMS-Blöcke und produkt-relevante Elemente zur Verfügung gestellt
- PT-11314 - Optionen und Warenkorb-Button für Customized Products in QuickView entfernt
- PT-11502 - Anzeige der Variantenauswahl in der QuickView korrigiert

# 1.1.1
- PT-11442 - Fehler beim Laden der QuickView auf Shopseiten ohne Produkt-Listing behoben

# 1.1.0
- PT-11143 - Produktbox-Template angepasst, sodass die Box nur noch von der QuickView berücksichtigt wird, wenn das zugehörige Produkt auch aktiv ist
- PT-11195 - Variantenauswahl zur QuickView hinzugefügt

# 1.0.1
- PT-11135 - Detailseiten-Button dauerhaft zur QuickView hinzugefügt

# 1.0.0
- Erste Veröffentlichung des CMS-Extensions Plugins inklusive der QuickView für Shopware 6
