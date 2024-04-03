<?php
/**
 * This file implements background mailer
 * - First we define the settings
 */

namespace Mail_Control;

define( 'MC_MAX_SLEEP_BETWWEN_EMAILS', 15 );

/**
 * Background Mailer settings
 */
add_filter(
	'mail_control_settings',
	function ( $settings ) {
		$settings['BACKGROUND_MAILER'] = array(
			'name'        => 'BACKGROUND_MAILER',
			'title'       => __( 'Background Mailer', 'mail-control' ),
			'description' => __( 'Ensure your pages maintain fast loading times by handling email processes in the background.', 'mail-control' ),
			'side_panel'  => function() { ?>
			<h3><?php esc_html_e( 'How does background mailing works?', 'mail-control' ); ?></h3>
			<p><?php esc_html_e( 'Background mailing acts during the pre_wp_mail phase by placing the emails in a queue and then canceling the sending process. The queue is usually processed by a cron job, typically triggered when the page rendering is done (during the shutdown phase).', 'mail-control' ); ?></p>
			<h3><?php esc_html_e( 'Why should I activate backgound mailing?', 'mail-control' ); ?></h3>
			<p><?php esc_html_e( 'The sending process usually takes time; SMTP servers are efficient in sending emails, but the protocol involves several phases, which makes the operation slow. To avoid having your user wait for the email sending to complete, it is good practice to use a queue for all outgoing emails and ensure the sending process is not user-blocking.', 'mail-control' ); ?></p>
				<?php
			},
			'fields'      => array(
				array(
					'id'    => 'ACTIVE',
					'type'  => 'checkbox',
					'title' => __( 'Send emails in background', 'mail-control' ),
				),
				array(
					'id'         => 'SLEEP_BETWEEN_EMAILS',
					'type'       => 'number',
					'title'      => __( 'Wait time between emails in seconds (in case of SMTP limitations)', 'mail-control' ),
					'desc'       => sprintf( __( "Limiting the sending rate can prevent server overloads. Ideally, this interval shouldn't exceed %1\$d seconds. Therefore, the maximum value allowed here is %2\$d.", 'mail-control' ), MC_MAX_SLEEP_BETWWEN_EMAILS, MC_MAX_SLEEP_BETWWEN_EMAILS ),
					'default'    => 0,
					'attributes' => array( 'max' => MC_MAX_SLEEP_BETWWEN_EMAILS ),
				),
				array(
					'id'      => 'MAX_MAILS_PER_RUN',
					'type'    => 'number',
					'title'   => __( 'Maximum number of emails sent by run', 'mail-control' ),
					'desc'    => __( 'A setting of 0 removes any cap, enabling an ongoing dispatch cycle. To avoid overextended processing times, if the queue count exceeds this setting, an additional dispatch cycle will begin immediately.', 'mail-control' ),
					'default' => 0,
				),
			),
		);
		return $settings;
	}
);


/**
 * Gets the email queue.
 *
 * @return      array  The email queue.
 */


/**
 * Gets the email queue.
 *
 * @param      int $max    The maximum number of emails to retrieve.
 *
 * @return     array  The email queue.
 */
function get_email_queue( int $max = 0 ) {
	global $wpdb;
	$email_table = $wpdb->prefix . MC_EMAIL_TABLE;
	$limit       = $max > 0 ? "LIMIT $max" : '';
	return $wpdb->get_results( "SELECT `id`, `to`, `subject`, `message`, `message_plain`, `headers`, `attachments` FROM `$email_table`  WHERE `in_queue` = 1 order by date_time ASC $limit" );
}

/**
 * Adds to email queue.
 *
 * @param      array $args   The wp_mail arguments.
 *
 * @return     int  Email id.
 */
function add_to_email_queue( array $args ) {
	global $wpdb;

	extract( $args );
	if ( is_string( $headers ) ) {
		// make sure we have an array.
		$headers = explode( "\n", $headers );
	}
	$wpdb->insert(
		$wpdb->prefix . MC_EMAIL_TABLE,
		array(
			'date_time'     => current_time( 'mysql' ),
			'to'            => $to,
			'subject'       => $subject,
			// customizer runs first, and sets text/html if content type is not html. see beautify.
			'message'       => isset( $message['text/html'] ) ? $message['text/html'] : $message,
			// message plain chan be set by customizer when transforming text to html.
			'message_plain' => isset( $message_plain ) ? $message_plain : $message,
			// We save as json.
			'headers'       => is_array( $headers ) ? json_encode( $headers ) : $headers,
			'attachments'   => json_encode( $attachments ),
			'in_queue'      => 1,
		)
	);

	return $wpdb->insert_id;
}

