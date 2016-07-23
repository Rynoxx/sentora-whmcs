# Changelog #

## 2.3.7 ##
- Reverted back to custom api functions for user creation
- Added the ability to set custom admin dir in Sentora
- Enabled the ability to disable single domain rather than the whole account.
- Added a basic email template for WHMCS

## 2.3.6 ##
- Improved error messages

## 2.3.5 ##
- Added the ability to customize the generated username
- Further migration towards using default sentora modules for API calls

## 2.3.4 ##
- Extending the error messages further for certain cases (no server configured for the product/order and no IP/Hostname assigned to a server)
- Put some debug code behind "If Debug" statements

## 2.3.3 ##
- Allows the option to automatically create default DNS records upon account creation
- Note added to the 'Reseller' option.
- Misc small changes.

## 2.3.2 ##
- Fixed [issue #2](https://github.com/Rynoxx/sentora-whmcs/issues/2)

## 2.3.1 ##
- Bumped version to 2.3.1 to match the version (plus one, due to updates) of AWServer ZPanelX version of the plugin.
- Included some ZPanelX compatability updates from MarkDark [Source](http://forums.sentora.org/showthread.php?tid=1563&pid=12786#pid12786)
- Changes to the ZPanelX compatability updates to ensure a more "neutral" use of ZPanelX/Sentora in comments while showing the one which is relevant for the currently installed panel.
- Translation updates.

## 1.3.10 ##
- Added the ability to choose whether or not resellers can view the API key
- Some style edits to the module.zpm file (module page)
- Changed the module numbering to allow 2 digit numbers in versions...

## 1.3.9 ##
- Fixed some compatibility issues with PHP 5.3 (Thanks to charityz2 for reporting the issues and letting me debug using his VPS)
- Removed some unneeded files from the dependencies of the Senitor API (documentation files, files for testing the dependencies)

## 1.3.8 ##
- Fixed a few more errors on windows
- Removed the ability to configure whether or not to use default Sentora modules in some situations
- Increased usage of default Sentora modules when using API calls

## 1.3.7 ##
- Fixed the deploy script(s) to work on Windows as well.
- Fixed errors when using windows, the user is now properly created
- Improved error messages when creating users.

## 1.3.6 ##
- Fixed various bugs occuring on windows
- Added the ability to configure the use of the default Sentora modules instead of the WHMCS in some cases, for API calls.
	- Can be configured per package

## 1.3.5 ##
- Fixed version warning message
- Fixed created users not being put into the right usergroup (Reseller, not reseller)
- Fixed domains not being created in Sentora

## 1.3.4 ##
- Added support for the WHMCS debugging system

## 1.3.3 ##
- Updated the module page
	- Using the new Sentora notice manager for the warning message and the "Settings updated" message
	- Using the proper button classes for the buttons

## 1.3.2 ##
- Fixed the module.zpm to match the proper HTML structure
- Changed default icon size to 35

## 1.3.1 ##
- Fixed UsageUpdate, forgot to make it use Senitor instead of the old xmws API
- Changed version, now the version will be more correct in relation to semantic versioning [SemVer.org](http://semver.org)

## 1.3 ##
- Changed API to "Senitor"
- Added API key to WHMCS module
- Allowing Sentora theme to change the Icon of the WHMCS module section (In Sentora)
	- Credits & Source: Ron-e https://github.com/sentora/sentora-core/commit/b88b1295db03cff536b33eebb865f0fa69e783ce

## 1.2 ##
- Testing it in Sentora
- Doing some minor changes
	- Editing variable names
	- Editing comments
- Updated XMWS

## 1.1 ##
- Fix Control panel link
- Fix error message
- Added Change Password
- Added Change Package

## 1.0 ##
- First Release
