<?php

namespace Mail_Control;

/**
 * Background Mailer settings
 */
add_filter('mail_control_settings', function ($settings) {
    $settings['BACKGROUND_MAILER'] = [
        'name' => 'BACKGROUND_MAILER',
        'title' => __('Background Mailer', 'mail-control'),
        'fields' => [
            [
                'id' => 'ACTIVE',
                'type' => 'checkbox',
                'title' => __('Send emails in background', 'mail-control') ,
            ],
            [
                'id' => 'SLEEP_BETWEEN_EMAILS',
                'type' => 'number',
                'title' => __('Sleep between emails in seconds ( in case of SMTP limitations)', 'mail-control') ,
                'default' => 0
            ]
        ]
    ];
    return $settings;
});


/**
 * Gets the email queue.
 *
 * @return      array  The email queue.
 */
function get_email_queue()
{
    global $wpdb;
    $email_table = $wpdb->prefix . MC_EMAIL_TABLE ;
    return $wpdb->get_results("SELECT `id`, `to`, `subject`, `message`, `message_plain`, `headers`, `attachments` FROM `$email_table`  WHERE `in_queue` = 1 order by date_time ASC");
}

/**
 * Adds to email queue.
 *
 * @param      array   $args   The wp_mail arguments
 *
 * @return     int  Email id
 */
function add_to_email_queue(array $args)
{
    global $wpdb;

    extract($args);
    if (is_string($headers)) {
        // make sure we have an array
        $headers = explode("\n", $headers);
    }
    $wpdb->insert(
        $wpdb->prefix.MC_EMAIL_TABLE,
        [
            'date_time'=> current_time('mysql'),
            'to'=> $to ,
            'subject' => $subject ,
            // customizer runs first, and sets text/html if content type is not html. see beautify
            'message' => isset($message['text/html']) ? $message['text/html'] : $message,
            // message plain chan be set by customizer when transforming text to html
            'message_plain' => isset($message_plain) ? $message_plain : $message ,
            // We save as json
            'headers' => is_array($headers) ? json_encode($headers) : $headers ,
            'attachments' => json_encode($attachments),
            'in_queue' => 1
        ]
    );


    return $wpdb->insert_id;
}

/**
 * Filter to bypass wp_mail call, it :
 * - Adds the mail to the mail queue
 * - Adds a cron event to handle the queue
 * - spawns a cron on shutdown action
 *
 * @param      bool   $return  The return
 * @param      array  $atts    The atts
 *
 * @return     bool   returns true if the emails is queued, null if not (so it can be sent by wp_mail)
 */
function queue_wp_mail(bool $return=null, array $atts)
{
    // add possibility to court-circuit queuing emails
    if (apply_filters('mc_disable_email_queue', false, $atts)) {
        return null;
    }
    static $added;
    $queue_id = add_to_email_queue($atts);
    $queued = ($queue_id > 0) ? true : null ;
    if ($queued && $added === null) {
        // schedule for right away
        $time = time();
        wp_schedule_single_event($time, 'mc_process_email_queue', [
            'time' =>  $time  // force scheduling ( caching may prevent adding new cron )
        ]);
        add_action('shutdown', 'spawn_cron');
        $added = true;
    }

    return $queued ;
}

/**
 * Processed the mail queue
 *
 * @param      timestamp  $time   The time when the email has been queued
 */
function process_email_queue($time = null)
{
    defined('MC_PROCESSING_MAIL_QUEUE') || define('MC_PROCESSING_MAIL_QUEUE', true);
    $queue = get_email_queue();

    if (! empty($queue)) {
        $count = count($queue);
        foreach ($queue as $key => $args) {
            $headers = json_decode($args->headers, ARRAY_A);
            if (!is_array($headers)) {
                $headers=[];
            }
            $headers[] = "X-Queue-id: {$args->id}";

            $attachments = $args->attachments ? json_decode($args->attachments, ARRAY_A) : [];

            // if the message in the queue is already htmlized
            if ($args->message != $args->message_plain) {
                $message = [
                    'text/plain' => $args->message_plain,
                    'text/html' => $args->message
                ];
                // Ensure header is set as alternative
                $headers = email_header_set($headers, 'Content-Type', 'multipart/alternative');
            } else {
                $message = $args->message;
            }

            wp_mail($args->to, $args->subject, $message, $headers, $attachments);
            $count--;
            if ($count > 0 && BACKGROUND_MAILER_SLEEP_BETWEEN_EMAILS>0) {
                // slowly on the smtp server
                // TODO : Should we be worried about php script timeout? maybe check php setting?
                sleep(BACKGROUND_MAILER_SLEEP_BETWEEN_EMAILS);
            }
        }
    }
}

add_action('wp_ajax_process_mail_queue', function () {
    if (defined('BACKGROUND_MAILER_ACTIVE') && BACKGROUND_MAILER_ACTIVE == 'on') {
        process_email_queue();
        die('Processed mail queue');
    } else {
        die('Background Mailer is not active');
    }
});

add_action('settings_ready_mc', function () {
    if (defined('BACKGROUND_MAILER_ACTIVE') && BACKGROUND_MAILER_ACTIVE == 'on') {
        if (defined('DOING_CRON') && DOING_CRON==1) {
            add_action('mc_process_email_queue', 'Mail_Control\\process_email_queue', 10, 1);
        } else {
            add_filter('pre_wp_mail', 'Mail_Control\\queue_wp_mail', 10, 2);
        }
    }
});
