<?php

// If this file is called directly, abort.
if (!defined('WPINC')) {
    exit;
}

class patreon_wordpress_login_widget extends WP_Widget
{
    public function __construct()
    {
        parent::__construct(
            'patreon_wordpress_login_widget', // Base ID
            PATREON_LOGIN_WIDGET_NAME, // Name
            ['description' => PATREON_LOGIN_WIDGET_DESC] // Args
        );
    }

    /** @see WP_Widget::widget -- do not rename this */
    public function widget($args, $instance)
    {
        extract($args);

        $title = apply_filters('widget_title', $instance['title']);
        $message = $instance['message'];

        echo $before_widget; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup provided by the theme

        if ($title) {
            echo $before_title.wp_kses_post($title).$after_title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup provided by the theme
        }
        if (isset($message) and '' != $message) {
            echo '<p>'.esc_html($message).'</p>';
        }

        echo Patreon_Frontend::login_widget(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML built and escaped by Patreon_Frontend

        echo $after_widget; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup provided by the theme
    }

    /** @see WP_Widget::update -- do not rename this */
    public function update($new_instance, $old_instance)
    {
        $instance = $old_instance;
        $instance['title'] = wp_strip_all_tags($new_instance['title']);
        $instance['message'] = wp_strip_all_tags($new_instance['message']);

        return $instance;
    }

    /** @see WP_Widget::form -- do not rename this */
    public function form($instance)
    {
        $instance = wp_parse_args((array) $instance, ['title' => PATREON_LOGIN_WIDGET_NAME, 'message' => '']);
        $title = $instance['title'];
        $message = $instance['message'];

        ?>
        <p>
          <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">Title:</label>
          <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
        </p>
        <p>
          <label for="<?php echo esc_attr($this->get_field_id('message')); ?>">Message over login button - optional</label>
          <input class="widefat" id="<?php echo esc_attr($this->get_field_id('message')); ?>" name="<?php echo esc_attr($this->get_field_name('message')); ?>" type="text" value="<?php echo esc_attr($message); ?>" />
        </p>
        <p>
          <?php echo Patreon_Frontend::login_widget(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML built and escaped by Patreon_Frontend?>
        </p>

        <?php
    }
}

function patreon_wordpress_register_widgets()
{
    register_widget('patreon_wordpress_login_widget');
}

add_action('widgets_init', 'patreon_wordpress_register_widgets');
