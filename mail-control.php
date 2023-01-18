<?php

/**
 * Plugin Name: Mail Control - SMTP Deliverability, Email logging, opens and clicks Tracking
 * Plugin URI: https://www.wpmailcontrol.com
 * Version: 0.2.6
 * Author: Instareza
 * Author URI: https://www.instareza.com
 * Description: Take control over your emails, send using smtp, log and track emails clicks and opening, and allow sendind the emails in the background to speed up responses
 * License: GPL
 * Text Domain: mail-control
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Stable tag: 0.2.6
 *
 * @package Mail_Control
 */
namespace Mail_Control;

// Init freemius integration.
require __DIR__ . '/init_freemius.php';
define( 'MC_ASSETS_DIR', __DIR__ . '/assets/' );
define( 'MC_EMAIL_TABLE', 'email' );
define( 'MC_EVENT_TABLE', 'email_event' );
define( 'MC_TRACK_URL', '/trackmail/' );
require __DIR__ . '/vendor/autoload.php';
// Main tracking action.
if ( isset( $_SERVER['REQUEST_URI'] ) && strtok( $_SERVER['REQUEST_URI'], '?' ) === MC_TRACK_URL ) {
    include __DIR__ . '/track.php';
}

if ( is_admin() ) {
    // Install and create tables.
    require __DIR__ . '/install.php';
    // Admin Screens.
    require __DIR__ . '/admin.php';
}

require __DIR__ . '/email-tracker.php';
require __DIR__ . '/background-mailer.php';
require __DIR__ . '/smtp-mailer.php';
// Load settings.
require __DIR__ . '/settings.php';