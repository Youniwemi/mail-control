<?php

namespace Mail_Control;

use  Pelago\Emogrifier\CssInliner ;
define( 'MC_MODEL', 'zen' );
add_filter( 'mail_control_settings', function ( $settings ) {
    $return = settings_url();
    $customizer = get_customizer_url( $return );
    $settings['EMAIL_CUSTOMIZER'] = array(
        'name'        => 'EMAIL_CUSTOMIZER',
        'title'       => __( 'Email Customizer', 'mail-control' ),
        'description' => __( 'Customize the look and feel of the emails sent by Wordpress.', 'mail-control' ),
        'side_panel'  => function () {
        ?>
			<h3><?php 
        esc_html_e( 'How does the email customizer work?', 'mail-control' );
        ?></h3>
			<p><?php 
        esc_html_e( 'The Mail Control email customizer acts as a template overlay that is applied to the emails sent through WordPress, allowing for a consistent design across all communications.', 'mail-control' );
        ?></p>
			<h3><?php 
        esc_html_e( 'Can I try the customizer before ap', 'mail-control' );
        ?></h3>
			<p><?php 
        esc_html_e( 'The Mail Control email customizer acts as a template overlay that is applied to the emails sent through WordPress, allowing for a consistent design across all communications.', 'mail-control' );
        ?></p>
				<?php 
    },
        'fields'      => array( array(
        'id'    => 'ACTIVE',
        'type'  => 'checkbox',
        'title' => __( 'Activate Email customizations', 'mail-control' ),
    ), array(
        'id'          => 'LINK',
        'type'        => 'html',
        'title'       => __( 'Customize Your Emails', 'mail-control' ),
        'description' => "<a href='" . $customizer . "'>Start</a>",
    ) ),
    );
    if ( is_woocommerce_active() ) {
        $settings['EMAIL_CUSTOMIZER']['fields'][] = array(
            'id'          => 'WOOCOMMERCE_ENABLED',
            'type'        => 'checkbox',
            'title'       => __( 'Customize Woocommerce emails', 'mail-control' ),
            'description' => __( '(If unchecked, WooCommerce emails will remain default. Select this to enable Mail Control customization for WooCommerce emails.)', 'mail-control' ),
        );
    }
    return $settings;
} );
/**
 * Gets the customizer url.
 *
 * @param      string $return  The return url.
 *
 * @return     string  The customizer url.
 */
function get_customizer_url( string $return = null )
{
    $preview = wp_nonce_url( add_query_arg( array(
        'email-customizer-preview' => '1',
    ), home_url( '/' ) ), 'preview-mail' );
    return add_query_arg( array(
        'email-customizer' => 1,
        'url'              => urlencode( $preview ),
        'return'           => $return,
    ), admin_url( 'customize.php' ) );
}

/**
 * Gets the defaults.
 *
 * @param      string $key    The key
 *
 * @return     array|string  All The defaults if key is null or default value
 */
function get_defaults( string $key = null )
{
    static  $defaults ;
    if ( $defaults == null ) {
        $defaults = apply_filters( 'mc_customizer_defaults', array(
            'email_type'          => 'default',
            'logo'                => null,
            'logo_position'       => 'center',
            'logo_size'           => null,
            'logo_width'          => 100,
            'main_bg_color'       => '#f7f9ff',
            'main_font_family'    => 'arial',
            'main_font_size'      => 11,
            'title_font_size'     => 20,
            'title_margin_bottom' => 15,
            'title_transform'     => 'uppercase',
            'txt_color'           => '#606171',
            'main_color'          => '#ececec',
            'title_color'         => '#000000',
            'footer_bg_color'     => '#545ae8',
            'footer_txt_color'    => '#ffffff',
            'header_color'        => '#ffffff',
            'button_txt_color'    => '#fff',
            'button_bg_color'     => '#545ae8',
            'button_bg_color_hv'  => '#4347bc',
            'button_txt_color_hv' => '#fff',
            'button_padding_lr'   => 80,
            'button_padding_tb'   => 15,
            'button_font_size'    => 14,
            'button_radius'       => 0,
            'footer'              => null,
            'container_width'     => 600,
            'container_padding'   => 85,
            'link_color'          => '#545ae8',
            'additional_css'      => '',
        ) );
    }
    return ( isset( $key ) ? $defaults[$key] : $defaults );
}

