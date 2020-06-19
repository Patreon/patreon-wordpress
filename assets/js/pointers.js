;(function() {

	jQuery(document).ready( function($) {
		patreon_wordpress_open_pointer(0);
		function patreon_wordpress_open_pointer(i) {
			pointer = patreon_wordpress_pointer.pointers[i];
			options = jQuery.extend( pointer.options, {
				close: function() {
					jQuery.post( ajaxurl, {
						pointer: pointer.pointer_id,
						action: 'dismiss-wp-pointer'
					});
				}
			});
			jQuery(pointer.target).pointer( options ).pointer('open');
		}
	});
})()