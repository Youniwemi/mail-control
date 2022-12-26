<?php

namespace Mail_Control;

use  PHPMailer\PHPMailer\PHPMailer ;
/**
 * Email tracker settings
 */
add_filter( 'mail_control_settings', function ( $settings ) {
    $tracking_config = [
        'name'   => 'EMAIL_TRACKING',
        'title'  => __( 'Email Logging and Tracking', 'mail-control' ),
        'fields' => [ [
        'id'      => 'ACTIVE_LOGGING',
        'type'    => 'checkbox',
        'title'   => __( 'Log Emails (Mandatory if we want to track emails)', 'mail-control' ),
        'default' => 'on',
    ], [
        'id'      => 'ACTIVE_TRACKING',
        'type'    => 'checkbox',
        'title'   => __( 'Enable opens and clicks tracking', 'mail-control' ),
        'default' => 'off',
        'show_if' => function () {
        return EMAIL_TRACKING_ACTIVE_LOGGING == 'on';
    },
    ] ],
    ];
    $settings['EMAIL_TRACKING'] = $tracking_config;
    return $settings;
} );
/**
 * Converts plain text email content to a simple html version (with clickable links and <br> tags ) 
 *
 * @param      string  $content  The plain text content
 *
 * @return     string  The html version
 */
function htmlize( $content )
{
    return nl2br( make_clickable( $content ) );
}

/**
 * Determines if email headers has a certain header.
 *
 * @param      array   $headers  The headers
 * @param      string  $key      The key
 * @param      string  $value    The value (optionnal)
 *
 * @return     bool    True if email header has, false otherwise.
 */
function email_header_has( array $headers, $key, $value = null )
{
    if ( count( $headers ) ) {
        foreach ( $headers as $header ) {
            
            if ( $header ) {
                [ $h, $v ] = array_map( 'trim', explode( ':', $header ) );
                
                if ( strtolower( $h ) === strtolower( $key ) ) {
                    if ( $value === null ) {
                        return true;
                    }
                    // Content-type is special, Content-Type: text/html; charset=...
                    // in this case, we compare with the first part
                    $v = explode( ";", $v );
                    return $value === $v[0];
                }
            
            }
        
        }
    }
    return false;
}

/**
 * Update the mail status in the queue
 *
 * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer The phpmailer
 *
 * @return int                          EMail's id
 */
function update_email( PHPMailer $phpmailer )
{
    // look for queue id
    $headers = get_all_headers( $phpmailer );
    $update = null;
    foreach ( $headers as [ $key, $id ] ) {
        
        if ( $key == 'X-Queue-id' ) {
            $update = $id;
            break;
        }
    
    }
    
    if ( $update ) {
        global  $wpdb ;
        $wpdb->update( $wpdb->prefix . MC_EMAIL_TABLE, [
            'date_time'     => current_time( 'mysql' ),
            'message'       => $phpmailer->Body,
            'message_plain' => ( $phpmailer->AltBody ? $phpmailer->AltBody : $phpmailer->html2text( $phpmailer->Body ) ),
            'headers'       => json_encode( $headers ),
            'attachments'   => json_encode( array_map( function ( $a ) {
            return $a[0];
        }, $phpmailer->getAttachments() ) ),
            'in_queue'      => 0,
        ], [
            'id' => $update,
        ] );
    }
    
    return $update;
}

/**
 * Gets all headers (completes PhpMailer getCustomHeaders).
 *
 * @param      \PHPMailer\PHPMailer\PHPMailer  $phpmailer  The phpmailer
 *
 * @return     \PHPMailer\PHPMailer\PHPMailer  All headers.
 */
function get_all_headers( PHPMailer $phpmailer )
{
    $headers = $phpmailer->getCustomHeaders();
    foreach ( [
        'To'       => 'getToAddresses',
        'Cc'       => 'getCcAddresses',
        'Bcc'      => 'getBccAddresses',
        'Reply-to' => 'getReplyToAddresses',
    ] as $header => $getter ) {
        
        if ( $emails = $phpmailer->{$getter}() ) {
            $recipients = [];
            foreach ( $emails as $email ) {
                [ $address, $name ] = $email;
                
                if ( $name ) {
                    $recipients[] = "{$name} <{$address}>";
                } else {
                    $recipients[] = $address;
                }
            
            }
            if ( $recipients ) {
                $headers[] = [ $header, implode( ', ', $recipients ) ];
            }
        }
    
    }
    // Add content type
    $content_type = $phpmailer->ContentType;
    if ( $phpmailer->CharSet ) {
        $content_type .= "; charset=" . $phpmailer->CharSet;
    }
    $headers[] = [ 'Content-Type', $content_type ];
    return $headers;
}

