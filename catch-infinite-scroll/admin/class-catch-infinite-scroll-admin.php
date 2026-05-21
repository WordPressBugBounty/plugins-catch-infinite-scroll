<?php

// Exit if accessed directly
if (! defined('ABSPATH')) exit;


/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @link       catchplugins.com
 * @since      1.0.0
 * @package    Catch_Infinite_Scroll
 * @subpackage Catch_Infinite_Scroll/admin
 * @author     catchplugins.com <info@catchplugins.com>
 */
class Catch_Infinite_Scroll_Admin
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $CATCH_INFINITE_SCROLL    The ID of this plugin.
	 */
	private $CATCH_INFINITE_SCROLL;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;
	private $catch_infinite_scroll;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $CATCH_INFINITE_SCROLL       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($CATCH_INFINITE_SCROLL, $version)
	{

		$this->catch_infinite_scroll = $CATCH_INFINITE_SCROLL;
		$this->version               = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles($hook_suffix)
	{

		if ('toplevel_page_catch-infinite-scroll' === $hook_suffix) {

			wp_enqueue_style($this->catch_infinite_scroll, plugin_dir_url(__FILE__) . 'css/catch-infinite-scroll-admin.css', array(), $this->version, 'all');
			wp_enqueue_style($this->catch_infinite_scroll . '-tabs', plugin_dir_url(__FILE__) . 'css/admin-dashboard.css', array(), $this->version, 'all');
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts($hook_suffix)
	{

		if ('toplevel_page_catch-infinite-scroll' === $hook_suffix) {

			$defaults = catch_infinite_scroll_default_options();

			wp_enqueue_script($this->catch_infinite_scroll . '-match-height', plugin_dir_url(__FILE__) . 'js/jquery.matchHeight.min.js', array('jquery'), $this->version, true);

			wp_register_script($this->catch_infinite_scroll, plugin_dir_url(__FILE__) . 'js/catch-infinite-scroll-admin.js', array('jquery', 'jquery-ui-tooltip'), $this->version, true);

			wp_localize_script($this->catch_infinite_scroll, 'default_options', $defaults);

			wp_enqueue_script($this->catch_infinite_scroll);

			wp_enqueue_media();
		}
	}

	/**
	 * Catch Infinite Scroll: action_links
	 * Catch Infinite Scroll Settings Link function callback
	 *
	 * @param arrray $links Link url.
	 *
	 * @param arrray $file File name.
	 */
	public function action_links($links, $file)
	{
		if ($file === $this->catch_infinite_scroll . '/' . $this->catch_infinite_scroll . '.php') {
			$settings_link = '<a href="' . esc_url(admin_url('admin.php?page=catch-infinite-scroll')) . '">' . esc_html__('Settings', 'catch-infinite-scroll') . '</a>';

			array_unshift($links, $settings_link);
		}
		return $links;
	}

	/**
	 * Catch Infinite Scroll: add_plugin_settings_menu
	 * add Catch Infinite Scroll to menu
	 */
	public function add_plugin_settings_menu()
	{
		add_menu_page(
			esc_html__('Catch Infinite Scroll', 'catch-infinite-scroll'),
			esc_html__('Catch Infinite Scroll', 'catch-infinite-scroll'),
			'manage_options',
			'catch-infinite-scroll',
			array($this, 'settings_page'),
			'dashicons-update',
			'99.01564'
		);
	}

	/**
	 * Catch Infinite Scroll: catch_web_tools_settings_page
	 * Catch Infinite Scroll Setting function
	 */
	public function settings_page()
	{
		if (! current_user_can('manage_options')) {
			wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'catch-infinite-scroll'));
		}

		require plugin_dir_path(__FILE__) . 'partials/catch-infinite-scroll-admin-display.php';
	}

	/**
	 * Catch Infinite Scroll: register_settings
	 * Catch Infinite Scroll Register Settings
	 */
	public function register_settings()
	{
		register_setting(
			'catch-infinite-scroll-group',
			'catch_infinite_scroll_options',
			array($this, 'sanitize_callback')
		);
	}

	/**
	 * Catch Infinite Scroll: sanitize_callback
	 * Catch Infinite Scroll Sanitization function callback
	 *
	 * @param array $input Input data for sanitization.
	 */
	public function sanitize_callback($input)
	{
		$defaults = catch_infinite_scroll_default_options();

		// Bail early on autosave.
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return get_option('catch_infinite_scroll_options', $defaults);
		}

		// Verify the nonce before proceeding. Must come before reset check to
		// prevent CSRF from wiping settings with an invalid nonce.
		if (! isset($_POST['catch_infinite_scroll_nonce'])
			|| ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['catch_infinite_scroll_nonce'])), 'catch_infinite_scroll_options')
		) {
			return get_option('catch_infinite_scroll_options', $defaults);
		}

		// Reset check runs only after nonce is verified.
		if (isset($input['reset']) && $input['reset']) {
			return $defaults;
		}

		if (null !== $input) {

			// Validate trigger against the allowed values only.
			$valid_triggers   = array( 'scroll', 'click' );
			$input['trigger'] = (isset($input['trigger']) && in_array($input['trigger'], $valid_triggers, true))
				? sanitize_key($input['trigger'])
				: $defaults['trigger'];

			// Sanitize critical selectors; fall back to defaults when cleared
			// so a blank field never silently breaks the front end.
			if (isset($input['navigation_selector'])) {
				$input['navigation_selector'] = sanitize_text_field($input['navigation_selector']);
				if ('' === $input['navigation_selector']) {
					$input['navigation_selector'] = $defaults['navigation_selector'];
				}
			}

			if (isset($input['next_selector'])) {
				$input['next_selector'] = sanitize_text_field($input['next_selector']);
				if ('' === $input['next_selector']) {
					$input['next_selector'] = $defaults['next_selector'];
				}
			}

			if (isset($input['content_selector'])) {
				$input['content_selector'] = sanitize_text_field($input['content_selector']);
				if ('' === $input['content_selector']) {
					$input['content_selector'] = $defaults['content_selector'];
				}
			}

			if (isset($input['item_selector'])) {
				$input['item_selector'] = sanitize_text_field($input['item_selector']);
				if ('' === $input['item_selector']) {
					$input['item_selector'] = $defaults['item_selector'];
				}
			}

			if (isset($input['image'])) {
				$input['image'] = esc_url_raw($input['image']);
			}

			if (isset($input['load_more_text'])) {
				$input['load_more_text'] = sanitize_text_field($input['load_more_text']);
			}

			if (isset($input['finish_text'])) {
				$input['finish_text'] = sanitize_text_field($input['finish_text']);
			}
		}

		return $input;
	}

	function add_plugin_meta_links($meta_fields, $file)
	{
		if (CATCH_INFINITE_SCROLL_BASENAME == $file) {
			$allowed_tags = array(
				'a'       => array( 'href' => array(), 'target' => array(), 'title' => array() ),
				'i'       => array( 'class' => array() ),
				'svg'     => array( 'xmlns' => array(), 'width' => array(), 'height' => array(), 'viewbox' => array(), 'fill' => array(), 'stroke' => array(), 'stroke-width' => array(), 'stroke-linecap' => array(), 'stroke-linejoin' => array(), 'class' => array() ),
				'polygon' => array( 'points' => array() ),
			);

			$star_svg = "<svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg>";

			$meta_fields[] = wp_kses(
				"<a href='https://catchplugins.com/support-forum/forum/catch-infinite-scroll/' target='_blank'>Support Forum</a>",
				$allowed_tags
			);
			$meta_fields[] = wp_kses(
				"<a href='https://wordpress.org/support/plugin/catch-infinite-scroll/reviews#new-post' target='_blank' title='Rate'><i class='ct-rate-stars'>" . str_repeat($star_svg, 5) . '</i></a>',
				$allowed_tags
			);

			$stars_color = '#ffb900';

			echo '<style>'
				. '.ct-rate-stars{display:inline-block;color:' . esc_attr($stars_color) . ';position:relative;top:3px;}'
				. '.ct-rate-stars svg{fill:' . esc_attr($stars_color) . ';}'
				. '.ct-rate-stars svg:hover{fill:' . esc_attr($stars_color) . '}'
				. '.ct-rate-stars svg:hover ~ svg{fill:none;}'
				. '</style>';
		}

		return $meta_fields;
	}
}
