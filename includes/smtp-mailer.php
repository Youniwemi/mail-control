<?php

namespace Mail_Control;

use  PHPMailer\PHPMailer\PHPMailer ;
/**
 * Smtp Mailer Settings
 */
add_filter( 'mail_control_settings', function ( $settings ) {
    $settings['SMTP_MAILER'] = [
        'name'   => 'SMTP_MAILER',
        'title'  => __( 'SMTP Mailer', 'mail-control' ),
        'fields' => [
        [
        'id'                         => 'HOST',
        'type'                       => 'text',
        'title'                      => __( 'Smtp Host', 'mail-control' ),
        'description'                => __( 'Your smtp mail server hostname (or IP)', 'mail-control' ),
        'sanitize_callback'          => 'Mail_Control\\sanitize_smtp_host',
        'sanitization_error_message' => __( 'Please insert a valid hostname or IP', 'mail-control' ),
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
    include MC_INCLUDES . 'smtp-checks.php';
    /**
     * Ajax sent a test email
     */
    add_action( 'wp_ajax_send_test_email', function () {
        check_ajax_referer( 'secure-nonce', 'test_email_once' );
        if ( empty($_POST["SMTP_MAILER_TEST_EMAIL"]) ) {
            send_json_result( __( "Please fill the email field", 'mail-control' ), false );
        }
        $to = array_map( 'sanitize_email', explode( ',', $_POST["SMTP_MAILER_TEST_EMAIL"] ) );
        if ( empty($to) ) {
            send_json_result( __( "Please fill a correct email field", 'mail-control' ), false );
        }
        init_test_email_mode();
        $headers = [];
        ob_start();
        $sent = wp_mail(
            $to,
            __( 'Mail Control, test email', 'mail-control' ),
            sprintf( __( "This is a test email sent mail control in by %s", 'mail-control' ), get_home_url() ),
            $headers
        );
        send_json_result( ob_get_clean(), $sent );
    } );
    /**
     * Ajax test a domain
     */
    add_action( 'wp_ajax_test_domain', function () {
        check_ajax_referer( 'secure-nonce', 'test_domain_once' );
        $email = sanitize_email( SMTP_MAILER_FROM_EMAIL );
        if ( !$email ) {
            send_json_result( __( "You have to setup From Email field to run this test", 'mail-control' ), false );
        }
        $host = SMTP_MAILER_HOST;
        if ( !$host ) {
            send_json_result( __( "You have to setup the smtp host to run this test", 'mail-control' ), false );
        }
        // SPF
        $domain = explode( '@', $email )[1];
        $report = [];
        [ $spf_ok, $report ] = test_spf_record( $domain, $host, $report );
        // DKIM
        if ( !empty($_POST["SMTP_MAILER_TEST_DKIM"]) ) {
            $selector = sanitize_text_field( $_POST["SMTP_MAILER_TEST_DKIM"] );
        }
        $dkim_host = ( isset( $selector ) ? $selector . '._domainkey.' . $domain : '' );
        [ $dkim_ok, $report ] = test_dkim_record( $dkim_host, $report );
        // DMARC
        [ $dmarc_ok, $report ] = test_dmarc_record( $domain, $report );
        $config_ok = $spf_ok && $dkim_ok && $dmarc_ok;
        
        if ( $config_ok ) {
            $report[] = '<p class="notice notice-success">' . __( 'Bravo! Our checks are succesful, still, make sure to send a test email', 'mail-control' ) . '<br/>';
        } else {
            $report[] = '<p class="notice notice-info">' . sprintf( __( 'Don\'t hesitate to request us for some assistance helping you setting your domains, feel free to <a href="%s" >contact us</a>', 'mail-control' ), mc_fs()->contact_url() ) . '<br/>';
        }
        
        $locale = substr( get_locale(), 0, 2 );
        $app_mail_dev = "https://www.appmaildev.com/{$locale}/dkim";
        $report[] = sprintf( __( 'For a more complete test, we suggest you go to %s. After clicking on "Next Step", you will be asked to send an email to a temporary address test-XXXXXXX@appmaildev.com. There, you can your use our "send a test email" feature to send your email and then receive a complete delivrability report.', 'mail-control' ), "<a href='{$app_mail_dev} ' target='_blank'>{$app_mail_dev} </a>" ) . '<br/>';
        $report[] = '</p>';
        send_json_result( implode( '', $report ), $config_ok );
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
    <form class="test_smtp"  data-result="email_test" method='post' action="<?php 
        echo  esc_url( $admin ) ;
        ?>">
	    <input type="hidden"  name="test_email_once" value="<?php 
        echo  esc_attr( $nonce ) ;
        ?>" />
	    <input type="hidden"  name="action" value="send_test_email" />
	    <div style="padding-left: 10px">
	 	<input type="email" multiple required class="medium-text" id="SMTP_MAILER_TEST_EMAIL" name="SMTP_MAILER_TEST_EMAIL" value="" placeholder="yourtestemail@domain.com" />
	 		<?php 
        submit_button(
            __( 'Send a test email', 'mail-control' ),
            'primary',
            'test_smtp',
            false
        );
        ?>
	 	    <div id="email_test" class="test_result"></div>
	 	</div>
	</form>

	<h2><?php 
        _e( 'Test your SPF, DKIM, and domain DMARC setup (experimental):', 'mail-control' );
        ?> </h2>
	<form class="test_smtp" data-result="dns_test" method='post' action="<?php 
        echo  esc_url( $admin ) ;
        ?>">
	    <input type="hidden"  name="test_domain_once" value="<?php 
        echo  esc_attr( $nonce ) ;
        ?>" />
	    <input type="hidden"  name="action" value="test_domain" />
	    <div style="padding-left: 10px;margin-top:1em;">
	    	
			<input type="text" class="medium-text" id="SMTP_MAILER_TEST_DKIM" name="SMTP_MAILER_TEST_DKIM" value="" placeholder="<?php 
        esc_attr_e( 'DKIM Selector', 'mail-control' );
        ?>" />
	    	<?php 
        submit_button(
            __( 'Test your domain', 'mail-control' ),
            'primary',
            'test_domain',
            false
        );
        ?>
		    <div id="dns_test" class="test_result" ></div>
		</div>
	</form>
	<script>
		jQuery(document).ready( function($) {
			$( 'form.test_smtp' ).submit( function(e) {
				$me = $(this);
				var result = $me.data('result');
				var $result = $('#'+result).html('').removeClass();
				$.ajax({
					url : $me.attr('action'),
					type : 'post',
					dataType: "json",
					data : $me.serializeArray(),
					success : function( response ) {						
						$result.html(response.result).
						addClass('notice').
						addClass( response.success ? 'notice-success' : 'notice-error' );
					},
					fail : function( err ) {
						$result.html(err).addClass('notice notice-error');
					}
				});
				return false;
			});
		});
	</script>
	<style>
	form .notice { word-break: break-all; }
	</style>
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
    
    if ( SMTP_MAILER_FROM_EMAIL && SMTP_MAILER_FROM_NAME ) {
        $phpmailer->setFrom( sanitize_email( SMTP_MAILER_FROM_EMAIL ), SMTP_MAILER_FROM_NAME );
    }
}

add_action( 'settings_ready_mc', function () {
    
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