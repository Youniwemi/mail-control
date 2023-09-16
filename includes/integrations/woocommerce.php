<?php

namespace Mail_Control;

define( 'MC_WOOCOMMERCE_EMAIL_TYPES', [
    'new_order'                 => 'WC_Email_New_Order',
    'cancelled_order'           => 'WC_Email_Cancelled_Order',
    'customer_processing_order' => 'WC_Email_Customer_Processing_Order',
    'customer_completed_order'  => 'WC_Email_Customer_Completed_Order',
    'customer_refunded_order'   => 'WC_Email_Customer_Refunded_Order',
    'customer_on_hold_order'    => 'WC_Email_Customer_On_Hold_Order',
    'customer_invoice'          => 'WC_Email_Customer_Invoice',
    'failed_order'              => 'WC_Email_Failed_Order',
    'customer_new_account'      => 'WC_Email_Customer_New_Account',
    'customer_note'             => 'WC_Email_Customer_Note',
    'customer_reset_password'   => 'WC_Email_Customer_Reset_Password',
] );
/**
 * Loads a woocommerce preview.
 *
 * @param      string  $email_template  The email template
 *
 * @return     WC_Emails|null  ( description_of_the_return_value )
 */
function load_woocommerce_preview( $email_template )
{
    $wc_emails = \WC_Emails::instance();
    $emails = $wc_emails->get_emails();
    $email = $emails[MC_WOOCOMMERCE_EMAIL_TYPES[$email_template]];
    $orders = wc_get_orders( [
        'numberposts' => 1,
    ] );
    
    if ( $orders ) {
        $email->object = $orders[0];
        return $email;
    } else {
        return null;
    }

}

/**
 * Adds woocommerce settings to email customizer
 */
function setup_woocommerce_customizer()
{
    add_filter( 'mc_customizer_sections', function ( $sections ) {
        $sections['woo_style'] = [
            'section'  => __( 'WooCommerce Table Style', 'mail-control' ),
            'priority' => 14,
        ];
        return $sections;
    } );
    add_filter( 'mc_customizer_settings', function ( $settings ) {
        $settings = array_merge( $settings, [ [
            'id'          => 'table_font_size',
            'label'       => __( 'Choose your Table font size', 'mail-control' ),
            'section'     => 'woo_style',
            'type'        => 'range',
            'input_attrs' => [
            'step' => 1,
            'min'  => 8,
            'max'  => 70,
        ],
            'selectors'   => 'td.td, th.td',
            'transport'   => 'postMessage',
            'default'     => get_defaults( 'table_font_size' ),
        ], [
            'id'        => 'table_border_color',
            'label'     => __( 'Choose your Table border color', 'mail-control' ),
            'section'   => 'woo_style',
            'control'   => 'color',
            'selectors' => 'td.td, th.td',
            'transport' => 'postMessage',
            'default'   => get_defaults( 'table_border_color' ),
        ], [
            'id'          => 'table_border_size',
            'label'       => __( 'Choose your Table border size', 'mail-control' ),
            'section'     => 'woo_style',
            'type'        => 'range',
            'selectors'   => 'td.td, th.td',
            'transport'   => 'postMessage',
            'input_attrs' => [
            'step' => 0.5,
            'min'  => 0,
            'max'  => 5,
        ],
            'default'     => get_defaults( 'table_border_size' ),
        ] ] );
        return $settings;
    } );
}

/**
 * Ensures email customizer integrates correctly with woocommerce emails
 */
function customize_woocommerce_emails()
{
    remove_all_actions( 'woocommerce_email_header' );
    remove_all_actions( 'woocommerce_email_footer' );
    add_filter( 'woocommerce_email_styles', '__return_empty_string', 1000 );
    if ( isset( $_GET['preview_woocommerce_mail'] ) && isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'preview-mail' ) ) {
        // phpcs:ignore input var ok, sanitization ok.
        add_filter( 'woocommerce_mail_content', 'Mail_Control\\wrap_message', 1000 );
    }
    add_action(
        'woocommerce_email_header',
        function ( $email_heading, $email ) {
        echo  "<h1>" . esc_html( $email_heading ) . "</h1>" ;
    },
        10,
        2
    );
    add_filter( 'woocommerce_email_settings', function ( $settings ) {
        $settings = array_filter( $settings, function ( $setting ) {
            return !isset( $setting['id'] ) || !in_array( $setting['id'], [
                'woocommerce_email_header_image',
                'woocommerce_email_footer_text',
                'woocommerce_email_base_color',
                'woocommerce_email_background_color',
                'woocommerce_email_body_background_color',
                'woocommerce_email_text_color',
                'email_template_options'
            ] );
        } );
        $customizer = get_customizer_url();
        $text = "<a href='" . $customizer . "'>" . __( 'Use Mail Control to customize your emails ', 'mail-control' ) . "</a>";
        $settings[] = [
            'title' => __( 'Email template', 'woocommerce' ),
            'type'  => 'title',
            'desc'  => sprintf( __( 'This section lets you customize the WooCommerce emails. <a href="%s" target="_blank">Click here to preview your email template</a> or <a href="%s" target="_blank">Click here to customize your email template</a> ', 'mail-control' ), wp_nonce_url( admin_url( '?preview_woocommerce_mail=true' ), 'preview-mail' ), $customizer ),
            'id'    => 'email_template_options',
        ];
        return $settings;
    } );
}

