<?php

if ( !defined( 'ABSPATH' ) || !isset( $_GET['email'] ) ) {
    // phpcs:ignore WordPress.CSRF.NonceVerification
    exit;
}
/**
 * Sanitize IP address
 *
 * @param      string $ip     Ip to sanitize.
 *
 * @return     string  sanitized IP address
 */
function sanitize_ip( $ip )
{
    return filter_var( $ip, FILTER_VALIDATE_IP );
}

/**
 * Tracks the event (Open or Click)
 * - Redirects to the clicked link
 * - sends transparent gif if it is an open event
 *
 * @param      int    $email  The email Id.
 * @param      string $url    The url ( if we have an url, it's a click event, else, it is a read/open ).
 */
function track_email( int $email, string $url = null )
{
    global  $wpdb ;
    $wpdb->insert( $wpdb->prefix . MC_EVENT_TABLE, array(
        'email_id'   => $email,
        'ip'         => ( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_ip( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'localhost' ),
        'user_agent' => ( isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : 'undefined' ),
        'when'       => current_time( 'mysql' ),
        'event'      => ( $url === null ? 0 : 1 ),
        'link'       => $url,
    ), array(
        '%d',
        '%s',
        '%s',
        '%s',
        '%d',
        '%s'
    ) );
    
    if ( $url ) {
        $link = html_entity_decode( $url );
        // Redirect to main link.
        header( "location: {$link}" );
        die;
    } else {
        header( 'Content-Type: image/gif' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );
        // die("\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x90\x00\x00\xff\x00\x00\x00\x00\x00\x21\xf9\x04\x05\x10\x00\x00\x00\x2c\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02\x04\x01\x00\x3b");
        $gif_safe = base64_decode( 'R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==' );
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPressDotOrg.sniffs.OutputEscaping.UnescapedOutputParameter -- Safe it's a transparent gif.
        die( $gif_safe );
    }

}

$email = intval( $_GET['email'] );
// phpcs:ignore WordPress.CSRF.NonceVerification
track_email( $email, ( isset( $_GET['url'] ) ? esc_url_raw( wp_unslash( $_GET['url'] ) ) : null ) );
// phpcs:ignore WordPress.CSRF.NonceVerification