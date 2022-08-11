=== WPDirectAdmin ===
Contributors: digitaldevs
Donate link: https://digitaldev.org.uk/
Tags: directadmin, api, control, panel
Requires at least: 6.0
Tested up to: 6.0.1
Requires PHP: 7.4
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

WordPress plugin to connect and control DirectAdmin servers.

== Description ==

The <strong>WPDA</strong> plugin allows users to connect, control and monitor DirectAdmin servers.


== Installation ==


== Changelog ==
= 1.2.0.0 =
Added in new feature to add user and reseller packages.
= 1.2.1.0 =
Added new feature to link/unlink WordPress accounts with DA server users. Link multiple DirectAdmin accounts to a single WordPress user.
= 1.2.2.0 =
Minor update to add in new database tables for future features.
Added CRON schedule every 60 seconds to prepare for automated features.
Changed: Default user/reseller package can now be set per server.
= 1.2.2.2 =
Added new change password and suspend account features
Added in provisions for PRO features
Added in SMS textlocal API
Now shows user email under info icon in user lists.
Added function to reboot server
Added a restart service feature and check_service function
= 1.3.0.1 =
Improved security by removing the local encryption key, the encryption key is now served via the licence key server so if you deactivate the plugin the encryption key is no longer available and users cant access your server login details via the database directly. Activate the plugin with a diffirent licence key and you will get a diffirent encryption key which wont work with your currently encrypted details.
= 1.3.0.2 =
Improved security and encryption.
= 1.4.0.1 =
New feautre in server settings to set an alert trigger time to detect server downtime. IF the server has been down for X time you can set alerts.
Made less calls to the remote DA server to improve performance.
Some minor fixes to SQL statements within core functions.
= 1.4.0.2 =
New action hooks available for 3rd party integrations or plugins. See more at - https://community.digitaldev.org.uk/forum-10.html
Added in new writeToLog function to start writing logs and log system functions.
Added new logging feature on all functions, and a log level setting in admin area.
Added admin snackbar style notifications on log events.