/**
 * Disables the woocommerce customization.
 */
function disable_woocommerce_customization()
{
    // All woocommerce emails use woocommerce_email_headers filter to allow customizing headers
    add_filter( 'woocommerce_email_headers', function ( $headers ) {
        // disable the beautification
        add_filter( 'mc_disable_beautify', '__return_true' );
        return $headers;
    } );
    // remove the filter right after wp_mail filter
    add_filter( 'pre_wp_mail', function ( $return ) {
        remove_filter( 'mc_disable_beautify', '__return_true' );
        return $return;
    } );
}

add_filter( 'mc_customizer_email_types', function ( $email_types ) {
    $email_types += [
        'new_order'                 => __( 'Woocommerce : New Order', 'mail-control' ),
        'cancelled_order'           => __( 'Woocommerce : Cancelled Order', 'mail-control' ),
        'customer_processing_order' => __( 'Woocommerce : Customer Processing Order', 'mail-control' ),
        'customer_completed_order'  => __( 'Woocommerce : Customer Completed Order', 'mail-control' ),
        'customer_refunded_order'   => __( 'Woocommerce : Customer Refunded Order', 'mail-control' ),
        'customer_on_hold_order'    => __( 'Woocommerce : Customer On Hold Order', 'mail-control' ),
        'customer_invoice'          => __( 'Woocommerce : Customer Invoice', 'mail-control' ),
        'failed_order'              => __( 'Woocommerce : Failed Order', 'mail-control' ),
        'customer_new_account'      => __( 'Woocommerce : Customer New Account', 'mail-control' ),
        'customer_note'             => __( 'Woocommerce : Customer Note', 'mail-control' ),
        'customer_reset_password'   => __( 'Woocommerce : Customer Reset Password', 'mail-control' ),
    ];
    return $email_types;
} );
add_filter(
    'mc_customizer_preview',
    function ( $preview, $email_type ) {
    
    if ( isset( MC_WOOCOMMERCE_EMAIL_TYPES[$email_type] ) ) {
        $email = load_woocommerce_preview( $email_type );
        
        if ( $email ) {
            $content = $email->get_content();
            $subject = $email->get_heading();
        } else {
            $content = __( "You'll have to create some orders to be able to customize woocommerce emails", 'mail-control' );
            $subject = __( "Create orders first", 'mail-control' );
        }
        
        return [ $content, $subject ];
    }
    
    return $preview;
},
    10,
    2
);
add_filter( 'mc_customizer_defaults', function ( $defaults ) {
    $defaults += [
        'table_font_size'    => 11,
        'table_border_color' => '#eee',
        'table_border_size'  => 1,
    ];
    return $defaults;
} );
add_action( 'settings_ready_mc', function () {
    setup_woocommerce_customizer();
    if ( defined( 'EMAIL_CUSTOMIZER_ACTIVE' ) && EMAIL_CUSTOMIZER_ACTIVE == 'on' || MC_TEST_EMAIL_CUSTOMIZATION ) {
        
        if ( defined( 'EMAIL_CUSTOMIZER_WOOCOMMERCE_ENABLED' ) && EMAIL_CUSTOMIZER_WOOCOMMERCE_ENABLED == 'on' ) {
            add_action( 'woocommerce_email', 'Mail_Control\\customize_woocommerce_emails' );
        } else {
            // disable beautify ( on woocommerce email initialization )
            add_action( 'woocommerce_email', 'Mail_Control\\disable_woocommerce_customization' );
        }
    
    }
}, 100 );