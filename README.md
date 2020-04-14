# AntiVirus #
* Contributors:      pluginkollektiv
* Tags:              antivirus, malware, scanner, phishing, safe browsing, vulnerability
* Donate link:       https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=TD4AMD2D8EMZW
* Requires at least: 3.8
* Requires PHP:      5.2
* Tested up to:      5.4
* Stable tag:        1.3.10
* License:           GPLv2 or later
* License URI:       http://www.gnu.org/licenses/gpl-2.0.html

Security plugin to protect your blog or website against exploits and spam injections.

## Description ##
*AntiVirus for WordPress* is a easy-to-use, safe tool to harden your WordPress site against exploits, malware and spam injections.
You can configure *AntiVirus* to perform an automated daily scan of your theme files. If the plugin happens to detect any suspicious code injections, it will send out a notification to a previously configured e-mail address.

In case your WordPress site has been hacked, *AntiVirus* will help you to become aware of the problem very quickly in order for you to take immediate action.

### Features ###
* Virus alert in the admin bar
* Cleaning up after plugin removal
* Daily scan with email notifications
* Theme template checks
* Whitelist solution: Mark suspected cases as "no virus"
* Manual check of template files with alerts on suspected cases
* Optional: Google Safe Browsing for malware and phishing monitoring.

### Support ###
* Community support via the [support forums on wordpress.org](https://wordpress.org/support/plugin/antivirus)
* We don’t handle support via e-mail, Twitter, GitHub issues etc.

### Contribute ###
* Active development of this plugin is handled [on GitHub](https://github.com/pluginkollektiv/antivirus).
* Pull requests for documented bugs are highly appreciated.
* If you think you’ve found a bug (e.g. you’re experiencing unexpected behavior), please post at the [support forums](https://wordpress.org/support/plugin/antivirus) first.
* If you want to help us translate this plugin you can do so [on WordPress Translate](https://translate.wordpress.org/projects/wp-plugins/antivirus).

### Credits ###
* Author: [Sergej Müller](https://sergejmueller.github.io/)
* Maintainers: [pluginkollektiv](http://pluginkollektiv.org/)

## Installation ##
* If you don’t know how to install a plugin for WordPress, [here’s how](https://codex.wordpress.org/Managing_Plugins#Installing_Plugins).

### Requirements ###
* PHP 5.2.4 or greater
* WordPress 3.8 or greater

## Frequently Asked Questions ##

### Will AntiVirus protect my site from being hacked? ###
Not literally "protect from". The plugin’s purpose is to *detect* any "hack" that has already happened and enable you to take immediate action upon it.

A complete documentation is available in the [GitHub repository Wiki](https://github.com/pluginkollektiv/antivirus/wiki).

## Changelog ##

### 1.3.10 ###

* Updated PayPal link for donations
* Improve coding standards
* Translation fixes, improvements and cleanups
* Better documentation
* Some minor markup, styling, accessibility and security improvements
* Update to Safe Browsing API v4 (fixing false positive email notifications)
* PHP 7.x compatibility fixes
* Better regex to prevent false positives in file scans

### 1.3.9 ###
* generated a POT file
* added German formal translation
* updated, translated + formatted README.md
* updated expired link URLs in plugin and languages files
* updated [plugin authors](https://gist.github.com/glueckpress/f058c0ab973d45a72720)

### 1.3.8 ###
* Detection and warning for the [MailPoet Vulnerability](http://blog.sucuri.net/2014/07/mailpoet-vulnerability-exploited-in-the-wild-breaking-thousands-of-wordpress-sites.html)

### 1.3.7 ###
* Update the Google Safe Browsing Lookup API to v3.1

### 1.3.6 ###
* Code revision and data validation

### 1.3.5 ###
* Optimizations for WordPress 3.8

### 1.3.4 ###
* [Google Safe Browsing](http://en.wikipedia.org/wiki/Google_Safe_Browsing) for malware and phishing monitoring with e-mail notification. [Additional information on Google+](https://plus.google.com/110569673423509816572/posts/H72FFwvna1i) (only german)

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

For the complete changelog, check out our [GitHub repository](https://github.com/pluginkollektiv/antivirus).

## Upgrade Notice ##

### 1.3.10 ###
This is a maintenance release, fixing our Google Safe Browsing lookup and some minor issues.

## Screenshots ##
1. WordPress AntiVirus settings
