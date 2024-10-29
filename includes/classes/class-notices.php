<?php
/**
 * Contains class Notices.
 *
 * @package AdminNoticesManager
 */

declare(strict_types=1);

namespace AdminNoticesManager;

if ( ! class_exists( '\AdminNoticesManager\Notices' ) ) {
	/**
	 * Takes care of the admin notices content capture.
	 *
	 * @package AdminNoticesManager
	 *
	 * @since 1.0.0
	 */
	class Notices {
		/**
		 * Notices constructor.
		 *
		 * @param bool $notice_hiding_allowed True if notice hiding is allowed for the current user.
		 *
		 * @since 1.0.0
		 */
		public static function init( $notice_hiding_allowed ) {
			if ( $notice_hiding_allowed ) {
				// Priority of 0 to render before any notices.
				\add_action( 'network_admin_notices', array( __CLASS__, 'start_output_capturing' ), 0 );
				\add_action( 'user_admin_notices', array( __CLASS__, 'start_output_capturing' ), 0 );
				\add_action( 'admin_notices', array( __CLASS__, 'start_output_capturing' ), 0 );

				// Priority of 999999 to render after all notices.
				\add_action( 'all_admin_notices', array( __CLASS__, 'finish_output_capturing' ), 999999 );

				\add_action( 'admin_bar_menu', array( __CLASS__, 'add_item_in_admin_bar' ), 100 );
			}

			\add_action( 'wp_ajax_anm_log_notices', array( __CLASS__, 'log_notices' ) );
			\add_action( 'wp_ajax_anm_hide_notice_forever', array( __CLASS__, 'hide_notice_forever' ) );
		}

		/**
		 * Prints the beginning of wrapper element before all notices.
		 *
		 * @since 1.0.0
		 */
		public static function start_output_capturing() {
			// Hidden by default to prevent a flash of unstyled content on page load.
			echo '<div class="anm-notices-wrapper" style="display: none;">';
		}

		/**
		 * Prints the beginning of wrapper element after all notices.
		 *
		 * @since 1.0.0
		 */
		public static function finish_output_capturing() {
			echo '</div><!-- /.anm-notices-wrapper -->';
		}

		/**
		 * Adds menu item showing number of notifications.
		 *
		 * @param \WP_Admin_Bar $admin_bar WordPress admin bar.
		 *
		 * @since 1.0.0
		 */
		public static function add_item_in_admin_bar( $admin_bar ) {
			$admin_bar->add_menu(
				array(
					'id'     => 'anm_notification_count',
					'title'  => esc_html__( 'No admin notices', 'admin-notices-manager' ),
					'href'   => '#',
					'parent' => 'top-secondary',
				)
			);
		}

		/**
		 * Handles AJAX requests for logging the notices.
		 *
		 * @return false|void
		 *
		 * @since 1.0.0
		 */
		public static function log_notices() {
			// If we have a nonce posted, check it.
			if ( \wp_doing_ajax() && isset( $_POST['_nonce'] ) ) {
				$nonce_check = \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['_nonce'] ) ), 'anm-ajax-nonce' );
				if ( ! $nonce_check ) {
					return false;
				}
			}

			if ( isset( $_POST['notices'] ) && ( ! empty( $_POST['notices'] && is_array( $_POST['notices'] ) ) ) ) {
				$currently_held_options = \get_option( 'anm-notices', array() );
				$hidden_forever         = \get_option( 'anm-hidden-notices', array() );
				$hashed_notices         = array();
				$details                = array();
				$format                 = \get_option( 'date_format' ) . ' ' . \get_option( 'time_format' );

				foreach ( $_POST['notices'] as $index => $notice ) {
					$hash = \wp_hash( $notice );

					$current_time            = \current_time( 'timestamp' );
					$hashed_notices[ $hash ] = $current_time;
					$details[ $index ]       = array( $hash, date_i18n( $format, $current_time ) );

					// Do we already know about this notice?
					if ( isset( $currently_held_options[ $hash ] ) ) {
						$hashed_notices[ $hash ] = $currently_held_options[ $hash ];
						$details[ $index ]       = array( $hash, date_i18n( $format, $currently_held_options[ $hash ] ) );
					}

					if ( in_array( $hash, $hidden_forever, true ) ) {
						$details[ $index ] = 'do-not-display';
					}
				}

				\update_option( 'anm-notices', $hashed_notices );

				\wp_send_json_success( $details );
			}
		}

		/**
		 * Handles AJAX requests for hiding a notice forever.
		 *
		 * @return false|void
		 *
		 * @since 1.0.0
		 */
		public static function hide_notice_forever() {
			// If we have a nonce posted, check it.
			if ( \wp_doing_ajax() && isset( $_POST['_nonce'] ) ) {
				$nonce_check = \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['_nonce'] ) ), 'anm-ajax-nonce' );
				if ( ! $nonce_check ) {
					return false;
				}
			}

			if ( isset( $_POST['notice_hash'] ) ) {
				$currently_held_options = \get_option( 'anm-hidden-notices', array() );
				array_push( $currently_held_options, $_POST['notice_hash'] );
				\update_option( 'anm-hidden-notices', $currently_held_options );
				\wp_send_json_success();
			}
		}
	}
}
