<?php

namespace Mail_Control;

define('MC_DB_VERSION', 1);

/**
 * Installs or upgrades the database tables.
 */
function install_or_upgrade($current_version)
{
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');


    $emails_table = "CREATE TABLE `".$wpdb->prefix.MC_EMAIL_TABLE. "` (
	  `id` bigint(30) unsigned NOT NULL AUTO_INCREMENT,
	  `date_time` datetime NOT NULL,
	  `to` varchar(255)  NOT NULL DEFAULT '',
	  `subject` varchar(255)  NOT NULL DEFAULT '',
	  `message` text  DEFAULT NULL,
	  `message_plain` text  DEFAULT NULL,
	  `headers` longtext  DEFAULT NULL,
	  `attachments` longtext  DEFAULT NULL,
	  `fail` text  DEFAULT NULL,
	  `in_queue` tinyint(1) DEFAULT NULL,
	  PRIMARY KEY (`id`),
	  KEY `date_time` (`date_time`),
	  KEY `to` (`to`),
	  KEY `subject` (`subject`),
	  KEY `in_queue` (`in_queue`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    dbDelta($emails_table);

    $stats_table = "CREATE TABLE `".$wpdb->prefix.MC_EVENT_TABLE. "` (
	  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	  `email_id` bigint(30) unsigned NOT NULL,
	  `ip` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
	  `user_agent` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
	  `when` datetime NOT NULL,
	  `event` int(1) NOT NULL,
	  `link` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
	  PRIMARY KEY (`id`)
	  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    dbDelta($stats_table);
    if ($current_version == 0) {
        $wpdb->query("ALTER TABLE ".$wpdb->prefix.MC_EVENT_TABLE. " ADD CONSTRAINT FK_EVENTS_EMAIL_ID FOREIGN KEY (email_id) REFERENCES ".$wpdb->prefix.MC_EMAIL_TABLE. " (id) ON DELETE CASCADE");
    }


    update_option("mc_db_version", MC_DB_VERSION);
}

function db_check()
{
    $current_version = (int) get_option('mc_db_version', 0);
    if ($current_version != MC_DB_VERSION) {
        install_or_upgrade($current_version);
    }
}

add_action('plugins_loaded', 'Mail_Control\\db_check');