/**
 * Schedules processing the queue
 *
 * @param      int $seconds_after  The seconds after.
 */
function schedule_process_queue( int $seconds_after = 0 ) {
	$time = time();
	if ( $seconds_after > 0 ) {
		$time += $seconds_after;
	}
	wp_schedule_single_event(
		$time,
		'mc_process_email_queue',
		array(
			'time' => $time,  // force scheduling ( caching may prevent adding new cron ).
		)
	);
}



/**
 * Filter to bypass wp_mail call, it :
 * - Adds the mail to the mail queue
 * - Adds a cron event to handle the queue
 * - spawns a cron on shutdown action
 *
 * @param      bool  $return  The return.
 * @param      array $atts    The atts.
 *
 * @return     bool   returns true if the emails is queued, null if not (so it can be sent by wp_mail).
 */
function queue_wp_mail( bool $return = null, array $atts ) {
	// add possibility to bypass queuing emails.
	if ( apply_filters( 'mc_disable_email_queue', false, $atts ) ) {
		return null;
	}
	static $processing_queue_scheduled;
	$queue_id = add_to_email_queue( $atts );
	$queued   = ( $queue_id > 0 ) ? true : null;
	if ( $queued && null === $processing_queue_scheduled ) {
		// schedule the right away.
		schedule_process_queue();
		add_action( 'shutdown', 'spawn_cron' );
		$processing_queue_scheduled = true;
	}

	return $queued;
}

/**
 * Processed the mail queue
 *
 * @param      timestamp $time   The time when the email has been queued.
 */
function process_email_queue( $time = null ) {
	defined( 'MC_PROCESSING_MAIL_QUEUE' ) || define( 'MC_PROCESSING_MAIL_QUEUE', true );
	$max   = (int) BACKGROUND_MAILER_MAX_MAILS_PER_RUN;
	$queue = get_email_queue( $max );

	if ( ! empty( $queue ) ) {
		$count = count( $queue );
		// Just to be sure, let's up time limit.
		if ( $count > 1 && BACKGROUND_MAILER_SLEEP_BETWEEN_EMAILS > 0 ) {
			set_time_limit( BACKGROUND_MAILER_SLEEP_BETWEEN_EMAILS * $count );
		}
		foreach ( $queue as $key => $args ) {
			$headers = json_decode( $args->headers, ARRAY_A );
			if ( ! is_array( $headers ) ) {
				$headers = array();
			}
			$headers[] = "X-Queue-id: {$args->id}";

			$attachments = $args->attachments ? json_decode( $args->attachments, ARRAY_A ) : array();

			// if the message in the queue is already htmlized.
			if ( $args->message !== $args->message_plain ) {
				$message = array(
					'text/plain' => $args->message_plain,
					'text/html'  => $args->message,
				);
				// Ensure header is set as alternative.
				$headers = email_header_set( $headers, 'Content-Type', 'multipart/alternative' );
			} else {
				$message = $args->message;
			}

			wp_mail( $args->to, $args->subject, $message, $headers, $attachments );
			$count--;
			if ( $count > 0 && BACKGROUND_MAILER_SLEEP_BETWEEN_EMAILS > 0 ) {
				// slowly on the smtp server.
				// TODO : Should we be worried about php script timeout? maybe check php setting.
				$sleep = BACKGROUND_MAILER_SLEEP_BETWEEN_EMAILS > 30 ? 30 : BACKGROUND_MAILER_SLEEP_BETWEEN_EMAILS;
				sleep( $sleep );
			}
		}
		// if there is a limit, make sure to schedule an other run.
		if ( $max > 0 ) {
			schedule_process_queue( BACKGROUND_MAILER_SLEEP_BETWEEN_EMAILS );
		}
	}
}

add_action(
	'wp_ajax_process_mail_queue',
	function () {
		if ( defined( 'BACKGROUND_MAILER_ACTIVE' ) && 'on' === BACKGROUND_MAILER_ACTIVE ) {
			check_ajax_referer( 'email-table', 'nonce' );
			process_email_queue();
			die( 'Processed mail queue' );
		} else {
			die( 'Background Mailer is not active' );
		}
	}
);

add_action(
	'settings_ready_mc',
	function () {
		if ( defined( 'BACKGROUND_MAILER_ACTIVE' ) && 'on' === BACKGROUND_MAILER_ACTIVE ) {
			if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
				add_action( 'mc_process_email_queue', __NAMESPACE__ . '\process_email_queue', 10, 1 );
			} else {
				add_filter( 'pre_wp_mail', __NAMESPACE__ . '\queue_wp_mail', 10, 2 );
			}
		}
	}
);
