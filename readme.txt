=== Mail Control ===
Contributors: rahal.aboulfeth
Tags: email tracking, email, smtp, tracking, email deliverability, email background, mail tracker, email log, email marketing
Requires at least: 5.0
Tested up to: 6.1
Requires PHP: 7.4
License: GPL
Stable tag: 0.2.4

Control your SMTP email deliverability, track your emails clicks and openings, and allow sending the emails as a background process to speed up your pages.

== Description ==

Control your SMTP email deliverability, track your emails clicks and openings, and allow sending the emails as a background process to speed up your pages.

With Mail Control, you will be able to have a better control over how your emails are handled by wordpress

=== Tracking emails opening and clicks ===
This will allow you to optimize how you craft your emails content and get the better of your email marketing.
=== Sending Emails via an SMTP server ===
Need a better deliverability for your emails? use a reputable SMTP server is the way to go.
=== Testing Email Delivrability of your SMTP server ===
Help you make sure your smtp servers checks all the requierements for the perfect deliverability by testing your SFP, DKIM and DMARC setup (and more on this to come).
=== Sending the emails by a background process to speed up your pages ==
No more page timeout because the smtp server take too much time to respond, let a cronjob take care of sending your emails in a separate process.
=== Having a log of all the emails sent (and failed) by wordpress ===
You can find all the emails handled by wordpress ( or still in the queue ).

== Frequently Asked Questions ==

= Why should I use an Email Tracker to track Email Opening and clicks? =

If your recipient receives your email, don't you want to know? Is it in his spam folder? Is the email subject clear enough that he actually opens the email? is your call to action compeling enough so he clicks on it?

What if you notice that a visitor reads you email multiple times? isn't it a good indicator that he is interested in what you are selling? maybe you should contact him again...

Email Tracking will help you detect problems and opportunities so you can take action, it is an invaluable tool for the great marketer.

= How Mail Control will help me solve my email deliverability problems? =

With this plugin, you will be able to test your domain name's SPF, DKIM and DMARC records and have actionable suggestions to fix any detected issue.

As it is an experimental feature for now, we can assist you if you need any help.

= Will Mail Control work with my theme and other plugins like WooCommerce or Contact Form 7 ? =

Probably, as long as your theme or plugin doesn't override the wp_mail function. Mail Control should work just fine.

As tested now, mail control is compatible with "Contact Form 7" and "WooCommerce".

= Where can I get support? =

If you get stuck, you can ask for help in the [Mail Control Plugin Forum](https://wordpress.org/support/plugin/mail-control).
== Installation ==
1. Upload the plugin to your plugins folder: 'wp-content/plugins/'
2. Activate the 'Mail Control' plugin from the Plugins admin panel.
3. Customize your installation in the "Settings" Page ( Then send a test email )

== Screenshots ==
1. Configure Logging and Tracking
2. Configure Background Mailer Settings
3. Configure Smtp Mailer
4. Test your smtp confirugation, "Send a test email" and "Test your SPF, DKIM, and domain DMARC setup"

== Changelog ==
= 0.2.4 =
* Added Show details button in the log view to display the emails content, headers, errors as well as events details ( opens and clicks )
* Emails logs and Mail Control settings can be accessed with "manage_options" permission
= 0.2.3 =
* Fix notice undefined variables and typo in readme file
= 0.2.2 : Email Delivreability testing  =
* SMTP Mailer : Added SPF, DKIM and DMARC Tester (experimental feature)
* Settings page : Better sanitization, and showing validation error
= 0.2.1 =
* Added Domain Path to plugin file comment
* Added field descriptions to SMTP Mailer Settings section
* Reviewed French translation
= 0.2 =
* Added the possibility to send a Test Email in the SMTP Mailer Section

= 0.1 =
* Initial release