/**
 * Gets all the email "to" recipients, comma separated
 *
 * @param      \PHPMailer\PHPMailer\PHPMailer  $phpmailer  The phpmailer
 *
 * @return     string  Comma separated list of recipients.
 */
function get_email_recipients( PHPMailer $phpmailer )
{
    $recipients = array_map( function ( $recipient ) {
        return $recipient[0];
    }, $phpmailer->getToAddresses() );
    return implode( ',', $recipients );
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
        'to'            => get_email_recipients( $phpmailer ),
        'subject'       => $phpmailer->Subject,
        'message'       => $phpmailer->Body,
        'message_plain' => ( $phpmailer->AltBody ? $phpmailer->AltBody : $phpmailer->html2text( $phpmailer->Body ) ),
        'headers'       => json_encode( get_all_headers( $phpmailer ) ),
        'attachments'   => json_encode( array_map( function ( $a ) {
        return $a[0];
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
    $content = preg_replace_callback( '/<a(.*?)href="(.*?)"/', function ( $matches ) use( $tracker_url ) {
        return '<a' . $matches[1] . 'href="' . esc_url( track_link( $matches[2], $tracker_url ) ) . '"';
    }, $phpmailer->Body );
    // track read
    $content .= '<img src="' . esc_url( $tracker_url ) . '" alt="" />';
    return $content;
}

// Update fail message.
add_action(
    'wp_mail_failed',
    function ( $error ) {
    
    if ( EMAIL_TRACKING_ACTIVE_LOGGING == 'on' || EMAIL_TRACKING_ACTIVE_TRACKING == 'on' ) {
        global  $wpdb ;
        $headers = $error->error_data['wp_mail_failed']["headers"];
        $id = ( isset( $headers['X-Queue-id'] ) ? (int) $headers['X-Queue-id'] : $wpdb->insert_id );
        $wpdb->update( $wpdb->prefix . MC_EMAIL_TABLE, [
            'fail' => $error->get_error_messages()[0],
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
    // if processed by the customizer, Body Would be an array
    // if $message is array as well ( resend )
    
    if ( is_array( $phpmailer->Body ) ) {
        $phpmailer->AltBody = $phpmailer->Body['text/plain'];
        $phpmailer->Body = $phpmailer->Body['text/html'];
    }
    
    if ( defined( 'EMAIL_TRACKING_ACTIVE_TRACKING' ) && EMAIL_TRACKING_ACTIVE_TRACKING == 'on' ) {
        // if not html, convert to html =
        
        if ( $phpmailer->ContentType == PHPMailer::CONTENT_TYPE_PLAINTEXT ) {
            $phpmailer->AltBody = $phpmailer->Body;
            $phpmailer->Body = htmlize( $phpmailer->Body );
            $phpmailer->isHTML( true );
        }
    
    }
    
    if ( defined( 'EMAIL_TRACKING_ACTIVE_LOGGING' ) && EMAIL_TRACKING_ACTIVE_LOGGING == 'on' || defined( 'EMAIL_TRACKING_ACTIVE_TRACKING' ) && EMAIL_TRACKING_ACTIVE_TRACKING == 'on' ) {
        // insert email in log or remove from queue
        $email_id = null;
        if ( defined( 'BACKGROUND_MAILER_ACTIVE' ) && BACKGROUND_MAILER_ACTIVE == 'on' ) {
            $email_id = update_email( $phpmailer );
        }
        // maybe the mail is not found
        if ( $email_id === null ) {
            $email_id = insert_email( $phpmailer );
        }
    }
    
    
    if ( defined( 'EMAIL_TRACKING_ACTIVE_TRACKING' ) && EMAIL_TRACKING_ACTIVE_TRACKING == 'on' ) {
        // tracking code
        $tracker = tracker_url( $email_id );
        $phpmailer->Body = track_email( $phpmailer, $tracker );
    }

},
    100,
    1
);