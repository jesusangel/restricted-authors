<?php
/**
 * Plugin Name: Restricted Authors.
 * Plugin URI:  https://iwritecode.blog/plugins/restricted-authors
 * Description: A plugin to set categories for authors and prevent them posting in others.
 * Author:      Chris Kelley
 * Author URI:  https://iwritecode.blog
 * Version:     1.1.0
 * Text Domain: restricted-authors
 * Domain Path: languages
 *
 * @package Restrict Authors.
 *
 * Restrict Authors. is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Restrict Authors. is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Restrict Authors.. If not, see <http://www.gnu.org/licenses/>.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core Class.
 */
final class Restricted_Authors {

	/**
	 * Holds Singleton
	 *
	 * @since 1.0.0
	 *
	 * @var object
	 */
	public static $instance = null;

	/**
	 * Undocumented variable
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $plugin_slug = 'restricted-authors';

	/**
	 * Plugin Version
	 *
	 * @var string
	 */
	public $version = '1.1.0';
	/**
	 * Undocumented variable
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $file = __FILE__;

	/**
	 * Cloning of this class isnt allowed
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheating huh?', 'restricted-authors' ), '1.0.0' );
	}

	/**
	 * Unserialized instances arent allowed.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheating huh?', 'restricted-authors' ), '1.0.0' );
	}

	/**
	 * Initialze method.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init() {

		add_action( 'show_user_profile', [ $this, 'profile_fields' ] );
		add_action( 'edit_user_profile', [ $this, 'profile_fields' ] );
		add_filter( 'get_terms_args', [ $this, 'restrict_user_terms' ], 12, 2 );
		add_action( 'admin_enqueue_scripts', [ $this, 'scripts_and_styles' ] );
		add_action( 'personal_options_update', [ $this, 'save_profile' ] );
		add_action( 'edit_user_profile_update', [ $this, 'save_profile' ] );
		add_filter( 'rest_category_query', [ $this, 'filter_rest' ], 10, 2 );
		add_filter( 'pre_option_default_category', [ $this, 'change_default_category' ] );

		add_action( 'init', [ $this, 'load_textdomain' ] );

		do_action( 'restricted_authors_init' );

	}

	/**
	 * Helper Method to load textdomain
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'restricted-authors', false, trailingslashit( dirname( plugin_basename( $this->file ) ) ) . 'languages' );
	}

	/**
	 * Helper Method to Alter Default id
	 *
	 * @param integer $id Default Id.
	 * @return integer
	 */
	public function change_default_category( $id ) {

		if ( ! is_user_logged_in() ) {
			return $id;
		}

		$user_id    = get_current_user_id();
		$default_id = get_user_meta( $user_id, '_restricted_authors_default_category', true );

		if ( $default_id ) {
			return $default_id;
		}

		return $id;

	}

	/**
	 * Modify the rest response to filter for gutenberg
	 *
	 * @since 1.0.0
	 *
	 * TODO: Look for a better method to handle this.
	 *
	 * @param array  $prepared_args Tax Args.
	 * @param object $request Requuest Object.
	 * @return array
	 */
	public function filter_rest( $prepared_args, $request ) {

		$current_user = wp_get_current_user();

		if ( 0 === $current_user->ID || in_array( 'administrator', $current_user->roles, true ) ) {
			return $prepared_args;
		}
		$referer = $request->get_header( 'referer' );
		$url     = wp_parse_url( $referer );

		if ( ! preg_match( '/wp-admin/', $url['path'] ) ) {
			return $prepared_args;
		}

		$restricted_categories    = get_user_meta( $current_user->ID, '_restricted_authors_restricted_category', true );
		$prepared_args['include'] = $restricted_categories;

		return $prepared_args;

	}

	/**
	 * Helper Method to load Scripts and Styles.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook Page hook.
	 * @return void
	 */
	public function scripts_and_styles( $hook ) {

		if ( in_array( $hook, [ 'profile.php', 'user-edit.php' ], true ) ) {
			wp_enqueue_script( 'restricted-choices', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'assets/js/choices.min.js', false, $this->version, false );
			wp_enqueue_style( 'restricted-admin-css', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'assets/css/restricted-admin.css', false, $this->version );
		}

	}
	/**
	 * Restrict Terms for users.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Tax Args..
	 * @param array $taxonomies Taxonimies.
	 *
	 * @return array
	 */
	public function restrict_user_terms( $args, $taxonomies ) {

		if ( ! is_admin() || 'category' !== $taxonomies[0] ) {

			return $args;

		}

		$current_user = wp_get_current_user();

		if ( in_array( 'administrator', $current_user->roles, true ) ) {
			return $args;
		}
		$restricted_categories = get_user_meta( $current_user->ID, '_restricted_authors_restricted_category', true );

		$args['include'] = $restricted_categories;

		return $args;
	}

	/**
	 * Modify the profile fields.
	 *
	 * @since 1.0.0
	 *
	 * @param object $user User Object.
	 * @return void
	 */
	public function profile_fields( $user ) {

		if ( ! current_user_can( 'edit_users' ) ) {
			return;
		}

		require_once plugin_dir_path( $this->file ) . 'includes/Views/profile-fields.php';

	}

	/**
	 * Helper Method for saving user profiles.
	 *
	 * @since 1.0.0
	 *
	 * @param intenger $user_id User ID of profile saving.
	 * @return void
	 */
	public function save_profile( $user_id ) {

		if ( ! current_user_can( 'edit_users' ) ) {
			return;
		}
		if ( ! isset( $_POST['restricted_authors_profile'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['restricted_authors_profile'] ) ), 'restricted_authors' ) ) {
			return;
		}

		$default_cat     = isset( $_POST['restricted_default'] ) ? sanitize_text_field( wp_unslash( $_POST['restricted_default'] ) ) : get_option( 'default_category' );
		$restricted_cats = isset( $_POST['restricted_categories'] ) ? wp_unslash( $_POST['restricted_categories'] ) : []; // @codingStandardsIgnoreLine

		update_user_meta( $user_id, '_restricted_authors_restricted_category', $restricted_cats );
		update_user_meta( $user_id, '_restricted_authors_default_category', $default_cat );
	}

	/**
	 * Gets Singleton Instance
	 *
	 * @since 1.0.0
	 *
	 * @return object|Restricted_Authors
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Restricted_Authors ) ) {

			self::$instance = new self();
			self::$instance->init();

		}

		return self::$instance;

	}

}

add_action(
	'plugins_loaded',
	function() {
		return Restricted_Authors::get_instance();
	}
);
