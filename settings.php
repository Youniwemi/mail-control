<?php

namespace Mail_Control;

class Settings extends \WP_OSA
{
    public function admin_menu()
    {
        add_submenu_page(
            'mail-control',
            'Settings',
            'Settings',
            MC_PERMISSION_MANAGER,
            'mail-control-settings',
            array( $this, 'plugin_page' )
        );
    }
    
    public function plugin_page()
    {
        if ( isset( $_GET['welcome-message'] ) && $_GET['welcome-message'] == 'true' ) {
            echo  '<div class="notice notice-success is-dismissible"><p>' . sprintf( __( 'Welcome to Mail Contol, your one stop plugin to take control over your wordpress emails, feel to <a href="%s" >contact us</a> if you have any question.', 'mail-control' ), mc_fs()->contact_url() ) . '</p></div>' ;
        }
        echo  '<div class="wrap">' ;
        echo  '<h1>' . esc_html__( 'Mail Control Settings', 'mail-control' ) . '</h1>' ;
        $this->show_navigation();
        $this->show_forms();
        echo  '</div>' ;
    }
    
    public function default_sanitization_error_message( $field_config )
    {
        return sprintf( __( 'Please insert a valid %s', 'mail-control' ), $field_config['type'] );
    }

}
function general_settings()
{
    $admin_capabilities = array_keys( get_role( 'administrator' )->capabilities );
    $capabilities = [];
    foreach ( $admin_capabilities as $cap ) {
        $capabilities[$cap] = $cap;
    }
    $general_settings = [
        'name'   => 'MC_PERMISSION',
        'title'  => __( 'Permission Settings', 'mail-control' ),
        'fields' => [ [
        'id'      => 'MANAGER',
        'type'    => 'select',
        'title'   => __( 'Minimal permissions to acces settings', 'mail-control' ),
        'default' => 'manage_options',
        'options' => $capabilities,
    ], [
        'id'      => 'VIEWER',
        'type'    => 'select',
        'title'   => __( 'Minimal permissions to view email logs', 'mail-control' ),
        'default' => 'edit_posts',
        'options' => $capabilities,
    ] ],
    ];
    return [
        'MC_PERMISSION' => $general_settings,
    ];
}

add_action( 'plugins_loaded', function () {
    load_plugin_textdomain( 'mail-control', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    new Settings( apply_filters( 'mail_control_settings', general_settings() ) );
} );