/**
 * Gets the settings.
 *
 * @param      string $key    The key
 *
 * @return     array|string  The settings or setting value if keu is provided
 */
function get_my_settings( string $key = null )
{
    static  $settings ;
    if ( $settings == null ) {
        $settings = array_merge( get_defaults(), get_option( 'mc_customizer', array() ) );
    }
    return ( isset( $key ) ? $settings[$key] : $settings );
}

/**
 * Gets the preview email.
 *
 * @return     array  The preview email ( content, subject ).
 */
function get_preview_email()
{
    extract( get_my_settings() );
    $preview = apply_filters( 'mc_customizer_preview', null, $email_type );
    
    if ( $preview === null ) {
        $content = '<h2>Big announcement</h2>
			<p>We’re making some changes to our <a>Basic plans</a>, and we wanted to let you know what’s coming. First, we’re increasing the price of our Basic plan by $19. However, we’re also adding some new features that we think you’ll love.</p>
			<p>Here are some of the new features you can expect to see soon:
			<ul>
			<li>More Color options</li>
			<li>Edited Labels</li>
			<li>SEO Pack</li>
			</ul></p>
			<p><strong>We know that change can be hard, but we hope you’ll be happy with the new features.</strong></p>
			<p>This is the last chance for you to upgrade at a <i>lower price</i>. After this, the monthly price will be $19. So, are you ready to upgrade?</p>
			<h3>Any questions?</h3>
			<p>As always, if you have any questions or concerns, please don’t hesitate to reach out to us <a href="">Here</a>.</p>
			<a href="" class="btn">UPGRADE</a>';
        $subject = 'Email customizer';
        return array( $content, $subject );
    }
    
    return $preview;
}

/**
 * Customizer's preview email
 */
function preview_email()
{
    list( $content, $subject ) = get_preview_email();
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPressDotOrg.sniffs.OutputEscaping.UnescapedOutputParameter -- safe html generated using zen.php template
    echo  wrap_message( $content, $subject ) ;
    exit;
}

/**
 * Gets the font family.
 *
 * @param      string $key    The key
 *
 * @return     array|string The font families or css definition if key is provided.
 */
function get_font_family( $key = null )
{
    static  $font_families ;
    if ( $font_families == null ) {
        $font_families = apply_filters( 'mc_customizer_font_families', array(
            'helvetica'   => '"Helvetica Neue", Helvetica, Roboto, Arial, sans-serif',
            'arial'       => 'Arial, Helvetica, sans-serif',
            'arial_black' => '"Arial Black", Gadget, sans-serif',
            'courier'     => '"Courier New", Courier, monospace',
            'impact'      => 'Impact, Charcoal, sans-serif',
            'lucida'      => '"Lucida Sans Unicode", "Lucida Grande", sans-serif',
            'palatino'    => '"Palatino Linotype", "Book Antiqua", Palatino, serif',
            'georgia'     => 'Georgia, serif',
        ) );
    }
    if ( $key ) {
        return $font_families[$key];
    }
    return $font_families;
}

/**
 * Gets the font transforms.
 *
 * @return     array   The font transforms.
 */
function get_font_transforms()
{
    static  $font_transform ;
    if ( $font_transform == null ) {
        $font_transform = array(
            'uppercase'  => __( 'Uppercase', 'mail-control' ),
            'capitalize' => __( 'Capitalize', 'mail-control' ),
            'lowercase'  => __( 'Lowercase', 'mail-control' ),
            'none'       => __( 'None', 'mail-control' ),
        );
    }
    return $font_transform;
}

/**
 * Gets the text align.
 *
 * @return     array   The text align.
 */
function get_text_align()
{
    static  $text_align ;
    if ( $text_align == null ) {
        $text_align = array(
            'left'   => __( 'Left', 'mail-control' ),
            'center' => __( 'Center', 'mail-control' ),
            'right'  => __( 'Right', 'mail-control' ),
        );
    }
    return $text_align;
}

