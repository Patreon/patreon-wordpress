<?php

namespace patreon;

/**
 * The main plugin class.
 */
class Plugin
{

    private $loader;
    private $plugin_slug;
    private $version;
    private $option_name;

    public function __construct() {
        $this->plugin_slug = Info::SLUG;
        $this->version     = Info::VERSION;
        $this->option_name = Info::OPTION_NAME;
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_frontend_hooks();
    }

    private function load_dependencies() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-admin.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'frontend/class-frontend.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-loader.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-patreon-posts.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-patreon-routing.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-patreon-routing.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-patreon-metabox.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-patreon-api.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'util.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-patreon-login.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'classes/patreon_wordpress.php';
        $this->loader = new Loader();
    }

    private function define_admin_hooks() {
        $plugin_admin = new Admin($this->plugin_slug, $this->version, $this->option_name);
        $plugin_metabox = new Patreon_Metabox($this->plugin_slug, $this->version, $this->option_name);
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'assets');
        $this->loader->add_action('admin_init', $plugin_admin, 'patreon_plugin_register_settings' );
        $this->loader->add_action('admin_menu', $plugin_admin, 'patreon_plugin_setup_page');
        $this->loader->add_action('load-post.php', $plugin_metabox, 'patreon_plugin_meta_boxes_setup' );
        $this->loader->add_action('load-post-new.php', $plugin_metabox, 'patreon_plugin_meta_boxes_setup' );

    }

    private function define_frontend_hooks() {
        // $plugin_frontend = new Frontend($this->plugin_slug, $this->version, $this->option_name);
        new Patreon_Routing;
		new Patreon_Frontend;
        new Patreon_Posts;
        // $this->loader->add_action('wp_enqueue_scripts', $plugin_frontend, 'assets');
        // $this->loader->add_action('wp_footer', $plugin_frontend, 'render');
    }

    public function run() {
        $this->loader->run();
    }
}
