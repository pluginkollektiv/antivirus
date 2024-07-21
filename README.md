# AntiVirus #
* Contributors:      pluginkollektiv
* Tags:              antivirus, malware, scanner, phishing, safe browsing, vulnerability
* Donate link:       https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=TD4AMD2D8EMZW
* Requires at least: 4.1
* Requires PHP:      5.2
* Tested up to:      6.6
* Stable tag:        1.5.1
* License:           GPLv2 or later
* License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Security plugin to protect your blog or website against exploits and spam injections.

## Description ##
*AntiVirus* is an easy-to-use, safe tool to harden your WordPress site against exploits, malware and spam injections.
You can configure *AntiVirus* to perform an automated daily scan of your theme files. If the plugin detects any suspicious code injections, it will send out a notification to a previously configured e-mail address.

In case your WordPress site has been hacked, *AntiVirus* will help you to become aware of the problem very quickly in order for you to take immediate action.

### Features ###
* Scan for suspicious code in the theme files (daily scan with email notifications and manual scan) with an option to mark detected cases as false positive
* Checksum verification for WordPress Core files
* Optional: Google Safe Browsing for malware and phishing monitoring.

A complete documentation is available on the [AntiVirus website](https://antivirus.pluginkollektiv.org/documentation/).

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
* Maintainers: [pluginkollektiv](https://pluginkollektiv.org)


## Changelog ##

### 1.5.1 ###
* Fix issue with "dismiss" button if multiple warnings are found for one theme file (#135) (#136)

### 1.5.0 ###
* Fix deprecation warning with PHP 8.1+ (#126) (#127)
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
* Drop recursive check on option that failed in several scenarios (#96, #97)
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
* updated [plugin authors](https://pluginkollektiv.org/de/hallo-welt/)

For the complete changelog, check out our [GitHub repository](https://github.com/pluginkollektiv/antivirus).

## Upgrade Notice ##

### 1.5.1 ###
This is a bugfix release which resolves a UI issue. Recommended for all users.

### 1.5.0 ###
This update finally removed support for Safe Browsing API without an API key.
If you are using this feature and did not provide your own key yet, it will be disabled.
Can be easily enabled again, just enter a key.

## Screenshots ##
1. WordPress AntiVirus settings
2. Theme scan results
