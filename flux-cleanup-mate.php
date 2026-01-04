<?php
/**
 * Plugin Name: Flux Cleanup Mate
 * Plugin URI: https://github.com/ogichanchan/flux-cleanup-mate
 * Description: A unique PHP-only WordPress utility. A flux style cleanup plugin acting as a mate. Focused on simplicity and efficiency.
 * Version: 1.0.0
 * Author: ogichanchan
 * Author URI: https://github.com/ogichanchan
 * License: GPLv2 or later
 * Text Domain: flux-cleanup-mate
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || die( 'No direct script access allowed.' );

if ( ! class_exists( 'Flux_Cleanup_Mate' ) ) {

	/**
	 * Flux_Cleanup_Mate class.
	 *
	 * A unique PHP-only WordPress utility. A flux style cleanup plugin acting as a mate.
	 * Focused on simplicity and efficiency.
	 */
	class Flux_Cleanup_Mate {

		/**
		 * The single instance of the class.
		 *
		 * @var Flux_Cleanup_Mate
		 */
		protected static $instance = null;

		/**
		 * Plugin options.
		 *
		 * @var array
		 */
		protected $options;

		/**
		 * Constructor.
		 */
		private function __construct() {
			$this->options = get_option( 'flux_cleanup_mate_options', $this->get_default_options() );

			$this->setup_hooks();
		}

		/**
		 * Ensures only one instance of the plugin is loaded or can be loaded.
		 *
		 * @return Flux_Cleanup_Mate
		 */
		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Get default options.
		 *
		 * @return array
		 */
		protected function get_default_options() {
			return array(
				'disable_emojis'            => true,
				'disable_rsd_wlw'           => true,
				'disable_shortlink'         => true,
				'disable_generator_tag'     => true,
				'disable_feed_links'        => true,
				'disable_rest_api_links'    => true,
				'disable_oembed'            => true,
				'disable_xmlrpc'            => false, // Can cause issues for some integrations. Default off.
				'disable_comment_reply_js'  => true, // Only loads on singular. Good to disable by default.
				'remove_dashboard_widgets'  => array(), // Store array of widget IDs to remove.
				'control_revisions'         => false,
				'revisions_to_keep'         => 5,
				'disable_heartbeat'         => false,
				'disable_admin_bar_front'   => false, // Remove admin bar for non-admins on front-end.
				'remove_jquery_migrate'     => true, // jQuery Migrate is often not needed.
			);
		}

		/**
		 * Set up plugin hooks.
		 */
		protected function setup_hooks() {
			// Always apply core cleanup actions if options are true.
			if ( $this->options['disable_emojis'] ) {
				add_action( 'init', array( $this, 'disable_emojis' ) );
			}

			if ( $this->options['disable_rsd_wlw'] ) {
				remove_action( 'wp_head', 'rsd_link' );
				remove_action( 'wp_head', 'wlwmanifest_link' );
			}

			if ( $this->options['disable_shortlink'] ) {
				remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
			}

			if ( $this->options['disable_generator_tag'] ) {
				remove_action( 'wp_head', 'wp_generator' );
				add_filter( 'the_generator', '__return_empty_string' ); // For RSS/Atom feeds.
			}

			if ( $this->options['disable_feed_links'] ) {
				remove_action( 'wp_head', 'feed_links', 2 );
				remove_action( 'wp_head', 'feed_links_extra', 3 );
			}

			if ( $this->options['disable_rest_api_links'] ) {
				remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
				remove_action( 'wp_head', 'wp_resource_hints', 2 ); // Remove DNS prefetch/preconnect for REST API.
			}

			if ( $this->options['disable_oembed'] ) {
				add_action( 'init', array( $this, 'disable_oembed' ) );
			}

			if ( $this->options['disable_xmlrpc'] ) {
				add_filter( 'xmlrpc_enabled', '__return_false' );
				// Also disable X-Pingback header to further reduce XML-RPC exposure.
				add_filter( 'wp_headers', array( $this, 'remove_x_pingback_header' ) );
			}

			if ( $this->options['disable_comment_reply_js'] ) {
				add_action( 'wp_enqueue_scripts', array( $this, 'remove_comment_reply_script' ) );
			}

			if ( ! empty( $this->options['remove_dashboard_widgets'] ) ) {
				add_action( 'wp_dashboard_setup', array( $this, 'remove_dashboard_widgets' ) );
			}

			if ( $this->options['control_revisions'] ) {
				add_filter( 'wp_revisions_to_keep', array( $this, 'set_revisions_to_keep' ), 10, 2 );
			}

			if ( $this->options['disable_heartbeat'] ) {
				add_action( 'init', array( $this, 'disable_heartbeat' ) );
			}

			if ( $this->options['disable_admin_bar_front'] ) {
				if ( ! current_user_can( 'manage_options' ) ) { // Only disable for non-admins.
					add_filter( 'show_admin_bar', '__return_false' );
				}
			}

			if ( $this->options['remove_jquery_migrate'] ) {
				add_action( 'wp_enqueue_scripts', array( $this, 'remove_jquery_migrate' ) );
			}

			// Admin menu and settings.
			add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
		}

		/**
		 * Remove jQuery Migrate.
		 *
		 * @return void
		 */
		public function remove_jquery_migrate() {
			if ( ! is_admin() ) {
				wp_deregister_script( 'jquery-migrate' );
			}
		}

		/**
		 * Disable emojis.
		 */
		public function disable_emojis() {
			remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
			remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
			remove_action( 'wp_print_styles', 'print_emoji_styles' );
			remove_action( 'admin_print_styles', 'print_emoji_styles' );
			remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
			remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
			remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
			add_filter( 'tiny_mce_plugins', array( $this, 'disable_emojis_tinymce' ) );
		}

		/**
		 * Filter function used to remove the tinymce emoji plugin.
		 *
		 * @param array $plugins Plugins list.
		 * @return array         Filtered plugins list.
		 */
		public function disable_emojis_tinymce( $plugins ) {
			if ( is_array( $plugins ) ) {
				return array_diff( $plugins, array( 'wpemoji' ) );
			}
			return $plugins;
		}

		/**
		 * Disable oEmbeds.
		 */
		public function disable_oembed() {
			remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
			remove_action( 'wp_head', 'wp_oembed_add_host_js' );
			add_filter( 'embed_oembed_discover', '__return_false' );
			remove_filter( 'rest_endpoints', 'wp_oembed_unregister_route' );

			// Remove all oEmbed rewrite rules.
			add_filter( 'rewrite_rules_array', array( $this, 'disable_embed_rewrite_rules' ) );

			// Remove oEmbed-specific JavaScript from the front-end.
			remove_action( 'wp_enqueue_scripts', 'wp_embed_register_scripts' );
			add_action( 'wp_footer', array( $this, 'dequeue_wp_embed_script' ) );
		}

		/**
		 * Dequeue wp-embed script if it's still somehow enqueued.
		 */
		public function dequeue_wp_embed_script() {
			wp_dequeue_script( 'wp-embed' );
		}

		/**
		 * Disable oEmbed rewrite rules.
		 *
		 * @param array $rules Existing rewrite rules.
		 * @return array Filtered rewrite rules.
		 */
		public function disable_embed_rewrite_rules( $rules ) {
			foreach ( $rules as $rule => $rewrite ) {
				if ( false !== strpos( $rewrite, 'embed=true' ) ) {
					unset( $rules[ $rule ] );
				}
			}
			return $rules;
		}

		/**
		 * Remove X-Pingback header.
		 *
		 * @param array $headers The HTTP headers.
		 * @return array Filtered headers.
		 */
		public function remove_x_pingback_header( $headers ) {
			unset( $headers['X-Pingback'] );
			return $headers;
		}

		/**
		 * Remove comment-reply.js if not on singular posts.
		 */
		public function remove_comment_reply_script() {
			if ( ! is_singular() ) {
				wp_dequeue_script( 'comment-reply' );
			}
		}

		/**
		 * Remove selected dashboard widgets.
		 */
		public function remove_dashboard_widgets() {
			$widgets_to_remove = $this->options['remove_dashboard_widgets'];

			if ( ! empty( $widgets_to_remove ) ) {
				// Common widget IDs (context: normal, side, column3, column4).
				$dashboard_widgets = array(
					'dashboard_right_now'   => array( 'dashboard', 'normal' ), // At a Glance
					'dashboard_activity'    => array( 'dashboard', 'normal' ), // Activity
					'dashboard_primary'     => array( 'dashboard', 'normal' ), // WordPress Events and News
					'dashboard_quick_press' => array( 'dashboard', 'side' ),   // Quick Draft
					'dashboard_browser_nag' => array( 'dashboard', 'normal' ), // Browser update nags.
					'dashboard_incoming_links' => array( 'dashboard', 'normal' ), // Deprecated, but for completeness.
					'dashboard_plugins'     => array( 'dashboard', 'normal' ), // Deprecated.
					'dashboard_recent_drafts' => array( 'dashboard', 'side' ),
					'dashboard_recent_comments' => array( 'dashboard', 'normal' ),
					'dashboard_site_health' => array( 'dashboard', 'normal' ),
				);

				foreach ( $widgets_to_remove as $widget_id ) {
					if ( isset( $dashboard_widgets[ $widget_id ] ) ) {
						remove_meta_box( $widget_id, $dashboard_widgets[ $widget_id ][0], $dashboard_widgets[ $widget_id ][1] );
					} else {
						// For custom widgets, assume default context 'normal' if not specified.
						// This might not cover all custom widget contexts but handles common cases.
						remove_meta_box( $widget_id, 'dashboard', 'normal' );
						remove_meta_box( $widget_id, 'dashboard', 'side' );
					}
				}
			}
		}

		/**
		 * Set the number of revisions to keep.
		 *
		 * @param int      $num     Number of revisions to keep.
		 * @param WP_Post  $post    The post object.
		 * @return int              Filtered number of revisions.
		 */
		public function set_revisions_to_keep( $num, $post ) {
			return (int) $this->options['revisions_to_keep'];
		}

		/**
		 * Disable Heartbeat API.
		 */
		public function disable_heartbeat() {
			if ( ! is_admin() && ! current_user_can( 'edit_posts' ) ) { // Only disable for non-logged-in/non-editor users on front-end.
				wp_deregister_script( 'heartbeat' );
			} elseif ( is_admin() ) {
				// For admin area, restrict frequency.
				add_filter( 'heartbeat_settings', array( $this, 'heartbeat_set_frequency' ) );
			}
		}

		/**
		 * Sets Heartbeat API frequency.
		 *
		 * @param array $settings Heartbeat settings.
		 * @return array Filtered settings.
		 */
		public function heartbeat_set_frequency( $settings ) {
			// Set to 60 seconds (default is 15 seconds for post edit, 30 for dashboard).
			$settings['interval'] = 60;
			return $settings;
		}

		/**
		 * Add admin menu page.
		 */
		public function add_admin_menu() {
			add_options_page(
				esc_html__( 'Flux Cleanup Mate Settings', 'flux-cleanup-mate' ),
				esc_html__( 'Flux Cleanup Mate', 'flux-cleanup-mate' ),
				'manage_options',
				'flux-cleanup-mate',
				array( $this, 'settings_page_html' )
			);
		}

		/**
		 * Register settings.
		 */
		public function register_settings() {
			register_setting(
				'flux_cleanup_mate_options_group', // Option group.
				'flux_cleanup_mate_options',       // Option name.
				array( $this, 'sanitize_options' ) // Sanitize callback.
			);

			add_settings_section(
				'flux_cleanup_mate_general_section', // ID.
				esc_html__( 'General Cleanup Options', 'flux-cleanup-mate' ), // Title.
				array( $this, 'general_section_callback' ), // Callback.
				'flux-cleanup-mate'                   // Page.
			);

			add_settings_field(
				'disable_emojis',
				esc_html__( 'Disable Emojis', 'flux-cleanup-mate' ),
				array( $this, 'checkbox_callback' ),
				'flux-cleanup-mate',
				'flux_cleanup_mate_general_section',
				array(
					'id'          => 'disable_emojis',
					'description' => esc_html__( 'Remove WordPress emoji scripts and styles.', 'flux-cleanup-mate' ),
				)
			);

			add_settings_field(
				'disable_rsd_wlw',
				esc_html__( 'Disable RSD & WLW Manifest Links', 'flux-cleanup-mate' ),
				array( $this, 'checkbox_callback' ),
				'flux-cleanup-mate',
				'flux_cleanup_mate_general_section',
				array(
					'id'          => 'disable_rsd_wlw',
					'description' => esc_html__( 'Remove Really Simple Discovery and Windows Live Writer manifest links from the HTML head.', 'flux-cleanup-mate' ),
				)
			);

			add_settings_field(
				'disable_shortlink',
				esc_html__( 'Disable Shortlink', 'flux-cleanup-mate' ),
				array( $this, 'checkbox_callback' ),
				'flux-cleanup-mate',
				'flux_cleanup_mate_general_section',
				array(
					'id'          => 'disable_shortlink',
					'description' => esc_html__( 'Remove the shortlink tag from the HTML head.', 'flux-cleanup-mate' ),
				)
			);

			add_settings_field(
				'disable_generator_tag',
				esc_html__( 'Disable Generator Tag', 'flux-cleanup-mate' ),
				array( $this, 'checkbox_callback' ),
				'flux-cleanup-mate',
				'flux_cleanup_mate_general_section',
				array(
					'id'          => 'disable_generator_tag',
					'description' => esc_html__( 'Remove the WordPress generator version tag from the HTML head and RSS feeds.', 'flux-cleanup-mate' ),
				)
			);

			add_settings_field(
				'disable_feed_links',
				esc_html__( 'Disable Feed Links', 'flux-cleanup-mate' ),
				array( $this, 'checkbox_callback' ),
				'flux-cleanup-mate',
				'flux_cleanup_mate_general_section',
				array(
					'id'          => 'disable_feed_links',
					'description' => esc_html__( 'Remove RSS feed links from the HTML head.', 'flux-cleanup-mate' ),
				)
			);

			add_settings_field(
				'disable_rest_api_links',
				esc_html__( 'Disable REST API Links', 'flux-cleanup-mate' ),
				array( $this, 'checkbox_callback' ),
				'flux-cleanup-mate',
				'flux_cleanup_mate_general_section',
				array(
					'id'          => 'disable_rest_api_links',
					'description' => esc_html__( 'Remove REST API discovery links and DNS prefetch hints from the HTML head.', 'flux-cleanup-mate' ),
				)
			);

			add_settings_field(
				'disable_oembed',
				esc_html__( 'Disable oEmbed', 'flux-cleanup-mate' ),
				array( $this, 'checkbox_callback' ),
				'flux-cleanup-mate',
				'flux_cleanup_mate_general_section',
				array(
					'id'          => 'disable_oembed',
					'description' => esc_html__( 'Remove WordPress oEmbed scripts, styles, discovery links, and rewrite rules.', 'flux-cleanup-mate' ),
				)
			);

			add_settings_field(
				'disable_xmlrpc',
				esc_html__( 'Disable XML-RPC', 'flux-cleanup-mate' ),
				array( $this, 'checkbox_callback' ),
				'flux-cleanup-mate',
				'flux_cleanup_mate_general_section',
				array(
					'id'          => 'disable_xmlrpc',
					'description' => esc_html__( 'Disable XML-RPC functionality. Can improve security but may break some remote publishing tools or apps.', 'flux-cleanup-mate' ),
				)
			);

			add_settings_field(
				'disable_comment_reply_js',
				esc_html__( 'Remove Comment Reply JS', 'flux-cleanup-mate' ),
				array( $this, 'checkbox_callback' ),
				'flux-cleanup-mate',
				'flux_cleanup_mate_general_section',
				array(
					'id'          => 'disable_comment_reply_js',
					'description' => esc_html__( 'Prevent the comment-reply.js script from loading site-wide, except on singular posts where comments are enabled.', 'flux-cleanup-mate' ),
				)
			);

			add_settings_field(
				'remove_jquery_migrate',
				esc_html__( 'Remove jQuery Migrate', 'flux-cleanup-mate' ),
				array( $this, 'checkbox_callback' ),
				'flux-cleanup-mate',
				'flux_cleanup_mate_general_section',
				array(
					'id'          => 'remove_jquery_migrate',
					'description' => esc_html__( 'Remove the jQuery Migrate script from the frontend. Recommended if your theme/plugins do not require it (usually true for modern setups).', 'flux-cleanup-mate' ),
				)
			);

			add_settings_field(
				'disable_heartbeat',
				esc_html__( 'Control Heartbeat API', 'flux-cleanup-mate' ),
				array( $this, 'checkbox_callback' ),
				'flux-cleanup-mate',
				'flux_cleanup_mate_general_section',
				array(
					'id'          => 'disable_heartbeat',
					'description' => esc_html__( 'Disable Heartbeat API on the frontend for non-logged-in/non-editor users. In the admin area, it increases the interval to 60 seconds.', 'flux-cleanup-mate' ),
				)
			);

			add_settings_field(
				'disable_admin_bar_front',
				esc_html__( 'Remove Admin Bar for Non-Admins (Frontend)', 'flux-cleanup-mate' ),
				array( $this, 'checkbox_callback' ),
				'flux-cleanup-mate',
				'flux_cleanup_mate_general_section',
				array(
					'id'          => 'disable_admin_bar_front',
					'description' => esc_html__( 'Hide the WordPress admin bar on the frontend for all users except administrators.', 'flux-cleanup-mate' ),
				)
			);

			// Revisions control.
			add_settings_section(
				'flux_cleanup_mate_revisions_section',
				esc_html__( 'Post Revisions Control', 'flux-cleanup-mate' ),
				array( $this, 'revisions_section_callback' ),
				'flux-cleanup-mate'
			);

			add_settings_field(
				'control_revisions',
				esc_html__( 'Enable Revision Control', 'flux-cleanup-mate' ),
				array( $this, 'checkbox_callback' ),
				'flux-cleanup-mate',
				'flux_cleanup_mate_revisions_section',
				array(
					'id'          => 'control_revisions',
					'description' => esc_html__( 'Enable this to control the number of post revisions stored. If disabled, WordPress default behavior applies.', 'flux-cleanup-mate' ),
				)
			);

			add_settings_field(
				'revisions_to_keep',
				esc_html__( 'Number of Revisions to Keep', 'flux-cleanup-mate' ),
				array( $this, 'number_input_callback' ),
				'flux-cleanup-mate',
				'flux_cleanup_mate_revisions_section',
				array(
					'id'          => 'revisions_to_keep',
					'description' => esc_html__( 'Enter the maximum number of revisions to store for each post/page. Set to 0 to disable revisions entirely.', 'flux-cleanup-mate' ),
				)
			);

			// Dashboard Widgets.
			add_settings_section(
				'flux_cleanup_mate_dashboard_section',
				esc_html__( 'Dashboard Widgets', 'flux-cleanup-mate' ),
				array( $this, 'dashboard_section_callback' ),
				'flux-cleanup-mate'
			);

			$dashboard_widgets = array(
				'dashboard_right_now'   => esc_html__( 'At a Glance', 'flux-cleanup-mate' ),
				'dashboard_activity'    => esc_html__( 'Activity', 'flux-cleanup-mate' ),
				'dashboard_primary'     => esc_html__( 'WordPress Events and News', 'flux-cleanup-mate' ),
				'dashboard_quick_press' => esc_html__( 'Quick Draft', 'flux-cleanup-mate' ),
				'dashboard_site_health' => esc_html__( 'Site Health Status', 'flux-cleanup-mate' ),
				'dashboard_recent_comments' => esc_html__( 'Recent Comments', 'flux-cleanup-mate' ),
				'dashboard_recent_drafts' => esc_html__( 'Recent Drafts', 'flux-cleanup-mate' ),
			);

			foreach ( $dashboard_widgets as $id => $title ) {
				add_settings_field(
					'remove_dashboard_widgets_' . $id,
					$title,
					array( $this, 'dashboard_widget_checkbox_callback' ),
					'flux-cleanup-mate',
					'flux_cleanup_mate_dashboard_section',
					array(
						'id'          => $id,
						'description' => sprintf( esc_html__( 'Remove the "%s" dashboard widget.', 'flux-cleanup-mate' ), $title ),
					)
				);
			}
		}

		/**
		 * Sanitize plugin options.
		 *
		 * @param array $input Input options.
		 * @return array Sanatized options.
		 */
		public function sanitize_options( $input ) {
			$new_input = $this->get_default_options(); // Start with defaults to ensure all keys exist.

			foreach ( $new_input as $key => $default_value ) {
				switch ( $key ) {
					case 'disable_emojis':
					case 'disable_rsd_wlw':
					case 'disable_shortlink':
					case 'disable_generator_tag':
					case 'disable_feed_links':
					case 'disable_rest_api_links':
					case 'disable_oembed':
					case 'disable_xmlrpc':
					case 'disable_comment_reply_js':
					case 'control_revisions':
					case 'disable_heartbeat':
					case 'disable_admin_bar_front':
					case 'remove_jquery_migrate':
						$new_input[ $key ] = isset( $input[ $key ] ) ? (bool) $input[ $key ] : false;
						break;
					case 'revisions_to_keep':
						$new_input[ $key ] = isset( $input[ $key ] ) ? absint( $input[ $key ] ) : 5;
						break;
					case 'remove_dashboard_widgets':
						$new_input[ $key ] = array();
						if ( isset( $input[ $key ] ) && is_array( $input[ $key ] ) ) {
							foreach ( $input[ $key ] as $widget_id => $value ) {
								if ( (bool) $value ) { // Only add if checkbox was checked.
									$new_input[ $key ][] = sanitize_text_field( $widget_id );
								}
							}
						}
						break;
					default:
						// For any future options, ensure they are handled or default.
						break;
				}
			}

			return $new_input;
		}

		/**
		 * Callback for general section.
		 */
		public function general_section_callback() {
			echo '<p>' . esc_html__( 'Configure various general cleanup settings for your WordPress site.', 'flux-cleanup-mate' ) . '</p>';
		}

		/**
		 * Callback for revisions section.
		 */
		public function revisions_section_callback() {
			echo '<p>' . esc_html__( 'Control the number of post revisions stored in your database. This helps keep your database lean.', 'flux-cleanup-mate' ) . '</p>';
		}

		/**
		 * Callback for dashboard section.
		 */
		public function dashboard_section_callback() {
			echo '<p>' . esc_html__( 'Select which default dashboard widgets you want to remove for all users. This can simplify the admin interface.', 'flux-cleanup-mate' ) . '</p>';
		}

		/**
		 * Generic checkbox callback.
		 *
		 * @param array $args Field arguments.
		 */
		public function checkbox_callback( $args ) {
			$id          = $args['id'];
			$description = isset( $args['description'] ) ? $args['description'] : '';
			$checked     = isset( $this->options[ $id ] ) && $this->options[ $id ];

			printf(
				'<input type="checkbox" id="%s" name="flux_cleanup_mate_options[%s]" value="1" %s />',
				esc_attr( $id ),
				esc_attr( $id ),
				checked( $checked, true, false )
			);
			printf( '<label for="%s"> %s</label>', esc_attr( $id ), esc_html( $description ) );
		}

		/**
		 * Number input callback.
		 *
		 * @param array $args Field arguments.
		 */
		public function number_input_callback( $args ) {
			$id          = $args['id'];
			$description = isset( $args['description'] ) ? $args['description'] : '';
			$value       = isset( $this->options[ $id ] ) ? absint( $this->options[ $id ] ) : 5; // Default to 5 if not set.

			printf(
				'<input type="number" id="%s" name="flux_cleanup_mate_options[%s]" value="%d" min="0" step="1" />',
				esc_attr( $id ),
				esc_attr( $id ),
				esc_attr( $value )
			);
			printf( '<label for="%s"> %s</label>', esc_attr( $id ), esc_html( $description ) );
		}

		/**
		 * Dashboard widget checkbox callback.
		 *
		 * @param array $args Field arguments.
		 */
		public function dashboard_widget_checkbox_callback( $args ) {
			$id          = $args['id'];
			$description = isset( $args['description'] ) ? $args['description'] : '';
			$checked     = in_array( $id, $this->options['remove_dashboard_widgets'], true );

			printf(
				'<input type="checkbox" id="remove_dashboard_widgets_%s" name="flux_cleanup_mate_options[remove_dashboard_widgets][%s]" value="1" %s />',
				esc_attr( $id ),
				esc_attr( $id ),
				checked( $checked, true, false )
			);
			printf( '<label for="remove_dashboard_widgets_%s"> %s</label>', esc_attr( $id ), esc_html( $description ) );
		}

		/**
		 * Render the plugin's settings page.
		 */
		public function settings_page_html() {
			// Check user capabilities.
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			?>
			<div class="wrap">
				<h1><?php esc_html_e( 'Flux Cleanup Mate Settings', 'flux-cleanup-mate' ); ?></h1>

				<style>
					/* Inline CSS for the settings page */
					.flux-cleanup-mate-settings form {
						max-width: 900px;
						background: #fff;
						padding: 20px 30px;
						margin-top: 20px;
						border-radius: 8px;
						box-shadow: 0 1px 3px rgba(0,0,0,.08);
					}
					.flux-cleanup-mate-settings h2 {
						border-bottom: 1px solid #eee;
						padding-bottom: 15px;
						margin-top: 30px;
						font-size: 1.5em;
						color: #333;
					}
					.flux-cleanup-mate-settings .form-table th {
						width: 250px;
						padding-top: 15px;
						padding-bottom: 15px;
					}
					.flux-cleanup-mate-settings .form-table td {
						padding-top: 15px;
						padding-bottom: 15px;
						vertical-align: top;
					}
					.flux-cleanup-mate-settings .form-table td label {
						display: inline-block;
						margin-left: 5px;
					}
					.flux-cleanup-mate-settings .form-table input[type="checkbox"] {
						margin-right: 5px;
						margin-top: 2px; /* Align checkbox better with label */
					}
					.flux-cleanup-mate-settings .form-table input[type="number"] {
						width: 80px;
						text-align: center;
					}
					.flux-cleanup-mate-settings .submit button {
						margin-top: 20px;
						font-size: 16px;
						padding: 8px 20px;
						height: auto;
					}
					.flux-cleanup-mate-settings .description {
						color: #666;
						font-style: italic;
						font-size: 0.9em;
						margin-top: 5px;
						display: block;
					}
				</style>

				<div class="flux-cleanup-mate-settings">
					<form action="options.php" method="post">
						<?php
						settings_fields( 'flux_cleanup_mate_options_group' );
						do_settings_sections( 'flux-cleanup-mate' );
						submit_button( esc_html__( 'Save Changes', 'flux-cleanup-mate' ) );
						?>
					</form>
				</div>
			</div>
			<?php
		}
	} // End class Flux_Cleanup_Mate.

	/**
	 * Instantiate the Flux_Cleanup_Mate class.
	 */
	add_action( 'plugins_loaded', array( 'Flux_Cleanup_Mate', 'instance' ) );

} // End if class_exists.