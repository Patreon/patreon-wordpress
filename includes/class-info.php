<?php

namespace patreon;

/**
 * The class containing informatin about the plugin.
 */
class Info
{
    /**
     * The plugin slug.
     *
     * @var string
     */
    const SLUG = 'patreon-plugin';

    /**
     * The plugin version.
     *
     * @var string
     */
    const VERSION = '0.0.01';

    /**
     * The nae for the entry in the options table.
     *
     * @var string
     */
    const OPTION_NAME = 'patreon';

    /**
     * The URL where your update server is located (uses wp-update-server).
     *
     * @var string
     */
    const UPDATE_URL = 'https://www.patreon.com/';

    /**
     * Retrieves the plugin title from the main plugin file.
     *
     * @return string The plugin title
     */
    public static function get_plugin_title() {
        $path = plugin_dir_path(dirname(__FILE__)).self::SLUG.'.php';
        return get_plugin_data($path)['Name'];
    }
}
