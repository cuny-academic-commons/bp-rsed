<?php
/*
Plugin Name: BP Restrict Signup By Email Domain
Description: Only allow users with email addresses from certain domains to register in BuddyPress.
Version: 1.0
Author: r-a-y
Author URI: http://profiles.wordpress.org/r-a-y
License: GPLv2 or later
*/

add_action( 'bp_include', array( 'BP_RSED', 'init' ) );

/**
 * BP Restrict Signup By Email Domain.
 */
class BP_RSED {
	/**
	 * Static initializer.
	 */
	public static function init() {
		return new self();
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'bp_signup_validate',               array( $this, 'validate' ) );
		add_action( 'bp_before_account_details_fields', array( $this, 'registration_msg' ) );

		// Set up admin area if in the WP dashboard
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			BP_RSED_Admin_Settings::init();
		}
	}

	/**
	 * Validate email address against custom whitelisted email domains.
	 */
	public function validate() {
		global $bp;

		$allowed_domains = self::allowed_domains();

		// make sure we have some whitelisted domains added
		if ( empty( $allowed_domains ) ) {
			return;
		}

		// get full email domain of email address
		$email_domain = substr(
			$bp->signup->email,
			strpos( $bp->signup->email, '@' ) + 1
		);

		$okay = false;

		foreach ( $allowed_domains as $allowed ) {
			// see if email address matches a whitelisted domain
			if ( self::string_ends_with( $email_domain, $allowed ) ) {
				// first character doesn't contain a dot
				// check length
				if ( strpos( $allowed, '.' ) !== 0 ) {
					if ( strlen( $allowed ) == strlen( $email_domain ) ) {
						$okay = true;
						break;
					}

				} else {
					$okay = true;
					break;
				}
			}
		}

		// email address doesn't match any whitelisted domain, so throw up an error
		if ( ! $okay ) {
			$settings = self::get_settings();

			$bp->signup->errors['signup_email'] = sprintf(
				wp_kses_data( $settings['error_msg'] ),
				implode( ', ', $allowed_domains )
			);
		}

	}

	/**
	 * Show custom registration blurb if available.
	 *
	 * Only shown if whitelisted email domains are set.
	 */
	public function registration_msg() {
		$allowed_domains = self::allowed_domains();

		// make sure we have some whitelisted domains added
		if ( empty( $allowed_domains ) ) {
			return;
		}

		$settings = self::get_settings();

		// make sure
		if ( empty( $settings['registration_msg'] ) ) {
			return;
		}

		echo apply_filters( 'comment_text',
			sprintf( $settings['registration_msg'], '<strong>' . implode( ', ', $allowed_domains ) . '</strong>', get_option( 'admin_email' ) )
		);
	}

	/**
	 * Only allow certain email domains to register in BuddyPress.
	 *
	 * @return array
	 */
	public static function allowed_domains() {
		$settings = self::get_settings();

		if ( empty( $settings['whitelist_domains'] ) ) {
			return false;
		}

		$domains = preg_split( '/\R/', $settings['whitelist_domains'] );

		return apply_filters( 'bp_rsed_allowed_domains', $domains );
	}

	/**
	 * Get our settings.
	 *
	 * Sets defaults if not set.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$settings = bp_get_option( 'bp_rsed' );

		// set defaults
		if ( is_string( $settings ) ) {
			$settings = array();

			// can be overriden in settings page by admin
			if ( empty( $settings['registration_msg'] ) ) {
				$settings['registration_msg'] = __( 'Registrations are currently only allowed for email addresses from these domains - %s. To register with a different address, please write to %s to request an account.', 'bp-rsed' );
			}

		}

		// always have an error message
		if ( empty( $settings['error_msg'] ) ) {
			$settings['error_msg'] = __( 'This email address is not allowed. Please use an email address from one of these domains - %s', 'bp-rsed' );
		}

		return $settings;
	}

	/**
	 * Static method to find out if a string ends with a certain string.
	 *
	 * @link http://stackoverflow.com/a/619725
	 *
	 * @param string $str The string we want to test against
	 * @param string $test The string we're attempting to find
	 * @bool
	 */
	public static function string_ends_with( $str, $test ) {
		return substr_compare( $str, $test, strlen( $str )-strlen( $test ), strlen( $test ) ) === 0;
	}

}