/**
 * Email type to customize
 *
 * @return     array  list of email types
 */
function email_types()
{
    $email_types = array(
        'default' => __( 'Simple Wordpress Email', 'mail-control' ),
    );
    return apply_filters( 'mc_customizer_email_types', $email_types );
}

/**
 * Gets the customizer settings.
 */
function get_customizer_settings()
{
    static  $settings, $sections ;
    
    if ( $settings == null ) {
        $sections = apply_filters( 'mc_customizer_sections', array(
            'type'         => __( 'Email Type', 'mail-control' ),
            'style'        => __( 'General Style', 'mail-control' ),
            'header_style' => __( 'Header', 'mail-control' ),
            'title_style'  => __( 'Title style', 'mail-control' ),
            'content'      => __( 'Content Syle', 'mail-control' ),
            'button_style' => __( 'Button', 'mail-control' ),
            'footer_style' => __( 'Footer', 'mail-control' ),
            'send_preview' => __( 'Send Preview', 'mail-control' ),
        ) );
        $settings = apply_filters( 'mc_customizer_settings', array(
            array(
                'id'      => 'email_type',
                'label'   => __( 'Choose the email Type', 'mail-control' ),
                'section' => 'type',
                'type'    => 'select',
                'choices' => email_types(),
                'default' => get_defaults( 'email_type' ),
            ),
            // Mail Header STYLE ( LOGO, HEADER BG COLOR...)
            array(
                'id'        => 'logo',
                'label'     => __( 'Choose your logo', 'mail-control' ),
                'section'   => 'header_style',
                'control'   => 'image',
                'transport' => 'postMessage',
                'selectors' => '#email_logo',
            ),
            array(
                'id'      => 'header_color',
                'label'   => __( 'Choose your main Header color', 'mail-control' ),
                'section' => 'header_style',
                'control' => 'color',
                'default' => get_defaults( 'header_color' ),
            ),
            array(
                'id'      => 'logo_position',
                'label'   => __( 'Logo Position', 'mail-control' ),
                'section' => 'header_style',
                'type'    => 'select',
                'choices' => get_text_align(),
                'default' => get_defaults( 'logo_position' ),
            ),
            array(
                'id'          => 'logo_width',
                'label'       => __( 'Logo Width (px)', 'mail-control' ),
                'section'     => 'header_style',
                'type'        => 'range',
                'default'     => get_defaults( 'logo_width' ),
                'input_attrs' => array(
                'min' => 90,
                'max' => 300,
            ),
            ),
            // GENERAL STYLE (BACKGROUND MAIL COLOR,FONT FAMILY...)
            array(
                'id'        => 'main_bg_color',
                'label'     => __( 'Choose main background color', 'mail-control' ),
                'section'   => 'style',
                'control'   => 'color',
                'transport' => 'postMessage',
                'selectors' => 'td.background',
                'default'   => get_defaults( 'main_bg_color' ),
            ),
            array(
                'id'          => 'main_font_size',
                'label'       => __( 'Choose your main font size (px)', 'mail-control' ),
                'section'     => 'style',
                'type'        => 'range',
                'input_attrs' => array(
                'step' => 1,
                'min'  => 8,
                'max'  => 70,
            ),
                'default'     => get_defaults( 'main_font_size' ),
            ),
            array(
                'id'      => 'main_font_family',
                'label'   => __( 'Choose your main font family', 'mail-control' ),
                'section' => 'style',
                'type'    => 'select',
                'choices' => get_font_family(),
                'default' => get_defaults( 'main_font_family' ),
            ),
            array(
                'id'          => 'container_width',
                'label'       => __( 'Container Width (px)', 'mail-control' ),
                'section'     => 'style',
                'type'        => 'range',
                'default'     => get_defaults( 'container_width' ),
                'input_attrs' => array(
                'min' => 300,
                'max' => 800,
            ),
            ),
            array(
                'id'          => 'additional_css',
                'label'       => __( 'Additional CSS', 'mail-control' ),
                'section'     => 'style',
                'control'     => 'code_editor',
                'code_type'   => 'text/css',
                'input_attrs' => array(
                'aria-describedby' => 'editor-keyboard-trap-help-1 editor-keyboard-trap-help-2 editor-keyboard-trap-help-3 editor-keyboard-trap-help-4',
            ),
                'default'     => '',
            ),
            // CONTENT STYLE (TXT COLOR,LINKs COLOR...)
            array(
                'id'        => 'txt_color',
                'label'     => __( 'Choose your text color', 'mail-control' ),
                'section'   => 'content',
                'control'   => 'color',
                'transport' => 'postMessage',
                'selectors' => '.body-cell p',
                'default'   => get_defaults( 'txt_color' ),
            ),
            array(
                'id'      => 'link_color',
                'label'   => __( 'Choose your link color', 'mail-control' ),
                'section' => 'content',
                'control' => 'color',
                'default' => get_defaults( 'link_color' ),
            ),
            array(
                'id'      => 'container_padding',
                'label'   => __( 'Content padding (px)', 'mail-control' ),
                'section' => 'content',
                'type'    => 'range',
                'default' => get_defaults( 'container_padding' ),
            ),
            // GENERAL HEADING STYLE (H1,H2,H3,H4...)
            array(
                'id'        => 'title_color',
                'label'     => __( 'Choose Title color', 'mail-control' ),
                'section'   => 'title_style',
                'transport' => 'postMessage',
                'selectors' => 'h1,h2,h3,h4,h5,h6',
                'control'   => 'color',
                'default'   => get_defaults( 'title_color' ),
            ),
            array(
                'id'          => 'title_font_size',
                'label'       => __( 'Choose your Title font size (px)', 'mail-control' ),
                'section'     => 'title_style',
                'type'        => 'range',
                'input_attrs' => array(
                'step' => 1,
                'min'  => 8,
                'max'  => 70,
            ),
                'default'     => get_defaults( 'title_font_size' ),
            ),
            array(
                'id'      => 'title_margin_bottom',
                'label'   => __( 'Title margin bottom (px)', 'mail-control' ),
                'section' => 'title_style',
                'type'    => 'range',
                'default' => get_defaults( 'title_margin_bottom' ),
            ),
            array(
                'id'      => 'title_transform',
                'label'   => __( 'Title Transform', 'mail-control' ),
                'section' => 'title_style',
                'type'    => 'select',
                'choices' => get_font_transforms(),
                'default' => get_defaults( 'title_transform' ),
            ),
            // Button STYLE
            array(
                'id'        => 'button_txt_color',
                'label'     => __( 'Text color', 'mail-control' ),
                'section'   => 'button_style',
                'control'   => 'color',
                'transport' => 'postMessage',
                'selectors' => 'a.button, a.btn',
                'default'   => get_defaults( 'button_txt_color' ),
            ),
            array(
                'id'      => 'button_bg_color',
                'label'   => __( 'Background color', 'mail-control' ),
                'section' => 'button_style',
                'control' => 'color',
                'default' => get_defaults( 'button_bg_color' ),
            ),
            array(
                'id'      => 'button_txt_color_hv',
                'label'   => __( 'Text hover color', 'mail-control' ),
                'section' => 'button_style',
                'control' => 'color',
                'default' => get_defaults( 'button_txt_color_hv' ),
            ),
            array(
                'id'      => 'button_bg_color_hv',
                'label'   => __( 'Background hover color', 'mail-control' ),
                'section' => 'button_style',
                'control' => 'color',
                'default' => get_defaults( 'button_bg_color_hv' ),
            ),
            array(
                'id'          => 'button_font_size',
                'label'       => __( 'Button font size (px)', 'mail-control' ),
                'section'     => 'button_style',
                'type'        => 'range',
                'input_attrs' => array(
                'step' => 1,
                'min'  => 8,
                'max'  => 40,
            ),
                'default'     => get_defaults( 'button_font_size' ),
            ),
            array(
                'id'      => 'button_padding_lr',
                'label'   => __( 'Left & Right padding (px)', 'mail-control' ),
                'section' => 'button_style',
                'type'    => 'range',
                'default' => get_defaults( 'button_padding_lr' ),
            ),
            array(
                'id'      => 'button_padding_tb (px)',
                'label'   => __( 'Top & Bottom padding', 'mail-control' ),
                'section' => 'button_style',
                'type'    => 'range',
                'default' => get_defaults( 'button_padding_tb' ),
            ),
            array(
                'id'      => 'button_radius',
                'label'   => __( 'Border Radius (px)', 'mail-control' ),
                'section' => 'button_style',
                'type'    => 'range',
                'default' => get_defaults( 'button_radius' ),
            ),
            // FOOTER
            array(
                'id'      => 'footer_bg_color',
                'label'   => __( 'Choose your Background Footer color', 'mail-control' ),
                'section' => 'footer_style',
                'control' => 'color',
                'default' => get_defaults( 'footer_bg_color' ),
            ),
            array(
                'id'      => 'footer_txt_color',
                'label'   => __( 'Text color', 'mail-control' ),
                'section' => 'footer_style',
                'control' => 'color',
                'default' => get_defaults( 'footer_txt_color' ),
            ),
            array(
                'id'        => 'footer',
                'transport' => 'postMessage',
                'selectors' => '#footer_text',
                'label'     => __( 'Type your footer content', 'mail-control' ),
                'section'   => 'footer_style',
                'type'      => 'textarea',
                'default'   => get_defaults( 'footer' ),
            ),
            array(
                'id'      => 'send_email_preview',
                'label'   => __( 'Choose a recepient', 'mail-control' ),
                'section' => 'send_preview',
                'control' => Send_Preview_Control::class,
                'default' => null,
            ),
        ) );
    }
    
    return array( $settings, $sections );
}

