# AntiVirus #
* Contributors:      pluginkollektiv
* Tags:              antivirus, malware, scanner, phishing, safe browsing, vulnerability
* Donate link:       https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=LG5VC9KXMAYXJ
* Requires at least: 3.8
* Tested up to:      4.3
* Stable tag:        trunk
* License:           GPLv2 or later
* License URI:       http://www.gnu.org/licenses/gpl-2.0.html


Security plugin to protect your blog or website against exploits and spam injections.


## Description ##
*AntiVirus for WordPress* is a easy-to-use, safe tool to harden your WordPress site against exploits, malware and spam injections.
You can configure *AntiVirus* to perform an automated daily scan of your theme files and database tables. If the plugin happens to detect any suspicious code injections, it will send out a notification to a previously configured e-mail address.

In case your WordPress site has been hacked, *AntiVirus* will help you to become aware of the problem very quickly in order for you to take immediate action.


### Features ###
* Virus alert in the admin bar
* Cleaning up after plugin removal
* Translations into many languages​​
* Daily scan with email notifications
* Database tables and theme templates checks
* WordPress 3.x ready: both visually and technically
* Whitelist solution: Mark suspected cases as "no virus"
* Manual check of template files with alerts on suspected cases
* Optional: Google Safe Browsing for malware and phishing monitoring.


> #### Auf Deutsch? ####
> Für eine ausführliche Dokumentation besuche bitte das [AntiVirus-Wiki](https://github.com/pluginkollektiv/antivirus/wiki).
>
> **Community-Support auf Deutsch** erhältst du in einem der [deutschsprachigen Foren](https://de.forums.wordpress.org/forum/plugins); im [Plugin-Forum für AntiVirus](https://wordpress.org/support/plugin/antivirus) wird, wie in allen Plugin-Foren auf wordpress.org, ausschließlich **Englisch** gesprochen.


### Languages ###
* English
* German
* German formal


### Credits ###
* Author: [Sergej Müller](https://sergejmueller.github.io/)
* Maintainers: [pluginkollektiv](http://pluginkollektiv.org/)


## Installation ##
* If you don’t know how to install a plugin for WordPress, [here’s how](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins).

### Requirements ###
* PHP 5.2.4 or greater
* WordPress 3.8 or greater

## Frequently Asked Questions ##

### Will AntiVirus protect my site from being hacked? ###
Not literally “protect from”. The plugin’s purpose is to _detect_ any “hack” that has already happened and enable you to take immediate action upon it.


## Changelog ##
### 1.3.9 ###
* generated a POT file
* added German formal translation
* updated, translated + formatted README.md
* updated expired link URLs in plugin and languages files
* updated [plugin authors](https://gist.github.com/glueckpress/f058c0ab973d45a72720)

### 1.3.8 ###
* Deutsch: Erkennung der [MailPoet-Sicherheitslücke](http://blog.sucuri.net/2014/07/mailpoet-vulnerability-exploited-in-the-wild-breaking-thousands-of-wordpress-sites.html)
* English: Detection and warning for the [MailPoet Vulnerability](http://blog.sucuri.net/2014/07/mailpoet-vulnerability-exploited-in-the-wild-breaking-thousands-of-wordpress-sites.html)

### 1.3.7 ###
* Deutsch: Aktualisierung auf Safe Browsing Lookup API 3.1
* English: Update the Google Safe Browsing Lookup API to v3.1

### 1.3.6 ###
* Deutsch: Code-Revision und Datenvalidierung
* English: Code revision and data validation

### 1.3.5 ###
* Deutsch: Optimierungen für WordPress 3.8
* English: Optimizations for WordPress 3.8

### 1.3.4 ###
* Deutsch: Benachrichtigung per E-Mail, sobald [Google Safe Browsing](http://en.wikipedia.org/wiki/Google_Safe_Browsing) Malware im Blog erkennt. [Mehr auf Google+](https://plus.google.com/110569673423509816572/posts/H72FFwvna1i)
* English: [Google Safe Browsing](http://en.wikipedia.org/wiki/Google_Safe_Browsing) for malware and phishing monitoring.

### 1.3.3 ###
* Add inspection for iFrames
* Retina support for teaser and screenshot

### 1.3.2 ###
* Remove the check for include and require commands (#wpforce)

### 1.3.1 ###
* Compatibility with WordPress 3.4
* High-resolution plugin icon for retina displays
* Remove icon from the admin sidebar
* System requirements: From PHP 5.0 to PHP 5.1

### 1.3 ###
* Xmas Edition

### 1.2 ###
* "Virus suspected" alert in the admin bar
* Fix for the manual scan link on dashboard
* More detailed checks for existing malware
* Code adjustments for WordPress 3.3

### 1.1 ###
* Testing for templates with empty content
* Minimum requirement upgraded to 2.8 and PHP5
* Code improvements for more speed
* GUI changes

### 1.0 ###
* More security checks (Email & Regexp)

### 0.9 ###
* Changes for the current WordPress virus

### 0.8 ###
* Support for WordPress 3.0
* System requirements: WordPress 2.7
* Code optimization

### 0.7 ###
* Advanced templates check

### 0.6 ###
* WordPress 2.9 support

### 0.5 ###
* Add security scan for the current [WordPress permalink back door](http://mashable.com/2009/09/05/wordpress-attack/ "WordPress permalink back door")
* Software architecture changes

### 0.4 ###
* Adds support for WordPress new changelog readme.txt standard
* Various changes for more speed, usability and security

### 0.3 ###
* Add alternate e-mail address (admin e-mail address as default)
* Admin notice on dashboard where it has found the virus suspicion
* Added blog URL in e-mail
* WordPress 2.8 support
* Check for hidden iframes
* Bugfix for IE problem with box positions
* Cleanup the source code
* Language support for Persian

### 0.2 ###
* Whitelist: Mark the suspicion as "No virus"
* Improving the output formatting
* Add WPlize library for option data
* Language support for Italian

### 0.1 ###
* AntiVirus for WordPress goes online


## Screenshots ##
1. WordPress AntiVirus settings