/**
 * Admin settings for the plugin.
 */
class BP_RSED_Admin_Settings {

	/**
	 * Static initializer.
	 */
	public static function init() {
		return new self();
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'bp_admin_init',              array( $this, 'screen' ),     99 );
		add_action( 'bp_register_admin_settings', array( $this, 'add_fields' ), 50 );
	}

	/**
	 * Sets up the screen.
	 *
	 * This method handles saving for our custom form fields and grabs our settings
	 * only when on the "BuddyPress > Settings" admin page.
	 */
	public function screen() {
		// we're on the bp-settings page
		if ( isset( $_GET['page'] ) && 'bp-settings' == $_GET['page'] ) {
			// save
			if ( ! empty( $_POST['submit'] ) ) {
				global $wp_settings_fields;

				// piggyback off existing admin referer
				check_admin_referer( 'buddypress-options' );

				// do not let bp_core_admin_settings_save() save our placeholder fields
				unset( $wp_settings_fields['buddypress']['bp_rsed'] );

				// sanitize before saving
				$retval = array();
				$retval['whitelist_domains'] = wp_filter_nohtml_kses( trim( $_REQUEST['bp_rsed']['whitelist_domains'] ) );
				$retval['error_msg']         = wp_filter_nohtml_kses( $_REQUEST['bp_rsed']['error_msg'] );
				$retval['registration_msg']  = wp_filter_kses( $_REQUEST['bp_rsed']['registration_msg'] );

				bp_update_option( 'bp_rsed', $retval );
			}

			// get settings
			$this->settings = BP_RSED::get_settings();
		}
	}

	/**
	 * Register our form fields.
	 *
	 * This is so they show up on the "BuddyPress > Settings" admin page.  Note:
	 * The settings fields passed as the first parameter in add_settings_field()
	 * are placeholders.
	 *
	 * Actual field names are named in the respective callbacks and saved under
	 * the screen() method.
	 */
	public function add_fields() {
		add_settings_section( 'bp_rsed', __( 'Email Address Restrictions', 'bp-rsed' ), '__return_false', 'buddypress' );

		add_settings_field( 'bp-rsed-whitelist-domains', __( 'Whitelist Email Domains', 'bp-rsed' ), array( $this, 'textarea_whitelist_domains' ), 'buddypress', 'bp_rsed' );

		add_settings_field( 'bp-rsed-error-msg', __( 'Error Message', 'bp-rsed' ), array( $this, 'textarea_error_msg' ), 'buddypress', 'bp_rsed' );

		add_settings_field( 'bp-rsed-registration-msg', __( 'Registration Message', 'bp-rsed' ), array( $this, 'textarea_registration_msg' ), 'buddypress', 'bp_rsed' );
	}

	/** FORM FIELDS ***************************************************/

	public function textarea_whitelist_domains() {
		$value = isset( $this->settings['whitelist_domains'] ) ? $this->settings['whitelist_domains'] : '';
	?>

		<textarea class="large-text" rows="5" cols="45" id="bp_rsed_whitelist_domains" name="bp_rsed[whitelist_domains]"><?php echo esc_textarea( $value ); ?></textarea>
		<p class="description"><?php _e( 'Limit site registrations to certain domains. One domain per line.', 'bp-rsed' ); ?></p>

	<?php
	}

	public function textarea_error_msg() {
		$value = stripslashes( $this->settings['error_msg'] );
	?>

		<textarea class="large-text" rows="5" cols="45" id="bp_rsed_error_msg" name="bp_rsed[error_msg]"><?php echo esc_textarea( $value ); ?></textarea>
		<p class="description"><?php _e( 'When a user enters an email address that does not match a whitelisted domain, this error message gets displayed.', 'bp-rsed' ); ?></p>

	<?php
	}

	public function textarea_registration_msg() {
		$value = stripslashes( $this->settings['registration_msg'] );
	?>

		<textarea class="large-text" rows="5" cols="45" id="bp_rsed_registration_msg" name="bp_rsed[registration_msg]"><?php echo esc_textarea( $value ); ?></textarea>
		<p class="description"><?php _e( 'Enter a custom message to display on the registration page. This will be shown right before the registration form.  Leave blank if desired.', 'bp-rsed' ); ?></p>

	<?php
	}
}
