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
        'Mail Control',
        __( 'Mail Control', 'mail-control' ),
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
    esc_attr_e( MC_ADMIN_EMAIL_TABLE );
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
 * @param      mixed  $result   The result
 * @param      bool    $success  succeeded or failed
 */
function send_json_result( $result, $success = true )
{
    wp_die( json_encode( [
        'success' => $success,
        'result'  => $result,
    ] ) );
}

/**
 * Resend the email
 */
add_action( 'wp_ajax_resend_email', function () {
    check_ajax_referer( 'email-table', 'nonce' );
    if ( !current_user_can( MC_PERMISSION_VIEWER ) ) {
        wp_die( esc_html__( "You don't have permission to do this" ) );
    }
    if ( empty($_GET["id"]) || !is_numeric( $_GET["id"] ) ) {
        wp_die( 'Wrong arguments' );
    }
    $email_id = intval( $_GET["id"] );
    global  $wpdb ;
    $email = $wpdb->get_row( $wpdb->prepare( "SELECT email.* FROM {$wpdb->prefix}" . MC_EMAIL_TABLE . " as email where  email.id = %d ", $email_id ) );
    // disable queuing email
    add_filter( 'mc_disable_email_queue', '__return_true' );
    define( 'MC_RESENDING_EMAIL', true );
    add_action( 'wp_mail_failed', function ( $error ) {
        echo  '<p>' . esc_html__( 'Failed to resend the email', 'mail-control' ) . ' : ' . esc_html( $error->getMessage() ) . '</p>' ;
    } );
    $headers = ( $email->headers ? string_header( json_decode( $email->headers, ARRAY_A ) ) : [] );
    
    if ( email_header_has( $headers, 'Content-Type', 'multipart/alternative' ) ) {
        $message = [
            'text/html'  => $email->message,
            'text/plain' => $email->message_plain,
        ];
    } elseif ( email_header_has( $headers, 'Content-Type', 'text/html' ) ) {
        $message = $email->message;
    } else {
        $message = $email->message_plain;
    }
    
    $headers = array_filter( $headers, function ( $line ) {
        [ $header, $value ] = explode( ': ', $line );
        return strtolower( $header ) != 'to';
    } );
    $sent = wp_mail(
        $email->to,
        $email->subject,
        $message,
        $headers,
        ( $email->attachments ? json_decode( $email->attachments, ARRAY_A ) : [] )
    );
    if ( $sent ) {
        echo  '<p>' . esc_html__( 'Email resent succesfully', 'mail-control' ) . '</p>' ;
    }
    exit;
} );
/**
 * Converts header for array form [$key, $content] to string form "$key: $content"
 *
 * @param      array  $headers  The headers
 *
 * @return     array  headers in string form
 */
function string_header( $headers )
{
    return array_map( function ( $header ) {
        
        if ( is_array( $header ) ) {
            [ $key, $content ] = $header;
            return "{$key}: {$content}";
        }
        
        return $header;
    }, $headers );
}

/**
 * Gets the email header.
 *
 * @param      array  $headers  The headers
 * @param      string  $header   The header key
 *
 * @return     string|null  The header value or null if not present
 */
function get_email_header( $headers, $header )
{
    // We may have a simple key => value array
    foreach ( $headers as $key => $value ) {
        
        if ( is_array( $value ) ) {
            [ $key, $value ] = $value;
            if ( is_array( $value ) ) {
                $value = implode( ', ', array_filter( $value ) );
            }
        }
        
        if ( $key == $header ) {
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
        wp_die( esc_html__( "You don't have permission to do this" ) );
    }
    if ( empty($_GET["id"]) || !is_numeric( $_GET["id"] ) ) {
        wp_die( 'Wrong arguments' );
    }
    $email_id = intval( $_GET["id"] );
    global  $wpdb ;
    $email = $wpdb->get_row( $wpdb->prepare( "SELECT email.* FROM {$wpdb->prefix}" . MC_EMAIL_TABLE . " as email where  email.id = %d ", $email_id ) );
    $events = $wpdb->get_results( $wpdb->prepare( "SELECT events.* FROM {$wpdb->prefix}" . MC_EVENT_TABLE . " as events where events.email_id = %d order by `when` ASC", $email_id ) );
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
    // if we don't have an head tag, let's add one with the charset
    
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
            [ $key, $value ] = $value;
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
	   						<td><?php 
                
                if ( $event->event == 0 ) {
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
            esc_html_e( "Sorry, no events so far", 'mail-control' );
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
add_action( 'admin_menu', 'Mail_Control\\admin_menu', 0 );