/**
 * Gets the control class.
 *
 * @param      bool $control  The control
 *
 * @return     bool|string  The control class.
 */
function get_control_class( $control )
{
    if ( $control && class_exists( $control ) ) {
        return $control;
    }
    return '\\WP_Customize_' . (( $control ? ucfirst( $control ) . '_' : '' )) . 'Control';
}

/**
 * Beautifies Email, wraps the content in the template then inlines css
 *
 * @param      array $atts   wp_mail atts
 *
 * @return     array  beautified atts
 */
function beautify( $atts )
{
    if ( defined( 'MC_PROCESSING_MAIL_QUEUE' ) || defined( 'MC_RESENDING_EMAIL' ) ) {
        // do nothing if processing queue, too late to beautify
        return $atts;
    }
    // allow to skip beautify ( if we don't want to customize some emails - woocommerce emails for example  )
    if ( apply_filters( 'mc_disable_beautify', false, $atts ) ) {
        return $atts;
    }
    // headers should be an array, but just to be safe
    if ( is_string( $atts['headers'] ) ) {
        $atts['headers'] = explode( "\n", $atts['headers'] );
    }
    // if the email already beautified
    if ( email_header_has( $atts['headers'], 'X-Template' ) ) {
        return $atts;
    }
    extract( get_my_settings() );
    if ( !isset( $model ) ) {
        $model = MC_MODEL;
    }
    $atts['headers'][] = 'X-Template : ' . $model;
    ob_start();
    // if message is plain text ( or at least not HTML  )
    
    if ( !email_header_has( $atts['headers'], 'Content-Type', 'text/html' ) ) {
        // Save the plain text ( for tracking )
        $atts['message_plain'] = $atts['message'];
        $atts['message'] = array(
            'text/html'  => htmlize( $atts['message'] ),
            'text/plain' => $atts['message_plain'],
        );
        $atts['headers'] = email_header_set( $atts['headers'], 'Content-Type', 'multipart/alternative' );
    }
    
    $content = ( isset( $atts['message']['text/html'] ) ? $atts['message']['text/html'] : $atts['message'] );
    $subject = $atts['subject'];
    include MC_TEMPLATES . 'emails/' . $model . '.php';
    $rendered = apply_filters( 'email_beautify', ob_get_clean(), $atts );
    // Now inline css
    $rendered = CssInliner::fromHtml( $rendered )->inlineCss()->render();
    
    if ( isset( $atts['message']['text/html'] ) ) {
        $atts['message']['text/html'] = $rendered;
    } else {
        $atts['message'] = $rendered;
    }
    
    return $atts;
}

