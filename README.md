Lockdown WP Admin
=============

This is a free WordPress plugin that will add security to your WordPress site. It will hide the admin area and rename the login area. Also, it add HTTP auth to your login area, too. We want to make sure you're safe on the web.

Author
------

Sean Fisher

[@talkingwithsean](http://twitter.com/talkingwithsean)

[http://talkingwithsean.com/](http://talkingwithsean.com/)


Requirements
------------
WordPress 3.0

PHP 5

MySQL 5

Description
-----------

This plugin will hide /wp-admin/ when you aren't logged in. If a user isn't logged in and they attempt to access /wp-admin/ directly, they will be unable to and it will return a 404.

Also, you can add HTTP authentication directly from WP admin and add custom username/password combinations for the HTTP auth, or use the WordPress credentials.

This doesn't touch any .htaccess files or change the WordPress core files. All the CSS/Images under /wp-admin/ are still accessible, just not the .php ones.

If you enable HTTP authencation, it will add HTTP auth to wthe PHP files in /wp-admin/. It will also rename the login URL from /wp-login.php to whatever you want (/login/, /random-stuff/, etc)


Installation
------------

	1. Upload `/lockdown-wp-admin/` to the `/wp-content/plugins/` directory
	2. Activate the plugin through the 'Plugins' menu in WordPress
	3. Navigate to the "Lockdown WP" menu


Changelog
---------
> 1.0
> > Initial release

> 1.0.1
> > Fixed a link to a broken file

> 1.1
> > Fixed a bug on activating the plugin network wide, we disabled network wide activation.
> > Cleaned up the plugin and prevented a double loop of the HTTP check, unnecessary.

> 1.2
> > Cleaned up more code.
> > Security fixes that will prevent somebody from possibly hijacking your website. (Props Jon Cave)

> 1.3.1
> > Added the ability to change the login URL entirely. It will disable /wp-login.php and give it whatever you want to make it.

> 1.4
> > Fixed a bug with user's with a index.php base
> > Added stats for us to collect about about URL setup and server configuration for our users. This will let us make the plugin even better.
> > Fixed bug for having private user management in WP Admin