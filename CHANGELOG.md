# Changelog

### 1.5.1 ###
* Fix issue with "dismiss" button if multiple warnings are found for one theme file (#135) (#136)

### 1.5.0 ###
* Replace `FILTER_SANIITZE_STRING` deprecated since PHP 8.1+ (#126) (#127)
* Enforce use of custom Safe Browsing API key (#104) (#108)
* Separate settings page from manual scanning with overhauled UI (#107)
* Update JS to ES2015 (IE11 no longer supported) (#32)

### 1.4.4 ###
* Fix warning on SafeBrowsing API errors with PHP 8.1+ (#123)
* Tested up to WordPress 6.2

### 1.4.3 ###
* Point Safe Browsing link on settings page to site-specific URL (#106)
* Increase the size of the Safe Browsing API input to show the entire key (#109)
* Show warning if Safe Browsing check is enabled without custom API key (#105)

### 1.4.2 ###
* Drop recursive check on option that failed in several scenarios (#96) (#97)
* Drop check for base64 encoded strings which did not work properly in al cases (#100)
* Use WP 5.7 color palette for the UI (#99)

### 1.4.1 ###
* Fix some spelling mistakes and correct translations (#85)
* Fix file name sanitization in manual theme scan causing errors to be not shown in the admin area (#88, #89)
* Fix theme file collection for child themes with duplicate names (#86)
* Consider all levels in theme file check instead of one only (#87, #90)
* Support translations in old WordPress versions (#91)

### 1.4.0 ###
* Option to provide a custom key for the Google Safe Browsing API (#69)
* Scan files of parent theme if a child theme is active (#1, #62)
* Verify checksums of WP core files (integrated functionality from _Checksum Verifier_ plugin (#5, #56)
* Allow to enable _Safe Browsing_ and _Checksum Verifier_ as cronjob without theme scan (#66)
* Update code style check and add build script (#68)

### 1.3.10 ###
* **English**
	 * Updated PayPal link for donations
	 * Improve coding standards
	 * Translation fixes, improvements and cleanups
	 * Better documentation
	 * Some minor markup, styling, accessibility and security improvements
	 * Update to Safe Browsing API v4 (fixing false positive email notifications)
	 * PHP 7.x compatibility fixes
	 * Better regex to prevent false positives in file scans
* **Deutsch**
	 * Aktualisierung des PayPal Senden-Buttons
	 * Verbesserung der Coding-Standards
	 * Kleinere Korrektur und Verbesserungen an den Übersetzungen
	 * Bessere Dokumentation
	 * Einige kleine Verbesserungen an Markup, Styles, Barrierefreiheit und Sicherheit
	 * Aktualisierung der Safe-Browsing-API auf Version 4 (zur Verhinderung von False-Positive E-Mail-Benachrichtigungen)
	 * PHP 7.x Kompatibilitätsanpassungen
	 * Ein besserer regulärer Ausdruck zur Vermeidung von False-Positives beim Datei-Scan

### 1.3.9 ###
* **English**
	 * generated a POT file
	 * added German formal translation
	 * updated, translated + formatted README.md
	 * updated expired link URLs in plugin and languages files
	 * updated [plugin authors](https://pluginkollektiv.org/hello-world/)
* **Deutsch**
	 * Eine POT-Datei erstellt
	 * formale deutsche Übersetzung (Sie) hinzugefügt
	 * README.md aktualisiert, übersetzt und formatiert
	 * Abgelaufene Link-Adressen in dem Plug-in und in den Sprachdateien aktualisiert
	 * [Plugin Author](https://pluginkollektiv.org/de/hallo-welt/) aktualisiert

### 1.3.8 ###
* **English**
	 * Detection and warning for the [MailPoet Vulnerability](http://blog.sucuri.net/2014/07/mailpoet-vulnerability-exploited-in-the-wild-breaking-thousands-of-wordpress-sites.html)
* **Deutsch**
	 * Erkennung der [MailPoet-Sicherheitslücke](http://blog.sucuri.net/2014/07/mailpoet-vulnerability-exploited-in-the-wild-breaking-thousands-of-wordpress-sites.html)

### 1.3.7 ###
* **English**
	 * Update the Google Safe Browsing Lookup API to v3.1
* **Deutsch**
	 * Aktualisierung auf Safe Browsing Lookup API 3.1

### 1.3.6 ###
* **English**
	 * Code revision and data validation
* **Deutsch**
	 * Code-Revision und Datenvalidierung

### 1.3.5 ###
* **English**
	 * Optimizations for WordPress 3.8
* **Deutsch**
	 * Optimierungen für WordPress 3.8

### 1.3.4 ###
* **English**
	 * [Google Safe Browsing](http://en.wikipedia.org/wiki/Google_Safe_Browsing) for malware and phishing monitoring with e-mail notification.
	   [Additional information](https://antivirus.pluginkollektiv.org/2013/05/08/new-features-in-antivirus-for-wordpress/)
* **Deutsch**
	 * Benachrichtigung per E-Mail, sobald [Google Safe Browsing](http://en.wikipedia.org/wiki/Google_Safe_Browsing) Malware im Blog erkennt.
	   [Mehr](https://antivirus.pluginkollektiv.org/de/2013/05/08/neue-funktion-im-antivirus-fuer-wordpress/)

### 1.3.3 ###
* **English**
	 * Add inspection for iFrames
	 * Retina support for teaser and screenshot
* **Deutsch**
	 * Inspektion für iFrames hinzugefügt
	 * Retina-Unterstützung für Teaser und Screenshot

### 1.3.2 ###
* **English**
	 * Remove the check for include and require commands (#wpforce)
* **Deutsch**
	 * Überprüfung für include und require Befehle entfernt (#wpforce)

### 1.3.1 ###
* **English**
	 * Compatibility with WordPress 3.4
	 * High-resolution plugin icon for retina displays
	 * Remove icon from the admin sidebar
	 * System requirements: From PHP 5.0 to PHP 5.1
* **Deutsch**
	 * Kompatibilität mit Wordpress 3.4
	 * Hochauflösendes Plugin Symbol für Retina-Bildschirme
	 * Symbol aus der Admin-Sidebar entfernbar
	 * Systemvorraussetzungen: von PHP 5.0 auf PHP 5.1 angehoben

### 1.3 ###
* **English**
	 * Xmas Edition
* **Deutsch**
	 * Weihnachtsausgabe

### 1.2 ###
* **English**
   * "Virus suspected" alert in the admin bar
   * Fix for the manual scan link on dashboard
   * More detailed checks for existing malware
   * Code adjustments for WordPress 3.3
* **Deutsch**
   * "Virusverdacht" Alarm in der Admin-Bar
   * Den manuellen Prüfen-Link auf dem Dashboard behoben
   * Genauere Prüfung für bekannte Malware
   * Quelltextanpassungen für Wordpress 3.3

### 1.1 ###
* **English**
   * Testing for templates with empty content
   * Minimum requirement upgraded to 2.8 and PHP5
   * Code improvements for more speed
   * GUI changes
* **Deutsch**
   * Tests für Vorlagen mit leerem Inhalt
   * Mindestvorrausetzungen auf 2.8 und PHP5 angehoben
   * Quelltext-Verbesserungen für mehr Geschwindigkeit
   * Benutzeroberflächen-Änderungen

### 1.0 ###
* **English**
   * More security checks (Email & Regexp)
* **Deutsch**
   * Mehr Sicherheitskontrollen (E-Mail & Regexp)

### 0.9 ###
* **English**
   * Changes for the current WordPress virus
* **Deutsch**
   * Änderungen für den aktuellen Wordpress-Virus

### 0.8 ###
* **English**
   * Support for WordPress 3.0
   * System requirements: WordPress 2.7
   * Code optimization
* **Deutsch**
   * WordPress 3.0 Unterstützung
   * Systemvorraussetzung: WordPress 2.7
   * Quelltext optimiert

### 0.7 ###
* **English**
   * Advanced templates check
* **Deutsch**
   * Erweiterte Vorlagen-Prüfungen

### 0.6 ###
* **English**
   * WordPress 2.9 support
* **Deutsch**
   * Unterstützung für WordPress 2.9

### 0.5 ###
* **English**
   * Add security scan for the current [WordPress permalink back door](http://mashable.com/2009/09/05/wordpress-attack/)
   * Software architecture changes
* **Deutsch**
   * Sicherheits-Überprüfung für die aktuelle [Wordpress Permalink Hintertür](http://mashable.com/2009/09/05/wordpress-attack/) hinzugefügt
   * Software-Architektur Änderungen

### 0.4 ###
* **English**
   * Adds support for WordPress new changelog readme.txt standard
   * Various changes for more speed, usability and security
* **Deutsch**
   * Unterstützung für den neuen Changelog readme.txt Standard von Wordpress
   * Verschiedene Änderungen für mehr Geschwindigkeit, Benutzerfreundlichkeit und Sicherheit

### 0.3 ###
* **English**
   * Add alternate e-mail address (admin e-mail address as default)
   * Admin notice on dashboard where it has found the virus suspicion
   * Added blog URL in e-mail
   * WordPress 2.8 support
   * Check for hidden iframes
   * Bugfix for IE problem with box positions
   * Cleanup the source code
   * Language support for Persian
* **Deutsch**
   * Möglichkeit eine alternative E-Mail-Adresse hinzuzufügen (Admin E-Mail-Adresse als Standard)
   * Admin Mitteilung auf dem Dashboard, wo ein Virus-Verdacht gefunden wurde
   * Blog-URL in E-Mail hinzugefügt
   * Unterstützung von WordPress 2.8
   * Überprüfung nach versteckten Iframes
   * Fehlerbehebung für IE Problem mit Box-Positionen
   * Bereinigung des Quelltextes
   * Sprachunterstützung für Persisch

### 0.2 ###
* **English**
   * Whitelist: Mark the suspicion as "No virus"
   * Improving the output formatting
   * Add WPlize library for option data
   * Language support for Italian
* **Deutsch**
   * Weiße Liste: Einen Verdacht als "kein Virus" markieren
   * Verbesserung der Ausgabeformatierung
   * WPlize Bibliothek für Optionsdaten hinzugefügt
   * Sprachunterstützung für Italienisch

### 0.1 ###
* **English**
   * AntiVirus for WordPress goes online
* **Deutsch**
   * AntiVirus für Wordpress geht online