/**
 * Wrap message in template
 *
 * @param      string $content  The content
 * @param      string $subject  The subject
 *
 * @return     string  wraped message in template
 */
function wrap_message( $content, $subject = null )
{
    extract( get_my_settings() );
    if ( !isset( $model ) ) {
        $model = MC_MODEL;
    }
    ob_start();
    include MC_TEMPLATES . 'emails/' . $model . '.php';
    return ob_get_clean();
}

/**
 * Customizer preview initialisation
 *
 * @param      WP_Customize_Manager $wp_customize  The wp customizer
 */
function customize_preview_init( $wp_customize )
{
    // Avoid concatenation ( nginx http concat )
    add_filter( 'css_do_concat', '__return_false' );
    add_filter( 'js_do_concat', '__return_false' );
    // For the customizer preview, we won't inject wp_head and wp_footer as it will load more cr*p than we can handle.
    // so we need to "make" our own mc_head and mc_footer to ensure necessary customizer scripts are loaded and styles
    add_action( 'mc_header', function () use( $wp_customize ) {
        // disable concatenation
        wp_print_styles( array(
            'customize-preview',
            'wp-block-library',
            'wp-block-library-theme',
            'global-styles'
        ) );
        $wp_customize->customize_preview_loading_style();
        $wp_customize->remove_frameless_preview_messenger_channel();
    } );
    add_action( 'mc_footer', function () use( $wp_customize ) {
        wp_print_scripts( array(
            'customize-base',
            'customize-preview',
            'customize-preview-widgets',
            'customize-selective-refresh',
            'customize-preview-nav-menus'
        ) );
        $wp_customize->customize_preview_settings();
        $wp_customize->nav_menus->export_preview_data();
        $wp_customize->widgets->print_preview_css();
        $wp_customize->widgets->export_preview_data();
        $wp_customize->selective_refresh->export_preview_data();
    } );
    remove_hooks_except( 'customize_preview_init', array(
        array( 'WP_Customize_Selective_Refresh', 'init_preview' ),
        array( 'WP_Customize_Widgets', 'customize_preview_init' ),
        array( 'WP_Customize_Widgets', 'selective_refresh_init' ),
        array( 'WP_Customize_Nav_Menus', 'customize_preview_init' ),
        array( 'WP_Customize_Nav_Menus', 'make_auto_draft_status_previewable' )
    ) );
}

