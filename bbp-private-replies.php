<?php
/*
Plugin Name: bbPress - Private Replies
Plugin URL: http://pippinsplugins.com/bbpress-private-replies
Description: Allows users to set replies as private so that only the original poster and admins can see it
Version: 1.4.0
Author: Pippin Williamson, Remi Corson, David Anderson
Author URI: http://pippinsplugins.com
Contributors: mordauk, corsonr, DavidAnderson
Text Domain: bbp_private_replies
Domain Path: languages
*/

if ( !defined( 'ABSPATH' ) ) die( 'No direct access.' );

class BBP_Private_Replies {

	/**
	 * The capability required to view private posts.
	 *
	 * @since 1.3.3
	 *
	 * @var string $capability
	 */
	public $capability = 'moderate';

	/**
	 * Post IDs that are moderator-only.
	 *
	 * @since 1.4.0
	 *
	 * @var array $moderator_only_posts
	 */
	private $moderator_only_posts = array();

	/*--------------------------------------------*
	 * Constructor
	 *--------------------------------------------*/

	/**
	 * Initializes the plugin by setting localization, filters, and administration functions.
	 */
	public function __construct() {

		// load the plugin translation files
		add_action( 'init', array( $this, 'textdomain' ) );

		// Allow others to change the capability required to view private posts.
		add_action( 'plugins_loaded', array( $this, 'filter_capability' ) );

		// show the "Private Reply?" checkbox
		add_action( 'bbp_theme_before_reply_form_submit_wrapper', array( $this, 'checkbox' ) );

		// save the private reply state
		add_action( 'bbp_new_reply',  array( $this, 'update_reply' ), 0, 6 );
		add_action( 'bbp_edit_reply',  array( $this, 'update_reply' ), 0, 6 );

		// hide reply content
		add_filter( 'bbp_get_reply_excerpt', array( $this, 'hide_reply' ), 999, 2 );
		add_filter( 'bbp_get_reply_content', array( $this, 'hide_reply' ), 999, 2 );
		add_filter( 'the_content', array( $this, 'hide_reply' ), 999 );
		add_filter( 'the_excerpt', array( $this, 'hide_reply' ), 999 );

		// prevent private replies from being sent in email subscriptions
		add_filter( 'bbp_subscription_mail_message', array( $this, 'prevent_subscription_email' ), 999999, 3 );

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
	 * Called during the plugins_loaded action to filter the capability
	 * required to view private replies.
	 *
	 * @since 1.3.3
	 *
	 * @return void
	 */
	public function filter_capability() {
		$this->capability = apply_filters( 'bbp_private_replies_capability', $this->capability );
	}

	/**
	 * Retrieves the no reply address.
	 *
	 * @since 1.3.3
	 *
	 * @return string
	 */
	public function get_no_reply() {
		return apply_filters( 'bbp_private_replies_no_reply_address', bbp_get_do_not_reply_address() );
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
		
		<?php if ( !current_user_can( $this->capability ) ) return; ?>
		
		<p>

			<input name="bbp_moderator_only_reply" id="bbp_moderator_only_reply" type="checkbox"<?php checked( '1', $this->is_moderator_only( bbp_get_reply_id() ) ); ?> value="1" tabindex="<?php bbp_tab_index(); ?>" />

			<?php if ( bbp_is_reply_edit() && ( get_the_author_meta( 'ID' ) != bbp_get_current_user_id() ) ) : ?>

				<label for="bbp_moderator_only_reply"><?php _e( 'Set author\'s post as moderator-only.', 'bbp_moderator_only_replies' ); ?></label>

			<?php else : ?>

				<label for="bbp_moderator_only_reply"><?php _e( 'Set as moderator-only reply', 'bbp_moderator_only_replies' ); ?></label>

			<?php endif; ?>

		</p>
		
<?php

	}


	/**
	 * Stores the private / moderator-only state on reply creation and edit
	 *
	 * @since 1.0
	 *
	 * @param $reply_id int The ID of the reply
	 * @param $topic_id int The ID of the topic the reply belongs to
	 * @param $forum_id int The ID of the forum the topic belongs to
	 * @param $anonymous_data bool Are we posting as an anonymous user?
	 * @param $author_id int The ID of user creating the reply, or the ID of the reply's author during edit
	 * @param $is_edit bool Are we editing a reply?
	 *
	 * @return void
	 */
	public function update_reply( $reply_id = 0, $topic_id = 0, $forum_id = 0, $anonymous_data = false, $author_id = 0, $is_edit = false ) {

		if( isset( $_POST['bbp_private_reply'] ) )
			update_post_meta( $reply_id, '_bbp_reply_is_private', '1' );
		else
			delete_post_meta( $reply_id, '_bbp_reply_is_private' );

		if ( ! current_user_can( $this->capability ) ) return;
			
		if( isset( $_POST['bbp_moderator_only_reply'] ) )
			update_post_meta( $reply_id, '_bbp_reply_is_moderator_only', '1' );
		else
			delete_post_meta( $reply_id, '_bbp_reply_is_moderator_only' );

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
	 * Determines if a reply is marked as moderator-only
	 *
	 * @since 1.4.0
	 *
	 * @param $reply_id int The ID of the reply
	 *
	 * @return bool
	 */
	public function is_moderator_only( $reply_id = 0 ) {

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
			$retval = get_post_meta( $reply_id, '_bbp_reply_is_moderator_only', true );
		}

		return (bool) apply_filters( 'bbp_reply_is_moderator_only', (bool) $retval, $reply_id );
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
	public function hide_reply( $content = '', $reply_id = 0 ) {

		if( empty( $reply_id ) )
			$reply_id = bbp_get_reply_id( $reply_id );

		if( $this->is_moderator_only( $reply_id ) ) {

			$can_view     = false;

			if( current_user_can( $this->capability ) ) {
				// Let moderators view all replies
				$can_view = true;
			}

			if( ! $can_view ) {
				$content = __( 'This reply has been marked as moderator-only.', 'bbp_private_replies' );
			}

		} elseif ( $this->is_private( $reply_id ) ) {

			$can_view     = false;
			$current_user = is_user_logged_in() ? wp_get_current_user() : false;
			$topic_author = bbp_get_topic_author_id();
			$reply_author = bbp_get_reply_author_id( $reply_id );

			if ( ! empty( $current_user ) && $topic_author === $current_user->ID && user_can( $reply_author, $this->capability ) ) {
				// Let the thread author view replies if the reply author is from a moderator
				$can_view = true;
			}

			if ( ! empty( $current_user ) && $reply_author === $current_user->ID ) {
				// Let the reply author view their own reply
				$can_view = true;
			}

			if( current_user_can( $this->capability ) ) {
				// Let moderators view all replies
				$can_view = true;
			}

			if( ! $can_view ) {
				$content = __( 'This reply has been marked as private.', 'bbp_private_replies' );
			}
		}

		return $content;
	}


	/**
	 * Prevents a New Reply notification from being sent if the user doesn't have permission to view it
	 *
	 * @since 1.0
	 *
	 * @param $message string The email message
	 * @param $reply_id int The ID of the reply
	 * @param $topic_id int The ID of the reply's topic
	 *
	 * @return mixed
	 */
	public function prevent_subscription_email( $message, $reply_id, $topic_id ) {

		if( $this->is_private( $reply_id ) || $this->is_moderator_only( $reply_id ) ) {
			$this->subscription_email( $message, $reply_id, $topic_id );
			return false;
		}

		return $message; // message unchanged
	}


	/**
	 * Sends the new reply notification email to moderators on private replies
	 *
	 * @since 1.2
	 *
	 * @param $message string The email message
	 * @param $reply_id int The ID of the reply
	 * @param $topic_id int The ID of the reply's topic
	 *
	 * @return void
	 */
	public function subscription_email( $message, $reply_id, $topic_id ) {

		if( ! $this->is_private( $reply_id ) ) {

			return false; // reply isn't private so do nothing

		}

		$topic_author      = bbp_get_topic_author_id( $topic_id );
		$reply_author      = bbp_get_reply_author_id( $reply_id );
		$reply_author_name = bbp_get_reply_author_display_name( $reply_id );

		// Strip tags from text and setup mail data
		$topic_title   = strip_tags( bbp_get_topic_title( $topic_id ) );
		$reply_content = strip_tags( bbp_get_reply_content( $reply_id ) );
		$reply_url     = bbp_get_reply_url( $reply_id );
		$blog_name     = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

		$subject = apply_filters( 'bbp_subscription_mail_title', '[' . $blog_name . '] ' . $topic_title, $reply_id, $topic_id );

		// Array to hold BCC's
		$headers = array();

		// Setup the From header
		$headers[] = 'From: ' . get_bloginfo( 'name' ) . ' <' . $this->get_no_reply() . '>';

		// Get topic subscribers and bail if empty
		$user_ids = bbp_get_topic_subscribers( $topic_id, true );
		if ( empty( $user_ids ) ) {
			return false;
		}

		// Loop through users
		foreach ( (array) $user_ids as $user_id ) {

			// Don't send notifications to the person who made the post
			if ( ! empty( $reply_author ) && (int) $user_id === (int) $reply_author ) {
				continue;
			}

			$should_notify_op = user_can( $reply_author, $this->capability ) && (int) $topic_author === (int) $user_id;

			if( user_can( $user_id, $this->capability ) || $should_notify_op ) {

				// Get email address of subscribed user
				$headers[] = 'Bcc: ' . get_userdata( $user_id )->user_email;

			}
		}

		wp_mail( $this->get_no_reply(), $subject, $message, $headers );
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

		$reply_id = bbp_get_reply_id();

		// only apply the class to replies
		if( bbp_get_reply_post_type() != get_post_type( $reply_id ) )
			return $classes;

		static $added_footer_js = false;
			
		if( $this->is_moderator_only( $reply_id ) ) {
			$classes[] = 'bbp-moderator-only-reply';
			if ( current_user_can( $this->capability ) ) {
				$classes[] = 'is-moderator';
			} else {
				$classes[] = 'not-moderator';
				$this->moderator_only_posts[] = $reply_id;
				if ( ! $added_footer_js ) {
					add_action( 'wp_footer', array ( $this, 'wp_footer' ) );
					$added_footer_js = true;
				}
			}
		}

		if( $this->is_private( $reply_id ) )
			$classes[] = 'bbp-private-reply';

		return $classes;
	}

	/**
	 * Add JavaScript to hide the elements that cannot be hidden with CSS
	 *
	 * @since 1.4.0
	 *
	 * @return void
	 */
	public function wp_footer() {

		if ( empty( $this->moderator_only_posts ) ) return;
		
		?><script>
		<?php
		
		foreach ($this->moderator_only_posts as $post_id) {
			echo "document.getElementById('post-{$post_id}').style.visibility = 'hidden';\n";
		}
		
		?></script>
		<?php
	
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
