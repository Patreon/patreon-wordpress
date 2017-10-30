<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
class Patreon_Welcome {
    function __construct() {
        add_action( 'admin_init', array($this, 'welcome_screen_do_activation_redirect'));
        add_action('admin_menu', array($this, 'welcome_screen_pages'));
        add_action( 'admin_head', array($this, 'welcome_screen_remove_menus'));
    }

    public function welcome_screen_activate() {
      set_transient( '_welcome_screen_activation_redirect', true, 30 );
    }

    public function welcome_screen_do_activation_redirect() {
      // Bail if no activation redirect
        if ( ! get_transient( '_welcome_screen_activation_redirect' ) ) {
        return;
      }

      // Delete the redirect transient
      delete_transient( '_welcome_screen_activation_redirect' );

      // Bail if activating from network, or bulk
      if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
        return;
      }

      // Redirect to bbPress about page
      wp_safe_redirect( add_query_arg( array( 'page' => 'welcome-screen-about' ), admin_url( 'index.php' ) ) );

    }



    public function welcome_screen_pages() {
      add_dashboard_page(
        'Welcome To Patreon Wordpress',
        'Welcome To Patreon Wordpress',
        'read',
        'welcome-screen-about',
        array($this, 'welcome_screen_content')
      );
    }

    public function welcome_screen_content() {
      ?>
      <div class="wrap">
        <h2>Welcome to Patreon Wordpress</h2>

        <p>
          We're excited for you to start using our plugin to deliver patron-only content on your wordpress page.
        </p>
		<h3>Getting Started:</h3>
		<p>There are a few things you need to do to get started.</p>
		<ol>
			<li><a href="https://www.patreon.com/portal/registration/register-clients" target="_blank">Register a Client</a> on Patreon</li>
			<li>Visit the <a href="/admin.php?page=patreon-plugin">Patreon Settings</a> in your WP Admin Panel and configure the plugin </li>
			<li>Create your first Patron Only Post</li>
		</ol>
      </div>
      <?php
    }
    public function welcome_screen_remove_menus() {
        remove_submenu_page( 'patreon.php', 'welcome-screen-about' );
    }
}

?>