/**
 * Removes a hooks except.
 *
 * @param      string $action  The action
 * @param      array  $except  The except
 */
function remove_hooks_except( $action, array $except )
{
    global  $wp_filter ;
    foreach ( $wp_filter[$action]->callbacks as $priority => $callbacks ) {
        foreach ( $callbacks as $order => $callback ) {
            if ( $except ) {
                
                if ( $callback['function'] instanceof \Closure ) {
                    // anyway to compare closures?
                    // we just let it remove for now
                } elseif ( is_object( $callback['function'][0] ) ) {
                    $object = get_class( $callback['function'][0] );
                    $method = $callback['function'][1];
                    if ( in_array( array( $object, $method ), $except ) ) {
                        continue;
                    }
                } elseif ( is_callable( $callback['function'] ) ) {
                    if ( in_array( $callback['function'], $except ) ) {
                        continue;
                    }
                }
            
            }
            unset( $wp_filter[$action]->callbacks[$priority][$order] );
        }
    }
}

/**
 * Setup customizer
 *
 * @param      WP_Customize_Manager $wp_customize  The wp customize
 */
function setup_customizer( $wp_customize )
{
    list( $settings, $sections ) = get_customizer_settings();
    // Add settings first
    foreach ( $settings as $config ) {
        $id = $default = $sanitize_callback = $control = $description = $transport = null;
        extract( $config );
        $setting_id = "mc_customizer[{$id}]";
        $wp_customize->add_setting( $setting_id, array(
            'type'              => 'option',
            'transport'         => ( isset( $transport ) ? $transport : 'refresh' ),
            'capability'        => MC_PERMISSION_MANAGER,
            'default'           => ( isset( $default ) ? $default : '' ),
            'sanitize_callback' => ( isset( $sanitize_callback ) ? array( get_control_class( $control ), $sanitize_callback ) : '' ),
        ) );
    }
    // only in preview and email customization
    if ( !MC_CUSTOMIZING_EMAIL && !MC_PREVIEWING_EMAIL ) {
        return;
    }
    add_action( 'customize_controls_enqueue_scripts', function () {
        wp_enqueue_script(
            'mc-customizer-scripts',
            MC_URL . '/assets/js/customizer.js',
            array( 'jquery', 'customize-controls' ),
            MC_VERSION,
            true
        );
        // disable any scripts from other theme plugins
        remove_hooks_except( 'customize_controls_enqueue_scripts', array() );
    } );
    require_once __DIR__ . '/send-email-control.php';
    remove_hooks_except( 'customize_register', array( array( 'WP_Customize_Manager', 'register_dynamic_settings' ), array( 'WP_Customize_Nav_Menus', 'customize_register' ) ) );
    $wp_customize->add_panel( 'mc_customizer-panel', array(
        'title'      => __( 'Email Customizer', 'mail-control' ),
        'capability' => MC_PERMISSION_MANAGER,
    ) );
    $count = 0;
    foreach ( $sections as $id => $section ) {
        $count++;
        $section_id = 'mc_customizer-' . $id;
        $priority = $count * 2;
        if ( is_array( $section ) ) {
            extract( $section );
        }
        $wp_customize->add_section( $section_id, array(
            'title'      => $section,
            'capability' => MC_PERMISSION_MANAGER,
            'panel'      => 'mc_customizer-panel',
            'priority'   => $priority,
        ) );
    }
    foreach ( $settings as $config ) {
        $id = $label = $section = $control = $type = $description = $choices = $input_attrs = $transport = $selectors = $render_callback = null;
        extract( $config );
        $control = get_control_class( $control );
        $setting_id = "mc_customizer[{$id}]";
        $section_id = 'mc_customizer-' . $section;
        $wp_customize->add_control( new $control( $wp_customize, $setting_id, array(
            'settings'        => $setting_id,
            'label'           => $label,
            'type'            => $type,
            'active_callback' => '__return_true',
            'description'     => $description,
            'choices'         => $choices,
            'section'         => $section_id,
            'input_attrs'     => $input_attrs,
        ) ) );
        if ( $wp_customize->selective_refresh && $selectors ) {
            $wp_customize->selective_refresh->add_partial( "mc_customizer[{$id}]", array(
                'selector'        => $selectors,
                'render_callback' => $render_callback,
            ) );
        }
    }
    add_filter(
        'allowed_block_types_all',
        __NAMESPACE__ . '\\restrict_blocks',
        100,
        2
    );
}

