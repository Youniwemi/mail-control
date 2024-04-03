<?php

namespace Mail_Control;

use  PHPMailer\PHPMailer\PHPMailer ;
/**
 * Smtp Mailer Settings
 */
add_filter( 'mail_control_settings', function ( $settings ) {
    $settings['SMTP_MAILER'] = array(
        'name'        => 'SMTP_MAILER',
        'title'       => __( 'SMTP Mailer and Deliverability', 'mail-control' ),
        'description' => __( 'Ensure reliable delivery. Configure SMTP to improve email deliverability.', 'mail-control' ),
        'side_panel'  => function () {
        ?>
			<h3><?php 
        esc_html_e( 'Why should I setup SMTP', 'mail-control' );
        ?></h3>
			<p><?php 
        esc_html_e( "SMTP stands for Simple Mail Transfer Protocol, and it's like the postal service for your emails. By setting up SMTP, you can send emails through a professional email provider, which helps make sure your messages land in the inbox, not the spam folder.", 'mail-control' );
        ?></p>
			<h3><?php 
        esc_html_e( 'What are my SMTP options?', 'mail-control' );
        ?></h3>
			<ul>
				<li><?php 
        esc_html_e( 'Your Hosting Provider: Check if they offer SMTP services.', 'mail-control' );
        ?></li>
				<li><?php 
        esc_html_e( 'Transactional Email Services: Like SendGrid, Brevo, MailJet, Mailgun, etc for high volume sending, most of those options have a free tier. (just google transactional email service)', 'mail-control' );
        ?></li>
				<li><?php 
        esc_html_e( 'Office Email Services: Microsoft 365, Yahoo Mail, Zoho Mail, etc., if you prefer using existing services.', 'mail-control' );
        ?></li>
			</ul>
			<h3><?php 
        esc_html_e( 'How can I use Gmail for SMTP?', 'mail-control' );
        ?></h3>
			<ul>
				<li><?php 
        echo  wp_kses_post( sprintf( __( "Gmail Settings: First, you should <a href='%s'>create a password application</a> for your wordpress site, this will allow Mail Control to send emails using your Gmail account.", 'mail-control' ), 'https://myaccount.google.com/apppasswords' ) ) ;
        ?></li>
				<li><?php 
        esc_html_e( 'SMTP Details: Enter the following details:', 'mail-control' );
        ?>
				<br/>
				<?php 
        esc_html_e( 'Smtp Host', 'mail-control' );
        ?>: smtp.gmail.com<br/>
				<?php 
        esc_html_e( 'Smtp PORT', 'mail-control' );
        ?>: 465<br/>
				<?php 
        esc_html_e( 'Smtp Encryption', 'mail-control' );
        ?>: ssl<br/>
				<?php 
        esc_html_e( 'Smtp User', 'mail-control' );
        ?>: your-gmail-adress@gmail.com<br/>
				<?php 
        esc_html_e( 'Smtp Password', 'mail-control' );
        ?>: <?php 
        esc_html_e( 'The password your created earlier', 'mail-control' );
        ?><br/>
				<?php 
        esc_html_e( 'From Name', 'mail-control' );
        ?>: your-gmail-adress@gmail.com
			</li>
			</ul>
			<p><?php 
        esc_html_e( 'Monitor Email Sending Limits: Remember that Gmail has a sending limit of 500 emails per day for regular accounts and 2,000 for G Suite users.', 'mail-control' );
        ?></p>
			<h3><?php 
        esc_html_e( 'What Are SPF, DKIM, and DMARC?', 'mail-control' );
        ?></h3>
			<p><?php 
        esc_html_e( 'SPF (Sender Policy Framework), DKIM (DomainKeys Identified Mail), and DMARC (Domain-based Message Authentication, Reporting, and Conformance) are email authentication methods that help protect your email reputation and combat spam.', 'mail-control' );
        ?></p>
			<h3><?php 
        esc_html_e( 'Why Should I Test My SPF, DKIM, and Domain DMARC Setup and When?', 'mail-control' );
        ?></h3>
			<p><?php 
        esc_html_e( "Testing your SPF, DKIM, and DMARC settings is like making sure your doors are locked and your alarm is set. It's all about security and ensuring that your emails are trusted and reach their destination. This test is advisable if you send using you domain name to ensure it is correctly configured.", 'mail-control' );
        ?></p>
			<h3><?php 
        esc_html_e( 'Still need Help?', 'mail-control' );
        ?></h3>
			<p><?php 
        esc_html_e( "Don't be shy, contact us with your question, we'll do our best to help you get up and running.", 'mail-control' );
        ?></p>
			<p><a class="button" href="<?php 
        echo  esc_url( mc_fs()->contact_url() ) ;
        ?>" ><?php 
        echo  esc_html__( 'Ask for help', 'mail-control' ) ;
        ?></a></p>
				<?php 
    },
        'fields'      => array(
        array(
        'id'                         => 'HOST',
        'type'                       => 'text',
        'title'                      => __( 'Smtp Host', 'mail-control' ),
        'description'                => __( 'Your smtp mail server hostname (or IP)', 'mail-control' ),
        'sanitize_callback'          => 'Mail_Control\\sanitize_smtp_host',
        'sanitization_error_message' => __( 'Please insert a valid hostname or IP', 'mail-control' ),
    ),
        array(
        'id'          => 'PORT',
        'type'        => 'number',
        'title'       => __( 'Smtp PORT', 'mail-control' ),
        'description' => __( 'You smtp mail server PORT', 'mail-control' ),
    ),
        array(
        'id'          => 'SSL',
        'type'        => 'radio',
        'options'     => array(
        ''    => 'None',
        'ssl' => 'SSL',
        'tls' => 'TLS',
    ),
        'title'       => __( 'Smtp Encryption', 'mail-control' ),
        'description' => __( 'What type of encryption your server is using', 'mail-control' ),
    ),
        array(
        'id'          => 'USER',
        'type'        => 'text',
        'title'       => __( 'Smtp User', 'mail-control' ),
        'description' => __( 'You smtp account\'s username', 'mail-control' ),
    ),
        array(
        'id'          => 'PASSWORD',
        'type'        => 'password',
        'title'       => __( 'Smtp Password', 'mail-control' ),
        'description' => __( 'You smtp account\'s password', 'mail-control' ),
    ),
        array(
        'id'          => 'FROM_EMAIL',
        'type'        => 'email',
        'title'       => __( 'From Email', 'mail-control' ),
        'description' => __( 'Your emails will be sent from this email adress', 'mail-control' ),
    ),
        array(
        'id'          => 'FROM_NAME',
        'type'        => 'text',
        'title'       => __( 'From Name', 'mail-control' ),
        'description' => __( 'Your emails will be sent with this name', 'mail-control' ),
    )
    ),
    );
    return $settings;
} );

