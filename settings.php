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
            MC_MANAGER_PERMISSION,
            'mail-control-settings',
            array( $this, 'plugin_page' )
        );
    }
    
    public function plugin_page()
    {
        if ( isset( $_GET['welcome-message'] ) && $_GET['welcome-message'] == 'true' ) {
            echo  '<div class="notice notice-success is-dismissible"><p>' . sprintf( __( 'Welcone to Mail Contol, your one stop plugin to take control over your wordpress emails, feel to <a href="%s" >contact us</a> if you have any question.', 'mail-control' ), mc_fs()->contact_url() ) . '</p></div>' ;
        }
        echo  '<div class="wrap">' ;
        echo  '<h1>' . __( 'Mail Control Settings', 'mail-control' ) . '</h1>' ;
        $this->show_navigation();
        $this->show_forms();
        echo  '</div>' ;
    }
    
    public function default_sanitization_error_message( $field_config )
    {
        return sprintf( __( 'Please insert a valid %s', 'mail-control' ), $field_config['type'] );
    }

}
add_action( 'plugins_loaded', function () {
    load_plugin_textdomain( 'mail-control', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    new Settings( apply_filters( 'mail_control_settings', [] ) );
} );