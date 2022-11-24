<?php

namespace Mail_Control;

use  PHPMailer\PHPMailer\PHPMailer ;
add_filter( 'mail_control_settings', function ( $settings ) {
    $settings['SMTP_MAILER'] = [
        'name'   => 'SMTP_MAILER',
        'title'  => __( 'SMTP Mailer', 'mail-control' ),
        'fields' => [
        [
        'id'          => 'HOST',
        'type'        => 'text',
        'title'       => __( 'Smtp Host', 'mail-control' ),
        'description' => __( 'Your smtp mail server hostname (or IP)', 'mail-control' ),
    ],
        [
        'id'          => 'PORT',
        'type'        => 'number',
        'title'       => __( 'Smtp PORT', 'mail-control' ),
        'description' => __( 'You smtp mail server PORT', 'mail-control' ),
    ],
        [
        'id'          => 'SSL',
        'type'        => 'radio',
        'options'     => [
        ''    => 'None',
        'ssl' => 'SSL',
        'tls' => 'TLS',
    ],
        'title'       => __( 'Smtp Encryption', 'mail-control' ),
        'description' => __( 'What type of encryption your server is using', 'mail-control' ),
    ],
        [
        'id'          => 'USER',
        'type'        => 'text',
        'title'       => __( 'Smtp User', 'mail-control' ),
        'description' => __( 'You smtp account\'s username', 'mail-control' ),
    ],
        [
        'id'          => 'PASSWORD',
        'type'        => 'text',
        'title'       => __( 'Smtp Password', 'mail-control' ),
        'description' => __( 'You smtp account\'s password', 'mail-control' ),
    ],
        [
        'id'          => 'FROM_EMAIL',
        'type'        => 'email',
        'title'       => __( 'From Email', 'mail-control' ),
        'description' => __( 'Your emails will be sent from this email adress', 'mail-control' ),
    ],
        [
        'id'          => 'FROM_NAME',
        'type'        => 'text',
        'title'       => __( 'From Name', 'mail-control' ),
        'description' => __( 'Your emails will be sent with this name', 'mail-control' ),
    ]
    ],
    ];
    return $settings;
} );

if ( is_admin() ) {
    /**
     * Ajax sent a test email
     */
    add_action( 'wp_ajax_send_test_email', function () {
        check_ajax_referer( 'secure-nonce', 'test_email_once' );
        if ( empty($_POST["SMTP_MAILER_TEST_EMAIL"]) ) {
            wp_die( __( "Please fill the email field", 'mail-control' ) );
        }
        $email = sanitize_email( $_POST["SMTP_MAILER_TEST_EMAIL"] );
        if ( empty($email) ) {
            wp_die( __( "Please fill a correct email field", 'mail-control' ) );
        }
        // disable backgroud sending
        add_filter( 'mc_disable_email_queue', '__return_true' );
        // Init phpmailer SMTPDEBUG
        add_action( 'phpmailer_init', function ( $phpmailer ) {
            $phpmailer->SMTPDebug = true;
        }, 11 );
        $result = wp_mail(
            $email,
            __( 'Mail Control, test email', 'mail-control' ),
            sprintf( __( "This is a test email sent mail control in by %s", 'mail-control' ), get_home_url() ),
            [ "X-Source: Mail Control", "X-Campaign: Send Test Email" ]
        );
        wp_die( json_encode( [
            'success' => $result,
            'result'  => ob_get_clean(),
        ] ) );
    } );
    /**
     * Test email form
     */
    add_action( 'wsa_after_form_SMTP_MAILER', function () {
        $nonce = wp_create_nonce( "secure-nonce" );
        $admin = admin_url( "admin-ajax.php" );
        ?>
    <h2><?php 
        echo  esc_html__( 'Test your setup', 'mail-control' ) ;
        ?></h2>
    <form class="test_smtp" method='post' action="<?php 
        echo  $admin ;
        ?>">
	    <input type="hidden"  name="test_email_once" value="<?php 
        echo  $nonce ;
        ?>" />
	    <input type="hidden"  name="action" value="send_test_email" />
	    <div style="padding-left: 10px">
	 	<input type="email" required class="regular-text" id="SMTP_MAILER_TEST_EMAIL" name="SMTP_MAILER_TEST_EMAIL" value="" placeholder="yourtestemail@domain.com" />
	 		<?php 
        submit_button(
            __( 'Send a test email', 'mail-control' ),
            'primary',
            'test_smtp',
            false
        );
        ?>
	 	    <div class="test_result"></div>
	 	</div>
	</form>
	<?php 
        ?>
	<script>
		jQuery(document).ready( function($) {
			$( 'form.test_smtp' ).submit( function(e) {
				e.preventDefault();
				$me = $(this);
				$.ajax({
					url : $(this).attr('action'), // Here goes our WordPress AJAX endpoint.
					type : 'post',
					dataType: "json",
					data : $me.serializeArray(),
					success : function( response ) {
						$('div.test_result' , $me).html(response.result).addClass('notice').addClass( response.success ? 'notice-success' : 'notice-error' );
					},
					fail : function( err ) {
						$('div.test_result', $me) .html(err).addClass('notice notice-error');
					}
				});
				return false;
			});
		});
	</script>
	<?php 
    } );
}

/**
 * Initializes PHPMailer
 *
 * @param      \PHPMailer\PHPMailer\PHPMailer  $phpmailer  The phpmailer
 */
function init_phpmailer( PHPMailer $phpmailer )
{
    $phpmailer->Mailer = 'smtp';
    
    if ( SMTP_MAILER_SSL == 'on' ) {
        // compat SSL as a checkbox
        $phpmailer->SMTPSecure = 'ssl';
    } else {
        $phpmailer->SMTPSecure = ( SMTP_MAILER_SSL ? SMTP_MAILER_SSL : false );
    }
    
    $phpmailer->SMTPAutoTLS = ( $phpmailer->SMTPSecure ? true : false );
    $phpmailer->Host = SMTP_MAILER_HOST;
    $phpmailer->Port = SMTP_MAILER_PORT;
    
    if ( SMTP_MAILER_USER && SMTP_MAILER_PASSWORD ) {
        $phpmailer->SMTPAuth = true;
        $phpmailer->Username = SMTP_MAILER_USER;
        $phpmailer->Password = SMTP_MAILER_PASSWORD;
    } else {
        $phpmailer->SMTPAuth = false;
    }

}

add_action( 'settings_ready', function () {
    
    if ( defined( 'SMTP_MAILER_HOST' ) && SMTP_MAILER_HOST ) {
        add_action( 'phpmailer_init', 'Mail_Control\\init_phpmailer' );
        if ( SMTP_MAILER_FROM_EMAIL ) {
            add_filter( 'wp_mail_from', function () {
                return sanitize_email( SMTP_MAILER_FROM_EMAIL );
            } );
        }
        if ( SMTP_MAILER_FROM_NAME ) {
            add_filter( 'wp_mail_from_name', function () {
                return SMTP_MAILER_FROM_NAME;
            } );
        }
    }

}, 0 );