if ( is_admin() ) {
    include MC_INCLUDES . 'smtp-checks.php';
    /**
     * Ajax sent a test email
     */
    add_action( 'wp_ajax_send_test_email', function () {
        check_ajax_referer( 'secure-nonce', 'test_email_once' );
        if ( empty($_POST['SMTP_MAILER_TEST_EMAIL']) ) {
            send_json_result( __( 'Please fill the email field', 'mail-control' ), false );
        }
        $to = array_map( 'sanitize_email', explode( ',', sanitize_text_field( wp_unslash( $_POST['SMTP_MAILER_TEST_EMAIL'] ) ) ) );
        if ( empty($to) ) {
            send_json_result( __( 'Please fill a correct email field', 'mail-control' ), false );
        }
        init_test_email_mode();
        $headers = array();
        ob_start();
        $sent = wp_mail(
            $to,
            __( 'Mail Control, test email', 'mail-control' ),
            sprintf( __( 'This is a test email sent mail control in by %s', 'mail-control' ), get_home_url() ),
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
            send_json_result( __( 'You have to setup From Email field to run this test', 'mail-control' ), false );
        }
        $host = SMTP_MAILER_HOST;
        if ( !$host ) {
            send_json_result( __( 'You have to setup the smtp host to run this test', 'mail-control' ), false );
        }
        // SPF
        $domain = explode( '@', $email )[1];
        $report = array();
        list( $spf_ok, $report ) = test_spf_record( $domain, $host, $report );
        // DKIM
        if ( !empty($_POST['SMTP_MAILER_TEST_DKIM']) ) {
            $selector = sanitize_text_field( wp_unslash( $_POST['SMTP_MAILER_TEST_DKIM'] ) );
        }
        $dkim_host = ( isset( $selector ) ? $selector . '._domainkey.' . $domain : '' );
        list( $dkim_ok, $report ) = test_dkim_record( $dkim_host, $report );
        // DMARC
        list( $dmarc_ok, $report ) = test_dmarc_record( $domain, $report );
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
        $nonce = wp_create_nonce( 'secure-nonce' );
        $admin = admin_url( 'admin-ajax.php' );
        ?>
	<h2><?php 
        esc_html_e( 'Test your setup', 'mail-control' );
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
        esc_html_e( 'Test your SPF, DKIM, and domain DMARC setup (experimental):', 'mail-control' );
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
 * @param      \PHPMailer\PHPMailer\PHPMailer $phpmailer  The phpmailer
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
        add_action( 'phpmailer_init', __NAMESPACE__ . '\\init_phpmailer' );
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