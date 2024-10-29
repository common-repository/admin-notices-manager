<?php
/**
 * Class adding plugin's pointers.
 *
 * @package AdminNoticesManager
 */

declare(strict_types=1);

namespace AdminNoticesManager;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\AdminNoticesManager\Pointers' ) ) {
	/**
	 * Responsible for showing the pointers.
	 *
	 * @since 1.6.0
	 */
	class Pointers {

		public const POINTER_ADMIN_MENU_NAME          = 'anm-admin-notifications-menu';
		public const POINTER_ADMIN_MENU_SETTINGS_NAME = 'anm-admin-settings-menu';

		/**
		 * Inits the class and sets the hooks
		 *
		 * @return void
		 *
		 * @since 1.6.0
		 */
		public static function init() {

			if ( \is_admin() ) {

				// Check that current user should see the pointers.
				$eligible_user_id = intval( \get_option( 'anm-plugin-installed-by-user-id', 1 ) );
				if ( 0 === $eligible_user_id ) {
					$eligible_user_id = 1;
				}

				$current_user_id = \get_current_user_id();
				if ( 0 === $current_user_id || $current_user_id !== $eligible_user_id ) {
					return;
				}

				if ( $eligible_user_id && ( ! self::is_dismissed( self::POINTER_ADMIN_MENU_NAME ) || ! self::is_dismissed( self::POINTER_ADMIN_MENU_SETTINGS_NAME ) ) ) {
					\add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
				}
			}
		}

		/**
		 * Adds the necessary scripts to the queue
		 *
		 * @return void
		 *
		 * @since 1.6.0
		 */
		public static function admin_enqueue_scripts() {
			// Using Pointers.
			\wp_enqueue_style( 'wp-pointer' );
			\wp_enqueue_script( 'wp-pointer' );

			// Register our action.
			\add_action( 'admin_print_footer_scripts', array( __CLASS__, 'print_footer_scripts' ) );
		}

		/**
		 * Prints out the JS needed to show the pointer.
		 *
		 * @return void
		 *
		 * @since 1.6.0
		 */
		public static function print_footer_scripts() {

			$first_element_id  = 'wp-admin-bar-anm_notification_count';
			$second_element_id = 'menu-settings';
			?>
			<script>
				jQuery(
					function() {
						var { __ } = wp.i18n;

						<?php
						if ( ! self::is_dismissed( self::POINTER_ADMIN_MENU_NAME ) ) {
							?>

						jQuery('#<?php echo \esc_attr( $first_element_id ); ?>').pointer( 
							{
								content:
									"<h3>" + __( 'Admin Notices Manager', 'admin-notices-manager' ) + "<\/h4>" +
									"<p>" + __( 'From now onward, all the admin notices will be displayed here.', 'admin-notices-manager' ) + "</p>",


								position:
									{
										edge:  'top',
										align: 'center'
									},

								pointerClass:
									'wp-pointer anm-pointer',

								//pointerWidth: 20,
								
								close: function() {
									jQuery.post(
										ajaxurl,
										{
											pointer: '<?php echo \esc_attr( self::POINTER_ADMIN_MENU_NAME ); ?>',
											action: 'dismiss-wp-pointer',
										}
									);

									second.pointer('open');
								},

							}
						).pointer('open');
							<?php
						}
						?>
						var second = jQuery('#<?php echo \esc_attr( $second_element_id ); ?>').pointer( 
							{
								content:
									"<h3>" + __( 'Configure the Admin Notices Manager', 'admin-notices-manager' ) + "<\/h3>" +
									"<p>" + __( 'Configure how the plugin handles different types of admin notices from the Settings > Admin Notices menu item.', 'admin-notices-manager' ) + "</p>",


								position:
									{
										edge:  'left',
										align: 'center'
									},

								// pointerClass:
								// 	'wp-pointer anm-pointer',

								//pointerWidth: 20,
								
								close: function() {
									jQuery.post(
										ajaxurl,
										{
											pointer: '<?php echo \esc_attr( self::POINTER_ADMIN_MENU_SETTINGS_NAME ); ?>',
											action: 'dismiss-wp-pointer',
										}
									);
								},

							}
						);
						<?php
						if ( self::is_dismissed( self::POINTER_ADMIN_MENU_NAME ) && ! self::is_dismissed( self::POINTER_ADMIN_MENU_SETTINGS_NAME ) ) {
							?>
								second.pointer('open');
							<?php
						}
						?>
					}
				);
			</script>
				<?php
		}

		/**
		 * Checks if the user already dismissed the message
		 *
		 * @param string $pointer - Name of the pointer to check.
		 *
		 * @return boolean
		 *
		 * @since 1.6.0
		 */
		public static function is_dismissed( string $pointer ): bool {

			$dismissed = array_filter( explode( ',', (string) \get_user_meta( \get_current_user_id(), 'dismissed_wp_pointers', true ) ) );

			return \in_array( $pointer, (array) $dismissed, true );
		}
	}
}
