<?php

// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
	die;
}

class patreon_wordpress_login_widget extends WP_Widget {

    public function __construct() {
		
        parent::__construct(
            'patreon_wordpress_login_widget', // Base ID
             PATREON_LOGIN_WIDGET_NAME, // Name
            array( 'description' => PATREON_LOGIN_WIDGET_DESC ) // Args
        );
    }
 
    /** @see WP_Widget::widget -- do not rename this */
    function widget( $args, $instance ) {
	
        extract( $args );
		
        $title 		= apply_filters( 'widget_title', $instance['title'] );
		$message 	= $instance['message'];
		
		echo $before_widget;
		
		if ( $title ) {
			echo $before_title . $title . $after_title; 
		}
		if ( isset( $message ) AND $message != '' ) {
			echo '<p>'. $message . '</p>';
		}
		
		echo Patreon_Frontend::login_widget();
		
		echo $after_widget;
		
    }
 
    /** @see WP_Widget::update -- do not rename this */
    function update( $new_instance, $old_instance ) {		
		$instance = $old_instance;
		$instance['title']   = strip_tags( $new_instance['title'] );
		$instance['message'] = strip_tags( $new_instance['message'] );
        return $instance;
    }
 
    /** @see WP_Widget::form -- do not rename this */
    function form( $instance ) {
			
		$instance   = wp_parse_args( (array) $instance, array( 'title' => PATREON_LOGIN_WIDGET_NAME, 'message'=> '' ) );
        $title 		= esc_attr( $instance['title'] );
        $message	= esc_attr( $instance['message'] );
		
        ?>
        <p>
          <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
          <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
        </p>
		<p>
          <label for="<?php echo $this->get_field_id( 'message' ); ?>"><?php echo 'Message over login button - optional'; ?></label> 
          <input class="widefat" id="<?php echo $this->get_field_id( 'message' ); ?>" name="<?php echo $this->get_field_name( 'message' ); ?>" type="text" value="<?php echo $message ?>" />
        </p>
		<p>
          <?php echo Patreon_Frontend::login_widget(); ?>
        </p>		
		
        <?php 
    }
	
}

function patreon_wordpress_register_widgets() {
	register_widget( 'patreon_wordpress_login_widget' );
}

add_action( 'widgets_init', 'patreon_wordpress_register_widgets' );
