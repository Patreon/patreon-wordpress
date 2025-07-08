<?php

class PatreonApiUtil
{
    public const CHECK_API_CONNECTION_COOLDOWN_KEY = 'patreon-check-api-connection-cooldown';

    public static function get_default_headers()
    {
        return ['User-Agent' => self::get_patreon_ua()];
    }

    public static function is_app_creds_invalid()
    {
        return get_option('patreon-wordpress-app-credentials-failure', false);
    }

    public static function is_creator_token_refresh_cooldown()
    {
        return get_transient('patreon-wordpress-app-creator-token-refresh-cooldown');
    }

    public static function get_check_api_connection_cooldown()
    {
        return get_transient(self::CHECK_API_CONNECTION_COOLDOWN_KEY);
    }

    public static function set_check_api_connection_cooldown()
    {
        set_transient(self::CHECK_API_CONNECTION_COOLDOWN_KEY, true, PATREON_CHECK_API_CONNECTION_COOLDOWN_S);
    }

    private static function get_patreon_ua()
    {
        $campaign_id = get_option('patreon-campaign-id', '?');
        $php_version = phpversion();
        $platform = php_uname('s').'-'.php_uname('r');
        $plugin_version = PATREON_WORDPRESS_VERSION.PATREON_WORDPRESS_BETA_STRING;
        $site_url = get_site_url();
        $wp_version = get_bloginfo('version');

        return 'Patreon-Wordpress, version '.$plugin_version.', platform '.$platform.' PW-Site: '.$site_url.' PW-Campaign-Id: '.$campaign_id.' PW-WP-Version: '.$wp_version.' PW-PHP-Version: '.$php_version;
    }
}
