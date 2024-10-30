=== InWebo Login ===

Contributors: Emmanuel_N
Tags: security, authentication, one-time-password, SSO, SaaS
Requires at least: 3.0
Tested up to: 3.3.2
Stable tag: 1.2.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

InWebo strong authentication plugin for WordPress

== Description ==

In-Webo Technologies provides a Strong Authentication as a Service solution that makes authentication process altogether secure, simple and affordable to any website.

If you publish a website where "member access" or "user accounts" are protected by a password, our solution enables:
 * To replace password authentication by a multi-factor authentication making connection to any website super easy and yet, more secure
 * To prevent users from remembering additional login and passwords or having to use password recovery methods as a standard way to access to your service
 * To integrate a user-centric, multi-browser, mobility-ready and free access control service

The first step to using InWebo solution is to create your InWebo account on our self-service platform: https://www.myinwebo.com/signup/3

== Prerequisites ==

Before installing and configuring the plugin, you need to create a Web Services API PEM certificate for the InWebo service that will manage the access to your site:
 * Signup to create your InWebo account on our platform: https://www.myinwebo.com/signup/3
 * Connect to InWebo administration console to generate the certificate (administrator connection required)
 * Save it to your desktop
 * Note the ID of the service for which the certificate was created, and the passphrase used to generate the certificate

For more information about InWebo account & service management: http://developer.inwebo.com/en/node/11

== Installation ==

Important: see "Other Notes" section for installation prerequisites

 * Download the package of the plugin
 * Install it as you would do for any standard WordPress plugin
 * Enable the plugin
 * Add InWebo certificate in the includes subfolder of the inwebo plugin directory, normally [path-to-your-wordpress-installation]/wp-content/plugins/inwebo/includes

At this step your plugin is installed.

== Plugin configuration ==

 * Open the plugin administration page (it appears as "In-Webo Login" in the WordPress administration menu, under the "Settings" section)
 * Add the required information in the appropriate fields: certificate file name (with extension .crt), certificate passphrase and service ID
 * Allow automatic provisioning of In-Webo users or not (see section 6)

The configuration of the plugin is done.

== Enabling InWebo authentication for users ==

InWebo authentication mode is set up at the user level in WordPress. It means you can determine which authentication mode to use for each user (InWebo or WordPress native). To activate InWebo authentication for a user:
 * Display user properties in WordPress administration
 * Tick box "User login is managed by In-Webo" at the bottom of the user page

Notes:
 * Choosing In-Webo authentication mode means that this user will have to authenticate with InWebo. You will have to add him  as user of the service in InWebo administration console before then he can connect to the site
 * It is recommended to leave the authentication of WordPress user "admin" rely on WordPress authentication

== Self-Provisiong of users ==

InWebo authentication plugin for WordPress allows the self provisioning of users in WordPress.

If the automatic provisioning of In-Webo users is allowed, it means that any user created in InWebo administration console can connect to the WordPress site, whether it exists or not in the WordPress users table.
 * If the new InWebo user already exists, the user is simply authenticated by InWebo (check that the user login is managed by InWebo)
 * If it does not exist, the user is first created on WordPress's side, with user login managed by InWebo set to on, then authenticated

== Testing the authentication ==

Before testing the access to your site with InWebo browser plugin, check that your login name for the service in InWebo administration console matches the login name of the user you would like to authenticate with in your CMS.
If you are the first administrator of the service, by default your InWebo login name has been set to "_admin_". Change it to match an appropriate CMS user login name.
E.g.: if you want to authenticate with WordPress login "test" with InWebo, change "_admin_" to "test" in InWebo administration console.

== Frequently Asked Questions ==

See In-Webo F.A.Q. here: http://faq.inwebo.com/en

== Changelog ==

= 1.2.1 =

 * enhanced management of In-Webo users auto provisioning

= 1.2.0 =

 * adding option to allow In-Webo users auto provisioning

= 1.1.1 =

 * minor upgrades & bug fixes

= 1.1.0 =

* adding plugin administration panel to WordPress administration

= 1.0.0 =

 * first version
