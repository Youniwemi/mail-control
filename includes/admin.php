<?php

namespace Mail_Control;

require MC_INCLUDES . 'emails-table.php';
define( 'MC_ADMIN_EMAIL_TABLE', 'mail-control' );
/**
 * Setup admin menu
 */
function admin_menu()
{
    add_menu_page(
        __( 'Email logs and tracking', 'mail-control' ),
        'Mail Control',
        MC_PERMISSION_VIEWER,
        MC_ADMIN_EMAIL_TABLE,
        __NAMESPACE__ . '\\show_email_table',
        'data:image/svg+xml;base64,' . base64_encode( file_get_contents( MC_ASSETS_DIR . 'img/icon.svg' ) )
    );
    add_action( 'load-toplevel_page_mail-control', function () {
        add_thickbox();
    } );
}

/**
 * Shows the email table.
 */
function show_email_table()
{
    $emails = new Emails_Table();
    $emails->prepare_items();
    ?>
	<div class="wrap">
		<h1><?php 
    echo  esc_html( get_admin_page_title() ) ;
    ?></h1>

		<form id="emails-table" method="get">        
			<?php 
    $emails->display();
    ?>
			<input type="hidden" name="page" value="<?php 
    echo  esc_attr( MC_ADMIN_EMAIL_TABLE ) ;
    ?>" />
		</form>
	</div>
	<style>
		.metabox-holder{padding-top:1em;}
		#email_content iframe {width: 100%; min-height: 300px;}
		mark.queued , mark.failed, mark.sent {
			display: inline-flex;
			padding:0em 0.9em;
			line-height: 2.2em;
			border:1px solid white;
			background-color: #72aee6;
			color:white;
			border-radius: 4px;
			cursor: inherit !important;
			border: 1px solid rgba(0,0,0,.05)
		}
		mark.queued { background-color : #f0c33c; }
		mark.failed { background-color : #d63638; }
	</style>
	<script>
		// tabs foo thickbox detail
		jQuery( document ).ready( function($) {
			$('body').click( function( evt ) {
				var $target = $(evt.target);
				if ($target.is('.nav-tab-wrapper a')){
					$( '.nav-tab-wrapper a' ).removeClass( 'nav-tab-active' );
					$target.addClass( 'nav-tab-active' ).blur();
					var clicked = $target.attr( 'href' );
					$( '.metabox-holder>div' ).hide();
					$( clicked ).fadeIn();
					evt.preventDefault();
				}                
			});
			// Listen to message from email content to resize the iframe
			window.addEventListener('message', function (e) {
				var data = JSON.parse(e.data);
				if (data.from == 'email_content'){
					$('#email_content iframe').height(data.height);
				}
			});
		});

	</script>
	<?php 
}

/**
 * Sends a json result.
 *
 * @param      mixed $result   The result.
 * @param      bool  $success  succeeded or failed.
 */
function send_json_result( $result, $success = true )
{
    wp_die( json_encode( array(
        'success' => $success,
        'result'  => $result,
    ) ) );
}

/**
 * Resend the email
 */
add_action( 'wp_ajax_resend_email', function () {
    check_ajax_referer( 'email-table', 'nonce' );
    if ( !current_user_can( MC_PERMISSION_VIEWER ) ) {
        wp_die( esc_html__( "You don't have permission to do this" ) );
    }
    if ( empty($_GET['id']) || !is_numeric( $_GET['id'] ) ) {
        wp_die( 'Wrong arguments' );
    }
    $email_id = intval( $_GET['id'] );
    global  $wpdb ;
    $email = $wpdb->get_row( $wpdb->prepare( "SELECT email.* FROM {$wpdb->prefix}" . MC_EMAIL_TABLE . ' as email where  email.id = %d ', $email_id ) );
    // disable queuing email.
    add_filter( 'mc_disable_email_queue', '__return_true' );
    define( 'MC_RESENDING_EMAIL', true );
    add_action( 'wp_mail_failed', function ( $error ) {
        echo  '<p>' . esc_html__( 'Failed to resend the email', 'mail-control' ) . ' : ' . esc_html( $error->getMessage() ) . '</p>' ;
    } );
    $headers = ( $email->headers ? string_header( json_decode( $email->headers, ARRAY_A ) ) : array() );
    
    if ( email_header_has( $headers, 'Content-Type', 'multipart/alternative' ) ) {
        $message = array(
            'text/html'  => $email->message,
            'text/plain' => $email->message_plain,
        );
    } elseif ( email_header_has( $headers, 'Content-Type', 'text/html' ) ) {
        $message = $email->message;
    } else {
        $message = $email->message_plain;
    }
    
    $headers = array_filter( $headers, function ( $line ) {
        list( $header, $value ) = explode( ': ', $line );
        return strtolower( $header ) !== 'to';
    } );
    $sent = wp_mail(
        $email->to,
        $email->subject,
        $message,
        $headers,
        ( $email->attachments ? json_decode( $email->attachments, ARRAY_A ) : array() )
    );
    if ( $sent ) {
        echo  '<p>' . esc_html__( 'Email resent succesfully', 'mail-control' ) . '</p>' ;
    }
    exit;
} );
/**
 * Converts header for array form [$key, $content] to string form "$key: $content"
 *
 * @param      array $headers  The headers.
 *
 * @return     array  headers in string form.
 */
function string_header( $headers )
{
    return array_map( function ( $header ) {
        
        if ( is_array( $header ) ) {
            list( $key, $content ) = $header;
            return "{$key}: {$content}";
        }
        
        return $header;
    }, $headers );
}

/**
 * Gets the email header.
 *
 * @param      array  $headers  The headers.
 * @param      string $header   The header key.
 *
 * @return     string|null  The header value or null if not present
 */
function get_email_header( $headers, $header )
{
    // We may have a simple key => value array.
    foreach ( $headers as $key => $value ) {
        
        if ( is_array( $value ) ) {
            list( $key, $value ) = $value;
            if ( is_array( $value ) ) {
                $value = implode( ', ', array_filter( $value ) );
            }
        }
        
        if ( $key === $header ) {
            return $value;
        }
    }
    return null;
}

/**
 * Detail Email
 */
add_action( 'wp_ajax_detail_email', function () {
    check_ajax_referer( 'email-table', 'nonce' );
    if ( !current_user_can( MC_PERMISSION_VIEWER ) ) {
        wp_die( esc_html__( "You don't have permission to do this", 'aiify' ) );
    }
    if ( empty($_GET['id']) || !is_numeric( $_GET['id'] ) ) {
        wp_die( 'Wrong arguments' );
    }
    $email_id = intval( $_GET['id'] );
    global  $wpdb ;
    $email = $wpdb->get_row( $wpdb->prepare( "SELECT email.* FROM {$wpdb->prefix}" . MC_EMAIL_TABLE . ' as email where  email.id = %d ', $email_id ) );
    $events = $wpdb->get_results( $wpdb->prepare( "SELECT events.* FROM {$wpdb->prefix}" . MC_EVENT_TABLE . ' as events where events.email_id = %d order by `when` ASC', $email_id ) );
    $headers = json_decode( $email->headers, ARRAY_A );
    $attachments = json_decode( $email->attachments, ARRAY_A );
    ?>
	<div class="nav-tab-wrapper">
		<?php 
    
    if ( $email->fail ) {
        ?>
			<a class="nav-tab nav-tab-active" href="#email_errors"><?php 
        esc_html_e( 'Email errors', 'mail-control' );
        ?></a>
		<?php 
    }
    
    ?>
		<a class="nav-tab <?php 
    echo  ( $email->fail ? '' : 'nav-tab-active' ) ;
    ?>" href="#email_content"><?php 
    esc_html_e( 'Email Content', 'mail-control' );
    ?></a>
		<?php 
    
    if ( $headers && count( $headers ) ) {
        ?>
			<a class="nav-tab" href="#email_headers"><?php 
        esc_html_e( 'Headers', 'mail-control' );
        ?></a>
		<?php 
    }
    
    ?>
		<?php 
    
    if ( count( $attachments ) ) {
        ?>
			<a class="nav-tab" href="#email_attachments"><?php 
        esc_html_e( 'Attachements', 'mail-control' );
        ?></a>
		<?php 
    }
    
    ?>
		<?php 
    
    if ( !$email->fail ) {
        ?>
		<a class="nav-tab" href="#email_events"><?php 
        esc_html_e( 'Events', 'mail-control' );
        ?></a>
		<?php 
    }
    
    ?>
	</div>
	<div class="metabox-holder">
		<div id="email_content" class='group' <?php 
    echo  ( $email->fail ? ' style="display:none" ' : '' ) ;
    ?> >
			<h3><?php 
    esc_html_e( 'HTML version', 'mail-control' );
    ?></h3>
			<?php 
    // if we don't have an head tag, let's add one with the charset.
    
    if ( !preg_match( '#<head(.*?)>#is', $email->message ) && ($header = get_email_header( $headers, 'Content-Type' )) ) {
        $content = "<head><meta http-equiv='Content-Type' content='{$header}'></head>" . $email->message;
    } else {
        $content = $email->message;
    }
    
    $content = sanitize_html_email_content( $content ) . "<script>\n   \t\t\t\twindow.onload = function(){ \n   \t\t\t\t\twindow.parent.postMessage(\n   \t\t\t\t\tJSON.stringify({\n   \t\t\t\t\t\tfrom:'email_content',\n   \t\t\t\t\t\theight: document.documentElement.scrollHeight  \n   \t\t\t\t\t}), '*');\n   \t\t\t\t};</script>";
    ?>
			<iframe src="<?php 
    echo  esc_attr( htmlspecialchars( 'data:text/html,' . rawurlencode( $content ) ) ) ;
    ?>" frameborder="0" scrolling="no" ></iframe>
			<h3><?php 
    esc_html_e( 'Plain Text version', 'mail-control' );
    ?></h3>
			<div style="white-space: pre;"><?php 
    echo  wp_kses_post( $email->message_plain ) ;
    ?></div>
		</div>
		<div id="email_headers"  class='group' style="display: none;">
			<h3><?php 
    esc_html_e( 'Headers', 'mail-control' );
    ?></h3>
			<ul>
			<?php 
    foreach ( $headers as $key => $value ) {
        
        if ( is_array( $value ) ) {
            list( $key, $value ) = $value;
            if ( is_array( $value ) ) {
                $value = implode( ', ', array_filter( $value ) );
            }
        }
        
        ?>
				<li><strong><?php 
        echo  esc_html( $key ) ;
        ?></strong> : <?php 
        echo  esc_html( $value ) ;
        ?></li>
			<?php 
    }
    ?>
			</ul>
		</div>
		<?php 
    
    if ( $attachments ) {
        ?>
			<div id="email_attachments"  class='group' style="display: none;">
				<h3><?php 
        esc_html_e( 'Attachements', 'mail-control' );
        ?></h3>

			<?php 
        foreach ( $attachments as $attachment ) {
            $filename = basename( $attachment );
            $filetype = strtolower( pathinfo( $attachment, PATHINFO_EXTENSION ) );
            $mime = mime_content_type( $attachment );
            ?>
				<h4><?php 
            echo  esc_html( $filename ) ;
            ?></h4>
				<?php 
            // view it if an image
            $encoded_file = base64_encode( file_get_contents( $attachment ) );
            
            if ( strpos( $mime, 'image/' ) === 0 ) {
                ?>
					<img style="max-width: 100%;" src="data:<?php 
                echo  esc_attr( $mime ) ;
                ?>;base64,<?php 
                echo  esc_attr( $encoded_file ) ;
                ?>" alt='<?php 
                echo  esc_attr( $filename ) ;
                ?>'/>
					<?php 
            } else {
                // download it
                ?>
					<a href="data:<?php 
                echo  esc_attr( $mime ) ;
                ?>;base64,<?php 
                echo  esc_attr( $encoded_file ) ;
                ?>" download='<?php 
                echo  esc_attr( $filename ) ;
                ?>'><?php 
                echo  esc_html__( 'Download attachment' ) ;
                ?> </a>
				<?php 
            }
            
            ?>

			<?php 
        }
        ?>
			</div>
		<?php 
    }
    
    ?>
		<?php 
    
    if ( $email->fail ) {
        ?>
			<div id="email_errors"  class='group' >
				<h3><?php 
        esc_html_e( 'Email errors', 'mail-control' );
        ?></h3>
				<p><?php 
        echo  esc_html( $email->fail ) ;
        ?></p>
			</div>
		<?php 
    } else {
        ?>
			   <div id="email_events"  class='group'  style="display: none;">
				   <h3><?php 
        esc_html_e( 'Events', 'mail-control' );
        ?></h3>
				<?php 
        
        if ( count( $events ) ) {
            ?>
					<table class="wp-list-table widefat striped table-view-list">
						<thead>
							<tr>
								<th scope="col"><?php 
            esc_html_e( 'Date', 'mail-control' );
            ?></th>
								<th scope="col"><?php 
            esc_html_e( 'Event', 'mail-control' );
            ?></th>
								<th scope="col"><?php 
            esc_html_e( 'URL', 'mail-control' );
            ?></th>
								<th scope="col"><?php 
            esc_html_e( 'IP', 'mail-control' );
            ?></th>
								<th scope="col"><?php 
            esc_html_e( 'User Agent', 'mail-control' );
            ?></th>
							</tr>
						</thead>
						<tbody>
						<?php 
            foreach ( $events as $event ) {
                ?>
							<tr>
								<td><?php 
                echo  esc_html( $event->when ) ;
                ?></td>
								<td>
								<?php 
                
                if ( 0 === (int) $event->event ) {
                    esc_html_e( 'Open', 'mail-control' );
                } else {
                    esc_html_e( 'Click', 'mail-control' );
                }
                
                ?>
								</td>
								<td><?php 
                echo  esc_html( $event->link ) ;
                ?></td>
								<td><?php 
                echo  esc_html( $event->ip ) ;
                ?></td>
								<td><?php 
                echo  esc_html( $event->user_agent ) ;
                ?></td>
							</tr>
						<?php 
            }
            ?>
						</tbody>
					</table>
				<?php 
        } else {
            ?>
						<p><?php 
            esc_html_e( 'Sorry, no events so far', 'mail-control' );
            ?></p>
				<?php 
        }
        
        ?>
				</div>
		<?php 
    }
    
    ?>
	</div>
		<?php 
    exit;
} );
add_action( 'admin_menu', __NAMESPACE__ . '\\admin_menu', 0 );
/**
 * Onboarding notice, presents the use with the next steps to properly setup the plugin.
 */
function onboarding_notice( $is_welcome = false )
{
    global  $pagenow ;
    $class = 'mc_setup';
    $show_dismiss = false;
    
    if ( 'index.php' !== $pagenow ) {
        $class .= ' notice notice-info is-dismissible';
        $show_dismiss = true;
    }
    
    // Setup states.
    $smtp_setup = ( defined( 'SMTP_MAILER_HOST' ) && SMTP_MAILER_HOST ? 'done' : 'to_do' );
    $is_tracking = ( defined( 'EMAIL_TRACKING_ACTIVE_TRACKING' ) && EMAIL_TRACKING_ACTIVE_TRACKING == 'on' ? 'done' : 'to_do' );
    $is_bg = ( defined( 'BACKGROUND_MAILER_ACTIVE' ) && BACKGROUND_MAILER_ACTIVE == 'on' ? 'done' : 'to_do' );
    $is_customized = ( get_option( 'mc_customizer', false ) ? 'done' : 'to_do' );
    $show_configure = !$show_dismiss;
    $all_set = array_unique( array(
        $smtp_setup,
        $is_tracking,
        $is_bg,
        $is_customized
    ) ) === array( 'done' );
    ?>
	<div class="<?php 
    echo  $class ;
    ?>">
		<div>
			<img width="80" style="float:left;margin-right:1em;" src="<?php 
    echo  esc_url( MC_PLUGIN_ASSETS . '/img/icon.svg' ) ;
    ?>" />

			<h3><?php 
    echo  esc_html__( 'Welcome to Mail Control: Let\'s Get Started!', 'mail-control' ) ;
    ?></h3>
			<p><?php 
    echo  esc_html__( 'Set up your essential email tools in just a few easy steps:', 'mail-control' ) ;
    ?></p>
		</div>
		<ol>
			<li class="<?php 
    echo  $smtp_setup ;
    ?>"><?php 
    echo  esc_html__( 'SMTP Settings and Delivrability', 'mail-control' ) ;
    ?> 🛠️<br/>
				<i><?php 
    echo  esc_html__( 'Ensure reliable delivery. Configure SMTP to improve email deliverability. ', 'mail-control' ) ;
    ?></i>
			</li>
			<li class="<?php 
    echo  $is_tracking ;
    ?>"><?php 
    echo  esc_html__( 'Email Logging and Tracking', 'mail-control' ) ;
    ?> 📊<br/>
				<i><?php 
    echo  esc_html__( 'Gain valuable insights. Activate logging and tracking to monitor email performance.', 'mail-control' ) ;
    ?></i>
			</li>
			<li class="<?php 
    echo  $is_bg ;
    ?>"><?php 
    echo  esc_html__( 'Background Email Queue', 'mail-control' ) ;
    ?> 🚀<br/>
				<i><?php 
    echo  esc_html__( 'Enhanced Speed: Keep your pages loading quickly by processing emails in the background. ', 'mail-control' ) ;
    ?></i>
			</li>
			<li class="<?php 
    echo  $is_customized ;
    ?>"><?php 
    echo  esc_html__( 'Customize Templates', 'mail-control' ) ;
    ?> 🎨<br/>
				<i><?php 
    echo  esc_html__( 'Branded Communication: Design email templates that reflect your brand’s unique style. ', 'mail-control' ) ;
    ?></i>
			</li>
		</ol> 
		<?php 
    
    if ( $all_set ) {
        ?> 
		<p><?php 
        echo  esc_html__( 'Congratulations, it looks like you are all set!', 'mail-control' ) ;
        ?></p>
		<?php 
    } else {
        ?> 
		<p><?php 
        echo  esc_html__( 'By completing these steps, you’ll ensure smooth email operations and faster website performance. Let’s get your system ready for prime time.', 'mail-control' ) ;
        ?></p>
		<?php 
    }
    
    ?>        
		<p>
			<?php 
    
    if ( $show_configure ) {
        ?> 

			<a class="button-primary button" href="<?php 
        echo  esc_url( settings_url() ) ;
        ?>"  ><?php 
        echo  esc_html__( 'Configure Mail Control', 'mail-control' ) ;
        ?></a>

			<?php 
    }
    
    ?>

			<a class="button <?php 
    echo  ( $show_configure ? '' : 'button-primary' ) ;
    ?>" href="<?php 
    echo  esc_url( get_customizer_url() ) ;
    ?>"  ><?php 
    echo  esc_html__( 'Customize your emails', 'mail-control' ) ;
    ?></a>

			<a class="button" href="<?php 
    echo  esc_url( mc_fs()->contact_url() ) ;
    ?>" ><?php 
    echo  esc_html__( 'Question? Contact the developper', 'mail-control' ) ;
    ?></a>
		</p>
		<?php 
    
    if ( $show_dismiss ) {
        ?> 
		<button type="button" class="notice-dismiss" onclick="this.parentNode.remove();" ><span class="screen-reader-text"><?php 
        esc_html_e( 'Dismiss this notice.', 'mail-control' );
        ?></span></button>
		<?php 
    }
    
    ?>

	</div>
	<style>.mc_setup li.done{text-decoration: line-through;}</style>
		<?php 
}

/**
 * Show a nice onboarding notice
 */
// add_action( 'admin_notices', __NAMESPACE__ . '\onboarding_notice' );
add_action( 'admin_init', function () {
    global  $pagenow ;
    if ( 'index.php' !== $pagenow ) {
        return;
    }
    $smtp_setup = ( defined( 'SMTP_MAILER_HOST' ) && SMTP_MAILER_HOST ? 'done' : 'to_do' );
    $is_tracking = ( defined( 'EMAIL_TRACKING_ACTIVE_TRACKING' ) && EMAIL_TRACKING_ACTIVE_TRACKING == 'on' ? 'done' : 'to_do' );
    $is_bg = ( defined( 'BACKGROUND_MAILER_ACTIVE' ) && BACKGROUND_MAILER_ACTIVE == 'on' ? 'done' : 'to_do' );
    $is_customized = ( get_option( 'mc_customizer', false ) ? 'done' : 'to_do' );
    $all_set = array_unique( array(
        $smtp_setup,
        $is_tracking,
        $is_bg,
        $is_customized
    ) ) === array( 'done' );
    !$all_set && add_meta_box(
        'mc_dashboard_setup',
        __( 'Mail Control Setup', 'mail-control' ),
        __NAMESPACE__ . '\\onboarding_notice',
        'dashboard',
        'normal',
        'high'
    );
} );
/**
 * Adding link to documentation in the plugin row
 */
add_filter(
    'plugin_row_meta',
    function ( $links, $file ) {
    if ( MC_PLUGIN_BASENAME !== $file ) {
        return $links;
    }
    $row_meta = array(
        'docs' => '<a href="https://www.wpmailcontrol.com/docs/" aria-label="' . esc_attr__( 'View Mail Control Documentation', 'mail-control' ) . '">' . esc_html__( 'Mail Control Docs', 'mail-control' ) . '</a>',
    );
    return array_merge( $links, $row_meta );
},
    10,
    2
);
/**
 * Adding actions links
 */
add_filter(
    'plugin_action_links',
    function ( $links, $file ) {
    if ( MC_PLUGIN_BASENAME !== $file ) {
        return $links;
    }
    $settings = sprintf( '<a href="%1$s" >%2$s</a>', esc_url( settings_url() ), esc_html__( 'Configure Mail Control', 'mail-control' ) );
    $customizer = sprintf( '<a href="%1$s" >%2$s</a>', esc_url( get_customizer_url() ), esc_html__( 'Customize your emails', 'mail-control' ) );
    array_unshift( $links, $settings );
    array_unshift( $links, $customizer );
    return array_merge( $links );
},
    10,
    2
);