=== BP Restrict Signup by Email Domain  ===
Contributors: r-a-y, cuny-academic-commons
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=V9AUZCMECZEQJ
Tags: buddypress, registration
Requires at least: WordPress 3.4.x, BuddyPress 1.6
Tested up to: WordPress 3.7.x, BuddyPress 1.9-bleeding
Stable tag: trunk
 
Only allow users with email addresses from certain domains to register in BuddyPress.

== Description ==

User registration spam is prevalent in BuddyPress.

One way to dramatically decrease signup spam is to restrict the email address domains that users can sign up with.

WordPress multi-site has a native option called "Limited Email Registrations".  But this option requires you to enter the full email domain.  For example, if you only wanted to allow .edu email addresses to register, this is not possible.

This plugin restricts registrations to the email domains that you specifiy and works in WordPress single-site and multi-site.

Plugin was developed for the [CUNY Academic Commons](http://commons.gc.cuny.edu).  Licensed under the GPLv2 or later.

== Installation ==

* Download, install and activate this plugin.
* Login to the WP admin dashboard and navigate to the "Settings > BuddyPress" page.  Next, click on the "Settings" tab.
* You should see a new section called "Email Address Restrictions".
* Under "Whitelist Email Domains", list the email domains that users can register with.

eg. If you type in:

    .edu
    .org

This will only allow users with .edu or .org email addresses to register on the site.  Anything else will be blocked.

If you know that your site will only allow email addresses from a specific domain, you can enter that as well.

* To customize the error message and registration blurb, edit the other two fields.


== Changelog ==

= 1.0 =
* Initial public release
