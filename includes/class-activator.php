<?php

namespace patreon;

/**
 * This class defines all code necessary to run during the plugin's activation.
 */
class Activator
{
    /**
     * Sets the default options in the options table on activation.
     */
    public function patreon_activation_redirect() {
        if ( get_option( 'patreon_activation_redirect', false ) ) {
            delete_option( 'patreon_activation_redirect' );

            if ( !isset( $_GET['activate-multi'] ) ) {
                wp_redirect( admin_url( 'options-general.php?page=memberful_options' ) );
            }
        }
    }
    public static function activate() {
        $option_name = INFO::OPTION_NAME;
        if (empty(get_option($option_name))) {
            $default_options = [
                'key' => 'value',
            ];
            update_option($option_name, $default_options);
        }
    }
}