function restrict_blocks( $allowed_block_types, $editor_context )
{
    $allowed_blocks = array(
        'core/group',
        'core/columns',
        'core/column',
        'core/heading',
        'core/image',
        'core/paragraph',
        'core/heading',
        'core/list'
    );
    // Allow adding/removing allowed blocks.
    apply_filters( 'mc_customizer_blocks', $allowed_blocks );
    return $allowed_blocks;
}

/**
 * Create Email Template widget
 */
function email_widget()
{
    register_sidebar( array(
        'name'          => esc_html__( 'Email Footer', 'mail-control' ),
        'id'            => 'mc_email_footer',
        'before_widget' => '<div id="%1$s" class="email_widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="email_widget-title">',
        'after_title'   => '</h3>',
    ) );
}


if ( is_admin() ) {
    /**
     * Adds customizer link in menu
     */
    add_action( 'admin_menu', function () {
        $customizer = get_customizer_url();
        add_submenu_page(
            'mail-control',
            __( 'Email Customizer', 'mail-control' ),
            __( 'Email Customizer', 'mail-control' ),
            MC_PERMISSION_MANAGER,
            $customizer
        );
    } );
    /**
     * Ajax sent a test email
     */
    add_action( 'wp_ajax_send_preview_email', function () {
        check_ajax_referer( 'secure-nonce', 'preview_email_once' );
        if ( empty($_POST['recipients']) ) {
            send_json_result( __( 'Please fill the email field', 'mail-control' ), false );
        }
        $to = array_map( 'sanitize_email', explode( ',', sanitize_text_field( wp_unslash( $_POST['recipients'] ) ) ) );
        if ( empty($to) ) {
            send_json_result( __( 'Please fill a correct email field', 'mail-control' ), false );
        }
        $headers = array( 'Content-Type: text/html' );
        ob_start();
        // no queue
        add_filter( 'mc_disable_email_queue', '__return_true' );
        list( $content ) = get_preview_email();
        $sent = wp_mail(
            $to,
            __( 'Mail Control, test preview customizer', 'mail-control' ),
            $content,
            $headers
        );
        send_json_result( ob_get_clean(), $sent );
    } );
}

