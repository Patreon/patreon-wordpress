<?php

class PatreonApiUtil
{
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
