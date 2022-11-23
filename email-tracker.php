<?php

namespace Mail_Control;

use  PHPMailer\PHPMailer\PHPMailer ;
add_filter( 'mail_control_settings', function ( $settings ) {
    $tracking_config = [
        'name'   => 'EMAIL_TRACKING',
        'title'  => __( 'Email Logging and Tracking', 'mail-control' ),
        'fields' => [ [
        'id'      => 'ACTIVE_LOGGING',
        'type'    => 'checkbox',
        'title'   => __( 'Log Emails (Mandatory if we want to track emails)', 'mail-control' ),
        'default' => true,
    ], [
        'id'      => 'ACTIVE_TRACKING',
        'type'    => 'checkbox',
        'title'   => __( 'Enable opens and clicks tracking', 'mail-control' ),
        'default' => true,
        'show_if' => function () {
        return defined( 'EMAIL_TRACKING_ACTIVE_LOGGING' ) && EMAIL_TRACKING_ACTIVE_LOGGING == 'on';
    },
    ] ],
    ];
    $settings['EMAIL_TRACKING'] = $tracking_config;
    return $settings;
} );
/**
 * Update the mail status in the queue
 *
 * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer The phpmailer
 *
 * @return int                          EMail's id
 */
function update_email( PHPMailer $phpmailer )
{
    global  $wpdb ;
    // look for queue id
    $headers = $phpmailer->getCustomHeaders();
    $update = null;
    foreach ( $headers as [ $key, $id ] ) {
        
        if ( $key == 'X-Queue-id' ) {
            $update = $id;
            break;
        }
    
    }
    if ( $update ) {
        $wpdb->update( $wpdb->prefix . MC_EMAIL_TABLE, [
            'date_time'     => current_time( 'mysql' ),
            'message'       => $phpmailer->Body,
            'message_plain' => ( $phpmailer->AltBody ? $phpmailer->AltBody : $phpmailer->html2text( $phpmailer->Body ) ),
            'headers'       => json_encode( $headers ),
            'attachments'   => json_encode( array_map( function ( $a ) {
            return $a[1];
        }, $phpmailer->getAttachments() ) ),
            'in_queue'      => 0,
        ], [
            'id' => $update,
        ] );
    }
    return $update;
}

/**
 * Insert the email in the Email Table ( email didn't come from the queue )
 *
 * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer The phpmailer
 *
 * @return int EMail's id
 */
function insert_email( PHPMailer $phpmailer )
{
    global  $wpdb ;
    $wpdb->insert( $wpdb->prefix . MC_EMAIL_TABLE, [
        'date_time'     => current_time( 'mysql' ),
        'to'            => $phpmailer->getToAddresses()[0][0],
        'subject'       => $phpmailer->Subject,
        'message'       => $phpmailer->Body,
        'message_plain' => ( $phpmailer->AltBody ? $phpmailer->AltBody : $phpmailer->html2text( $phpmailer->Body ) ),
        'headers'       => json_encode( $phpmailer->getCustomHeaders() ),
        'attachments'   => json_encode( array_map( function ( $a ) {
        return $a[1];
    }, $phpmailer->getAttachments() ) ),
    ] );
    return $wpdb->insert_id;
}

/**
 * Gets the tracking URL
 *
 * @param int $email_id The email identifier
 *
 * @return string  the tracking url for the email
 */
function tracker_url( int $email_id )
{
    return add_query_arg( 'email', $email_id, home_url() . MC_TRACK_URL );
}

/**
 * Returns the tracking link for a url
 *
 * @param string $url      The url
 * @param string $tracking The tracking
 *
 * @return string  The tracking link
 */
function track_link( string $url, string $tracking )
{
    // nothing to track here
    if ( substr( $url, 0, 1 ) == '#' ) {
        return $url;
    }
    return add_query_arg( 'url', urlencode( $url ), $tracking );
}

/**
 * Inserts tracking img and replaces links with tracking links
 *
 * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer   The phpmailer
 * @param string                         $tracker_url The tracker url
 *
 * @return string                          The new mail body
 */
function track_email( PHPMailer $phpmailer, string $tracker_url )
{
    // track clicks
    $content = preg_replace_callback( '/<a(.*?)href="(.*?)"/', function ( $matches ) use( $tracker_url, $campaign, $source ) {
        return '<a' . $matches[1] . 'href="' . track_link(
            $matches[2],
            $tracker_url,
            $campaign,
            $source
        ) . '"';
    }, $phpmailer->Body );
    // track read
    $content .= "<img src='{$tracker_url}' alt='pixel' />";
    return $content;
}

// Update headers to table.
add_action(
    'wp_mail_succeeded',
    function ( $mail_data ) {
    
    if ( EMAIL_TRACKING_ACTIVE_LOGGING == 'on' || EMAIL_TRACKING_ACTIVE_TRACKING == 'on' ) {
        global  $wpdb ;
        $headers = $mail_data['headers'];
        $id = ( isset( $headers['X-Queue-id'] ) ? (int) $headers['X-Queue-id'] : $wpdb->insert_id );
        $wpdb->update( $wpdb->prefix . MC_EMAIL_TABLE, [
            'headers' => json_encode( $headers ),
        ], [
            'id' => $id,
        ] );
    }

},
    100,
    1
);
// Update fail message.
add_action(
    'wp_mail_failed',
    function ( $error ) {
    
    if ( EMAIL_TRACKING_ACTIVE_LOGGING == 'on' || EMAIL_TRACKING_ACTIVE_TRACKING == 'on' ) {
        global  $wpdb ;
        $headers = $error->error_data['wp_mail_failed']["headers"];
        $id = ( isset( $headers['X-Queue-id'] ) ? (int) $headers['X-Queue-id'] : $wpdb->insert_id );
        $wpdb->update( $wpdb->prefix . MC_EMAIL_TABLE, [
            'fail'    => $error->get_error_messages()[0],
            'headers' => json_encode( $headers ),
        ], [
            'id' => $id,
        ] );
    }

},
    100,
    1
);
// Include tracking to email just before sendind.
add_action(
    'phpmailer_init',
    function ( $phpmailer ) {
    if ( EMAIL_TRACKING_ACTIVE_TRACKING == 'on' ) {
        // if not html, convert to html
        
        if ( $phpmailer->ContentType == PHPMailer::CONTENT_TYPE_PLAINTEXT ) {
            $phpmailer->AltBody = $phpmailer->Body;
            $phpmailer->Body = nl2br( make_clickable( $phpmailer->Body ) );
            $phpmailer->isHTML( true );
        }
    
    }
    
    if ( EMAIL_TRACKING_ACTIVE_LOGGING == 'on' || EMAIL_TRACKING_ACTIVE_TRACKING == 'on' ) {
        // insert email in log or remove from queue
        $email_id = null;
        if ( BACKGROUND_MAILER_ACTIVE == 'on' ) {
            $email_id = update_email( $phpmailer );
        }
        // maybe the mail is not found
        if ( $email_id === null ) {
            $email_id = insert_email( $phpmailer );
        }
    }
    
    
    if ( EMAIL_TRACKING_ACTIVE_TRACKING == 'on' ) {
        // tracking code
        $tracker = tracker_url( $email_id );
        $phpmailer->Body = track_email( $phpmailer, $tracker );
    }

},
    100,
    1
);