add_action( 'settings_ready_mc', function () {
    define( 'MC_CUSTOMIZING_EMAIL', isset( $_GET['email-customizer'] ) );
    // phpcs:ignore WordPress.CSRF.NonceVerification
    define( 'MC_PREVIEWING_EMAIL', isset( $_REQUEST['email-customizer-preview'] ) && $_REQUEST['email-customizer-preview'] == 1 );
    // phpcs:ignore WordPress.CSRF.NonceVerification
    define( 'MC_TEST_EMAIL_CUSTOMIZATION', isset( $_POST['action'] ) && $_POST['action'] == 'send_preview_email' );
    // phpcs:ignore WordPress.CSRF.NonceVerification
    add_action( 'widgets_init', 'Mail_Control\\email_widget' );
    // Setup customizer, settings need to be declared, so ajax saving (publish) would work
    // we need to remove all customizations, so we set you customizer as first
    // We use priority one so our customizer will kick right after widget customizer
    add_action( 'customize_register', __NAMESPACE__ . '\\setup_customizer', 1 );
    
    if ( (MC_CUSTOMIZING_EMAIL || MC_PREVIEWING_EMAIL) && current_user_can( MC_PERMISSION_MANAGER ) ) {
        // Ensure we can selectively refresh widgets
        add_theme_support( 'customize-selective-refresh-widgets' );
        // Blocks load all
        add_filter( 'should_load_separate_core_block_assets', '__return_false', 1000 );
        wp_enqueue_global_styles();
        add_action( 'customize_preview_init', __NAMESPACE__ . '\\customize_preview_init', 0 );
        if ( MC_PREVIEWING_EMAIL ) {
            // we need to wait until template redirect
            add_action( 'template_redirect', __NAMESPACE__ . '\\preview_email', 1000 );
        }
    } elseif ( defined( 'EMAIL_CUSTOMIZER_ACTIVE' ) && EMAIL_CUSTOMIZER_ACTIVE == 'on' || MC_TEST_EMAIL_CUSTOMIZATION ) {
        add_action( 'mc_header', function () {
            // disable concatenation
            add_filter( 'css_do_concat', '__return_false' );
            // all block css at once
            add_filter( 'should_load_separate_core_block_assets', '__return_false' );
            // make sure global styles are included
            wp_enqueue_global_styles();
            wp_print_styles( array( 'wp-block-library', 'wp-block-library-theme', 'global-styles' ) );
            // remove our filter
            remove_filter( 'should_load_separate_core_block_assets', '__return_false' );
        } );
        add_action( 'mc_footer', function () {
            // disable concatenation
            // wp_print_styles();
        } );
        // let's beautify the email using wp_mail filter, only the email is not in queue
        add_filter(
            'wp_mail',
            __NAMESPACE__ . '\\beautify',
            100,
            1
        );
    }

} );