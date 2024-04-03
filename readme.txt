=== Mail Control - Email Customizer, SMTP Deliverability, logging, open and click Tracking ===
Contributors: rahal.aboulfeth
Tags: email customizer, smtp, email, email log, email deliverability, email tracking
Requires at least: 5.0
Tested up to: 6.5
Requires PHP: 7.4
License: GPL
Stable tag: 0.3.7

Design and customize email templates, control your SMTP email deliverability, track your emails clicks and openings, and allow defering the emails as a background process to speed up your pages.

== Description ==

Design and customize your emails using Wordpress native customizer (compatible with WooCommerce), control your SMTP email deliverability, track your emails clicks and openings (reads), and allow defering the emails as a background process to speed up your pages.

With Mail Control, you will have a better control over how your emails are handled by wordpress (and WooCommerce) from email design and customization to smtp delivery and click tracking.

=== Email Designer using Wordpress native customizer ===
Design beautiful emails using the WooCommerce compatible Email Customizer. The UI provided by Wordpress Native Customizer makes it easy to customize the look and feel of your emails.

=== Tracking and logging emails opening and clicks ===
This will allow you to optimize how you craft your emails content and get the better of your email marketing.

=== Sending Emails via an SMTP server ===
For a better deliverability for your emails, Mail Control lets you setup easily you favorite SMTP server.

=== Testing Email Deliverability of your SMTP server ===
Help you make sure your smtp servers checks all the requierements for the perfect deliverability by testing your SFP, DKIM and DMARC setup (and more on this to come).

=== Sending the emails by a background process to speed up your pages ==
No more page timeout because the smtp server take too much time to respond, defer your emails and let a cronjob take care of sending your emails in a separate process.

=== Having a log of all the emails sent (or failed) by wordpress ===
You can find all the emails handled by wordpress (or still in the queue), and resend them if needed.

=== More features and documentation coming ===

[Mail Control Online Documentation](https://www.wpmailcontrol.com/docs/) writing is in progress.

== Frequently Asked Questions ==

= How can I customize wordpress and WooCommerce emails? =
After installing Mail Control, you can access the Email customizer to design you emails (you will be able to select what email to customize) and send a preview email before activating the feature.

= Why should I use an Email Tracker to track Email Opening and clicks? =

If your recipient receives your email, don't you want to know? Is it in his spam folder? Is the email subject clear enough that he actually opens the email? is your call to action compelling enough so he clicks on it?

What if you notice that a visitor reads you email multiple times? isn't it a good indicator that he is interested in what you are selling? maybe you should contact him again...

Email Tracking will help you detect problems and opportunities so you can take action, it is an invaluable tool for the great marketer.

= How Mail Control will help me solve my email deliverability problems? =

With this plugin, you will be able to test your domain name's SPF, DKIM and DMARC records and have actionable suggestions to fix any detected issue.

As it is an experimental feature for now, we can assist you if you need any help.

= How can I defer sending the emails and why should I? =

With this plugin, you can activate the Background Mailer, this will defer sending the email to a background cron job.

This will speed up your pages and response times and more importantly avoid any critical timeout error in your checkout experience.

= Will Mail Control work with my theme and other plugins like WooCommerce or Contact Form 7 ? =

Probably, as long as your theme or plugin doesn't override the wp_mail function. Mail Control should work just fine.

As tested now, mail control is compatible with "Contact Form 7" and "WooCommerce".

= Can Mail Control send emails using Gmail? =
Sure, you can setup the SMTP Mailer to use Gmail. 
First, you should create a [password application](https://myaccount.google.com/apppasswords) for your wordpress site, this will allow Mail Control to send emails using your gmail account.
SMTP Host : smtp.gmail.com
SMTP Port : 465
Encryption : ssl
Smtp User : your-gmail-adress@gmail.com
Smtp Password : The password your created earlier
From Email : your-gmail-adress@gmail.com

Please be aware that there is a [500 emails limit per day](https://support.google.com/mail/answer/22839).


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
= 0.3.7 =
* Customizer : for a safe customization, restricting the allowed blocks while customizing email template
* Upgraded dependencies and tested up to
* Hiding the smtp password
* Backgroud Mailer : Addded setting Max Emails Per Run
* Better onboarding experience
= 0.3.6 =
* Added button to allow sending mails in the queue
* Improve security
= 0.3.5 =
* Improve Email Customizer compatibility with Astra Theme
= 0.3.2, 0.3.3 , 0.3.4  =
* More Review : Sanitize early, Escape Late, Always Validate 
= 0.3.1 =
* Review : Sanitize early, Escape Late, Always Validate
= 0.3.0 =
* Update dependencies
* Escape before printing admin table
= 0.2.8 =
* Bug fix : customized message was not saved correctly to mail queue if background mailer is active.
= 0.2.7 : Email Customizer =
* Customize emails using wordpress native customizer (compatible with WooCommerce)
= 0.2.6 =
* Fix regression : send test email result doesn't show
* Fix composer dependencies : downgrade symfony/cssselector to 5.4.17 to keep minimum php version to 7.4
= 0.2.5 : Resend Wordpress emails =
* Added possibility to resend emails
* Email Detail will list and show email attachments (image viewer or download link)
* Minimum capabilities to access Email logs and Mail Control settings can be configured in the settings page
* Use custom icon go in dashboard menu
= 0.2.4 =
* Added Show details button in the log view to display the emails content, headers, errors as well as events details (opens and clicks)
* Emails logs and Mail Control settings can be accessed with "manage_options" permission
= 0.2.3 =
* Fix notice undefined variables and typo in readme file
= 0.2.2 : Email Delivreability testing  =
* SMTP Mailer : Added SPF, DKIM and DMARC Tester (experimental feature)
* Settings page : Better variables sanitization, and showing validation error
= 0.2.1 =
* Added Domain Path to plugin file comment
* Added field descriptions to SMTP Mailer Settings section
* Reviewed French translation
= 0.2 =
* Added the possibility to send a Test Email in the SMTP Mailer Section
= 0.1 =
* Initial release