<?php

namespace Mail_Control;

require __DIR__ . '/emails-table.php';
function admin_menu()
{
    add_menu_page(
        'Mail Control',
        __( 'Mail Control', 'mail-control' ),
        MC_MANAGER_PERMISSION,
        'mail-control',
        'Mail_Control\\show_email_table',
        "dashicons-email"
    );
    add_action( 'load-toplevel_page_mail-control', function () {
        add_thickbox();
    } );
}

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
	        <input type="hidden" name="page" value="<?php 
    echo  esc_attr( $_REQUEST['page'] ) ;
    ?>" />
	        <?php 
    $emails->display();
    ?>
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
 * Detail Email
 */
add_action( 'wp_ajax_detail_email', function () {
    if ( !current_user_can( MC_MANAGER_PERMISSION ) ) {
        wp_die( __( "You don't have permission to do this" ) );
    }
    $email = $_GET["id"];
    if ( empty($email) || !is_numeric( $email ) ) {
        wp_die( 'Wrong arguments' );
    }
    $email_id = intval( $email );
    global  $wpdb ;
    $email = $wpdb->get_row( $wpdb->prepare( "SELECT email.* FROM {$wpdb->prefix}" . MC_EMAIL_TABLE . " as email where  email.id = %d ", intval( $email ) ) );
    $events = $wpdb->get_results( $wpdb->prepare( "SELECT events.* FROM {$wpdb->prefix}" . MC_EVENT_TABLE . " as events where events.email_id = %d order by `when` ASC", $email_id ) );
    $headers = json_decode( $email->headers, ARRAY_A );
    $content_style = ( $email->fail ? ' style="display:none" ' : '' );
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
    
    if ( count( $headers ) ) {
        ?>
    		<a class="nav-tab" href="#email_headers"><?php 
        esc_html_e( 'Headers', 'mail-control' );
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
    echo  $content_style ;
    ?> >
   			<h3><?php 
    esc_html_e( 'HTML version', 'mail-control' );
    ?></h3>
   			<?php 
    $content = preg_replace( '#<script(.*?)>(.*?)</script>#is', '', $email->message ) . "<script>\n   \t\t\t\twindow.onload = function(){ \n   \t\t\t\t\twindow.parent.postMessage(\n   \t\t\t\t\tJSON.stringify({\n   \t\t\t\t\t\tfrom:'email_content',\n   \t\t\t\t\t\theight: document.documentElement.scrollHeight  \n   \t\t\t\t\t}), '*');\n   \t\t\t\t};</script>";
    ?>
   			<iframe src="<?php 
    echo  htmlspecialchars( 'data:text/html,' . rawurlencode( $content ) ) ;
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
            if ( $email->in_queue == 1 ) {
                $headers = Emails_Table::normalize_headers( $headers );
            }
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
                echo  $event->when ;
                ?></td>
	   						<td><?php 
                echo  ( $event->event == 0 ? __( 'Open', 'mail-control' ) : __( 'Click', 'mail-control' ) ) ;
                ?></td>
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