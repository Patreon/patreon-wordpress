<?php

/*
Plugin Name: Patreon
Plugin URI:
Description: Stay close with the Artists & Creators you're supporting
Version: 1.0
Author: Ben Parry
Author URI: http://uiux.me
*/

namespace patreon;

class Admin
{
    private $plugin_slug;
    private $version;
    private $option_name;
    private $settings;
    private $settings_group;
    private $creator_id;

    public function __construct($plugin_slug, $version, $option_name) {
        $this->plugin_slug = $plugin_slug;
        $this->version = $version;
        $this->option_name = $option_name;
        $this->settings = get_option($this->option_name);
        $this->settings_group = $this->option_name.'_group';
        $this->creator_id = false;
    }

    /**
     * Generate settings fields by passing an array of data (see the render method).
     *
     * @param array $field_args The array that helps build the settings fields
     * @param array $settings   The settings array from the options table
     *
     * @return string The settings fields' HTML to be output in the view
     */
    private function custom_settings_fields($field_args, $settings) {
        $output = '';

        foreach ($field_args as $field) {
            $slug = $field['slug'];
            $setting = $this->option_name.'['.$slug.']';
            $label = esc_attr__($field['label'], 'official-patreon-plugin');
            $output .= '<h3><label for="'.$setting.'">'.$label.'</label></h3>';

            if ($field['type'] === 'text') {
                $output .= '<p><input type="text" id="'.$setting.'" name="'.$setting.'" value="'.$settings[$slug].'"></p>';
            } elseif ($field['type'] === 'textarea') {
                $output .= '<p><textarea id="'.$setting.'" name="'.$setting.'" rows="10">'.$settings[$slug].'</textarea></p>';
            }
        }

        return $output;
    }

    public function assets() {
        wp_enqueue_style($this->plugin_slug, plugin_dir_url(__FILE__).'css/official-patreon-plugin-admin.css', [], $this->version);
        wp_enqueue_script($this->plugin_slug, plugin_dir_url(__FILE__).'js/official-patreon-plugin-admin.js', ['jquery'], $this->version, true);
    }

    public function patreon_plugin_register_settings() { // whitelist options
    	register_setting( 'patreon-options', 'patreon-client-id' );
        register_setting( 'patreon-options', 'patreon-client-secret' );
        register_setting( 'patreon-options', 'patreon-creators-access-token' );
        register_setting( 'patreon-options', 'patreon-creators-refresh-token' );
        register_setting( 'patreon-options', 'patreon-creator-id' );
        register_setting( 'patreon-options', 'patreon-paywall-img-url' );
        register_setting( 'patreon-options', 'patreon-rewrite-rules-flushed' );
    }

    public function patreon_plugin_setup(){
        add_menu_page( 'Patreon Settings', 'Patreon Settings', 'manage_options', $this->plugin_slug, array(&$this, 'render'));
    }

    public function patreon_plugin_setup_page(){
        /* update Patreon creator ID on page load */
        if(get_option('patreon-client-id', false) && get_option('patreon-client-secret', false) && get_option('patreon-creators-access-token', false)) {
            $creator_id = Patreon_Wordpress::getPatreonCreatorID();
            if($creator_id != false) {
                update_option( 'patreon-creator-id', $creator_id );
            }
            $this->patreon_plugin_setup();
        } else {
            $creator_id = Patreon_Wordpress::getPatreonCreatorID();
            $this->patreon_plugin_setup();
        }

    }

    public function render() {
        /* update Patreon creator ID on page load */

        // View
        $creator_id = get_option('patreon-creator-id', '');
        // Model
        require_once plugin_dir_path(dirname(__FILE__)).'admin/partials/options.php';
    }
}

?>
