<?php

namespace Mail_Control;

/**
 * Get the plugin setting url.
 *
 * @return     string  The section url.
 */
function settings_url( $hash = null )
{
    return admin_url( 'admin.php?page=mail-control-settings' );
}


if ( !function_exists( 'mc_fs' ) ) {
    /**
     * Create a helper function for easy SDK access
     */
    function mc_fs()
    {
        global  $mc_fs ;
        
        if ( !isset( $mc_fs ) ) {
            // Activate multisite network integration.
            if ( !defined( 'WP_FS__PRODUCT_11451_MULTISITE' ) ) {
                define( 'WP_FS__PRODUCT_11451_MULTISITE', true );
            }
            // Include Freemius SDK.
            require_once MC_VENDOR . 'freemius/wordpress-sdk/start.php';
            $mc_fs = fs_dynamic_init( array(
                'id'             => '11451',
                'slug'           => 'mail-control',
                'type'           => 'plugin',
                'public_key'     => 'pk_8e53c9c66edde29bd65f424ee62a8',
                'is_premium'     => false,
                'premium_suffix' => 'Pro',
                'has_addons'     => false,
                'has_paid_plans' => true,
                'trial'          => array(
                'days'               => 14,
                'is_require_payment' => true,
            ),
                'menu'           => array(
                'slug'       => 'mail-control',
                'first-path' => 'admin.php?page=mail-control-settings&welcome-message=true',
                'support'    => false,
            ),
                'is_live'        => true,
            ) );
        }
        
        return $mc_fs;
    }
    
    function mc_fs_custom_connect_message_on_update(
        $message,
        $user_first_name,
        $plugin_title,
        $user_login,
        $site_link,
        $freemius_link
    )
    {
        return sprintf(
            __( 'Hey %1$s' ) . ',<br>' . __( 'Please help us improve %2$s! If you opt-in, some data about your usage of %2$s will be sent to %5$s. If you skip this, that\'s okay! %2$s will still work just fine.', 'mail-control' ),
            $user_first_name,
            '<b>' . $plugin_title . '</b>',
            '<b>' . $user_login . '</b>',
            $site_link,
            $freemius_link
        );
    }
    
    // Init Freemius.
    $mc_fs = mc_fs();
    // customize opt-in message
    $mc_fs->add_filter(
        'connect_message_on_update',
        __NAMESPACE__ . '\\mc_fs_custom_connect_message_on_update',
        10,
        6
    );
    // Signal that SDK was initiated.
    do_action( 'mc_fs_loaded' );
}
