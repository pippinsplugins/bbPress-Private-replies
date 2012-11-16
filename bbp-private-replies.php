<?php
/*
Plugin Name: bbPress - Private Replies
Plugin URL: http://pippinsplugins.com/bbpress-private-replies
Description: Allows users to set replies as private so that only the original poster and admins can see it
Version: 0.2
Author: Pippin Williamson and Remi Corson
Author URI: http://pippinsplugins.com
Contributors: mordauk, corsonr
Text Domain: bbp_private_replies
Domain Path: languages
*/

class BBP_Private_Replies {

	/*--------------------------------------------*
	 * Constructor
	 *--------------------------------------------*/

	/**
	 * Initializes the plugin by setting localization, filters, and administration functions.
	 */
	function __construct() {

		// load the plugin translation files
		add_action( 'init', array( $this, 'textdomain' ) );

		// show the "Private Reply?" checkbox
		add_action( 'bbp_theme_before_reply_form_subscription', array( $this, 'checkbox' ) );

		// save the private reply state
		add_action( 'bbp_new_reply',  array( $this, 'update_reply' ), 10, 6 );
		add_action( 'bbp_edit_reply',  array( $this, 'update_reply' ), 10, 6 );

		// hide reply content
		add_filter( 'bbp_get_reply_excerpt', array( $this, 'hide_reply' ), 0, 2 );
		add_filter( 'bbp_get_reply_content', array( $this, 'hide_reply' ), 0, 2 );

		// add a class name indicating the read status
		add_filter( 'post_class', array( $this, 'reply_post_class' ) );
		
		// register css files
		add_action( 'wp_enqueue_scripts', array( $this, 'register_plugin_styles' ) );

	} // end constructor


	/**
	 * Load the plugin's text domain
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function textdomain() {
		load_plugin_textdomain( 'bbp_private_replies', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}


	/**
	 * Outputs the "Set as private reply" checkbox
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function checkbox() {

?>
		<p>

			<input name="bbp_private_reply" id="bbp_private_reply" type="checkbox"<?php checked( '1', $this->is_private( bbp_get_reply_id() ) ); ?> value="1" tabindex="<?php bbp_tab_index(); ?>" />

			<?php if ( bbp_is_reply_edit() && ( get_the_author_meta( 'ID' ) != bbp_get_current_user_id() ) ) : ?>

				<label for="bbp_private_reply"><?php _e( 'Set author\'s post as private.', 'bbp_private_replies' ); ?></label>

			<?php else : ?>

				<label for="bbp_private_reply"><?php _e( 'Set as private reply', 'bbp_private_replies' ); ?></label>

			<?php endif; ?>

		</p>
<?php

	}


	/**
	 * Stores the private state on reply creation and edit
	 *
	 * @since 1.0
	 *
	 * @param $reply_id int The ID of the reply
	 * @param $topic_id int The ID of the topic the reply belongs to
	 * @param $forum_id int The ID of the forum the topic belongs to
	 * @param $anonymous_data bool Are we posting as an anonymous user?
	 * @param $author_id int The ID of user creating the reply, or the ID of the replie's author during edit
	 * @param $is_edit bool Are we editing a reply?
	 *
	 * @return void
	 */
	public function update_reply( $reply_id = 0, $topic_id = 0, $forum_id = 0, $anonymous_data = false, $author_id = 0, $is_edit = false ) {

		if( isset( $_POST['bbp_private_reply'] ) )
			update_post_meta( $reply_id, '_bbp_reply_is_private', '1' );
		else
			delete_post_meta( $reply_id, '_bbp_reply_is_private' );

	}


	/**
	 * Determines if a reply is marked as private
	 *
	 * @since 1.0
	 *
	 * @param $reply_id int The ID of the reply
	 *
	 * @return bool
	 */
	public function is_private( $reply_id = 0 ) {

		$retval 	= false;
	
		// Checking a specific reply id
		if ( !empty( $reply_id ) ) {
			$reply     = bbp_get_reply( $reply_id );
			$reply_id = !empty( $reply ) ? $reply->ID : 0;

		// Using the global reply id
		} elseif ( bbp_get_reply_id() ) {
			$reply_id = bbp_get_reply_id();

		// Use the current post id
		} elseif ( !bbp_get_reply_id() ) {
			$reply_id = get_the_ID();
		}

		if ( ! empty( $reply_id ) ) {
			$retval = get_post_meta( $reply_id, '_bbp_reply_is_private', true );
		}

		return (bool) apply_filters( 'bbp_reply_is_private', (bool) $retval, $reply_id );
	}


	/**
	 * Hides the reply content for users that do not have permission to view it
	 *
	 * @since 1.0
	 *
	 * @param $content string The content of the reply
	 * @param $reply_id int The ID of the reply
	 *
	 * @return string
	 */
	public function hide_reply( $content, $reply_id) {
		
		if( $this->is_private( $reply_id ) ) {

			$topic_author = bbp_get_topic_author_id();
			$reply_author = bbp_get_reply_author_id( $reply_id );

			if( $topic_author != bbp_get_current_user_id() && $reply_author != bbp_get_current_user_id() && !current_user_can( 'publish_forums' ) ) {

				$content = __( 'This reply has been marked as private.', 'bbp_private_replies' );

			}

		}

		return $content;
	}


	/**
	 * Adds a new class to replies that are marked as private
	 *
	 * @since 1.0
	 *
	 * @param $classes array An array of current class names
	 *
	 * @return bool
	 */
	public function reply_post_class( $classes ) {
		global $post;
		
		// only apply the class to replies
		if( bbp_get_reply_post_type() != get_post_type( $post ) )
			return $classes;

		if( $this->is_private( $post->ID ) )
			$classes[] = 'bbp-private-reply';

		return $classes;
	}
  
	/**
	 * Load the plugin's CSS files
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function register_plugin_styles() {
		$css_path = plugin_dir_path( __FILE__ ) . 'css/frond-end.css';
	    wp_enqueue_style( 'bbp_private_replies_style', plugin_dir_url( __FILE__ ) . 'css/frond-end.css', filemtime( $css_path ) );
	}

} // end class

// instantiate our plugin's class
$GLOBALS['bbp_private_replies'] = new BBP_Private_Replies();