<?php

/**
 * Plugin Name: Mail Control - Email Customizer, SMTP Deliverability, logging, open and click Tracking
 * Plugin URI: https://www.wpmailcontrol.com
 * Version: 0.3.7
 * Author: Instareza
 * Author URI: https://www.instareza.com
 * Description: Design and customize emails, send using smtp, log and track emails clicks and opening, and allow sending the emails in the background to speed up responses
 * License: GPL
 * Text Domain: mail-control
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Stable tag: 0.3.7
 *
 * @package Mail_Control
 */
namespace Mail_Control;

define( 'MC_VERSION', '0.3.7' );
define( 'MC_URL', plugin_dir_url( __FILE__ ) );
define( 'MC_ASSETS_DIR', __DIR__ . '/assets/' );
define( 'MC_EMAIL_TABLE', 'email' );
define( 'MC_EVENT_TABLE', 'email_event' );
define( 'MC_TRACK_URL', '/trackmail/' );
define( 'MC_INCLUDES', __DIR__ . '/includes/' );
define( 'MC_TEMPLATES', __DIR__ . '/templates/' );
define( 'MC_VENDOR', __DIR__ . '/vendor/' );
define( 'MC_PLUGIN_ASSETS', untrailingslashit( plugins_url( '/', __FILE__ ) ) . '/assets/' );
define( 'MC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
// Init freemius integration.
require MC_INCLUDES . 'init_freemius.php';
require __DIR__ . '/vendor/autoload.php';
// Main tracking action.
if ( isset( $_SERVER['REQUEST_URI'] ) && strtok( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), '?' ) === MC_TRACK_URL ) {
    include MC_INCLUDES . 'track.php';
}

if ( is_admin() ) {
    // Install and create tables.
    require MC_INCLUDES . 'install.php';
    // Admin Screens.
    require MC_INCLUDES . 'admin.php';
}

require MC_INCLUDES . 'email-tracker.php';
require MC_INCLUDES . 'background-mailer.php';
require MC_INCLUDES . 'smtp-mailer.php';
require MC_INCLUDES . 'email-customizer.php';
// Load settings.
require MC_INCLUDES . 'settings.php';