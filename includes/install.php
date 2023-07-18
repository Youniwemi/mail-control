<?php

namespace Mail_Control;

define( 'MC_DB_VERSION', 1 );
/**
 * Installs or upgrades the database tables.
 */
function install_or_upgrade( $current_version )
{
    global  $wpdb ;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $emails_table = "CREATE TABLE `" . $wpdb->prefix . MC_EMAIL_TABLE . "` (\n\t  `id` bigint(30) unsigned NOT NULL AUTO_INCREMENT,\n\t  `date_time` datetime NOT NULL,\n\t  `to` varchar(255)  NOT NULL DEFAULT '',\n\t  `subject` varchar(255)  NOT NULL DEFAULT '',\n\t  `message` text  DEFAULT NULL,\n\t  `message_plain` text  DEFAULT NULL,\n\t  `headers` longtext  DEFAULT NULL,\n\t  `attachments` longtext  DEFAULT NULL,\n\t  `fail` text  DEFAULT NULL,\n\t  `in_queue` tinyint(1) DEFAULT NULL,\n\t  PRIMARY KEY (`id`),\n\t  KEY `date_time` (`date_time`),\n\t  KEY `to` (`to`),\n\t  KEY `subject` (`subject`),\n\t  KEY `in_queue` (`in_queue`)\n\t) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    dbDelta( $emails_table );
    $stats_table = "CREATE TABLE `" . $wpdb->prefix . MC_EVENT_TABLE . "` (\n\t  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,\n\t  `email_id` bigint(30) unsigned NOT NULL,\n\t  `ip` varchar(20) COLLATE utf8_unicode_ci NOT NULL,\n\t  `user_agent` varchar(255) COLLATE utf8_unicode_ci NOT NULL,\n\t  `when` datetime NOT NULL,\n\t  `event` int(1) NOT NULL,\n\t  `link` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,\n\t  PRIMARY KEY (`id`)\n\t  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    dbDelta( $stats_table );
    if ( $current_version == 0 ) {
        $wpdb->query( "ALTER TABLE " . $wpdb->prefix . MC_EVENT_TABLE . " ADD CONSTRAINT FK_EVENTS_EMAIL_ID FOREIGN KEY (email_id) REFERENCES " . $wpdb->prefix . MC_EMAIL_TABLE . " (id) ON DELETE CASCADE" );
    }
    update_option( "mc_db_version", MC_DB_VERSION );
}

/**
 * Checks Database version and updates it if necessary
 */
function db_check()
{
    $current_version = (int) get_option( 'mc_db_version', 0 );
    if ( $current_version != MC_DB_VERSION ) {
        install_or_upgrade( $current_version );
    }
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\\db_check' );