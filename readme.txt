=== Mail Control ===
Contributors: rahal.aboulfeth
Tags: email tracking, email stmp, email logging, mail tracking, email tracker, marketing email, background email
Requires at least: 5.0
Tested up to: 6.1
Requires PHP: 7.4
License: GPL
Stable tag: 0.2.1

Have control over your emails, log and track emails clicks and opening, send using smtp, and allow sending the emails in the background to speed up responses.

== Description ==
Have control over your emails, log and track emails clicks and opening, send using smtp, and allow sending the emails in the background to speed up responses.

With Mail Control, you will be able to have a better control over how your emails are handled by wordpress

=== Tracking emails openning and clicks ===
This will allow you to optimize how you craft your emails content and get the better of your email marketing
=== Sending Emails via an SMTP server ===
Need a better deliverability for your emails? use a reputable SMTP server is the way to go
=== Allowing sending the emails by a background process to speed up you pages ==
No more page timeout because the smtp server take too much time to respond, let a cronjob take care of sending your emails in a separate process.
=== Having a log of all the emails sent (and failed) by wordpress ===
You can find all the emails handled by wordpress ( or still in the queue )


== Frequently Asked Questions ==

= Why should I track Email Opening and clicks? =

If your recipient receives your email, don't you want to know? Is it in his spam folder? Is the email subject clear enough that he actually opens the email? is your call to action compeling enough so he clicks on it?

What if you notice that a visitor reads you email multiple times? isn't it a good indicator that he is interested in what you are selling? maybe you should contact him again...

= Where can I get support? =

If you get stuck, you can ask for help in the [Mail Control Plugin Forum](https://wordpress.org/support/plugin/mail-control).

= Will Mail Control work with my theme and other plugins like Woocommerce or Contact form 7 ? =

Probably, as long as your theme or plugin doesn't override the wp_mail function. Mail Control should work just fine.
As tested now, mail control is compatible with contact form 7 and woocommerce

== Installation ==
1. Upload the plugin to your plugins folder: 'wp-content/plugins/'
2. Activate the 'Mail Control' plugin from the Plugins admin panel.
3. Customize your installtion in the "Settings" Page ( Then send a test email )

== Screenshots ==
1. Configure Logging and Tracking
2. Configure Background Mailer Settings
3. Configure Smtp Mailer

== Changelog ==
= 0.2.1 =
* Added Domain Path to plugin file comment
* Added field descriptions to SMTP Mailer Settings section
* Reviewd French translation
= 0.2 =
* Added the possibility to send a Test Email in the SMTP Mailer Section

= 0.1 =
* Initial release