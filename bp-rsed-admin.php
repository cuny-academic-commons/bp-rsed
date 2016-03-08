<?php
/**
 * Admin code for BP Restrict Signup By Email Domain
 *
 * @package BP_RSED
 * @subpackage Admin
 */

/**
 * Admin handler class for BP Restrict Signup By Email Domain.
 *
 * @since 1.0.0
 */
class BP_RSED_Admin_Settings {

	/**
	 * Settings holder.
	 *
	 * @var array
	 */
	protected $settings = array();

	/**
	 * Static initializer.
	 */
	public static function init() {
		return new self();
	}

	/**
	 * Constructor.
	 */
	protected function __construct() {
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
		// We're on the bp-settings page
		if ( isset( $_GET['page'] ) && 'bp-settings' == $_GET['page'] ) {
			// save
			if ( ! empty( $_POST['submit'] ) ) {
				global $wp_settings_fields;

				// Piggyback off existing admin referer.
				check_admin_referer( 'buddypress-options' );

				// Do not let bp_core_admin_settings_save() save our placeholder fields.
				unset( $wp_settings_fields['buddypress']['bp_rsed'] );

				// Sanitize before saving
				$retval = array();
				$retval['whitelist_domains'] = wp_filter_nohtml_kses( trim( $_REQUEST['bp_rsed']['whitelist_domains'] ) );
				$retval['error_msg']         = wp_filter_nohtml_kses( $_REQUEST['bp_rsed']['error_msg'] );
				$retval['registration_msg']  = wp_filter_kses( $_REQUEST['bp_rsed']['registration_msg'] );

				bp_update_option( 'bp_rsed', $retval );
			}

			// Get settings.
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

		add_settings_field(
			'bp-rsed-whitelist-domains',
			__( 'Whitelist Email Domains', 'bp-rsed' ),
			array( $this, 'textarea_whitelist_domains' ),
			'buddypress',
			'bp_rsed',
			array( 'label_for' => 'bp_rsed_whitelist_domains' )
		);

		add_settings_field(
			'bp-rsed-error-msg',
			__( 'Error Message', 'bp-rsed' ),
			array( $this, 'textarea_error_msg' ),
			'buddypress',
			'bp_rsed',
			array( 'label_for' => 'bp_rsed_error_msg' )
		);

		add_settings_field(
			'bp-rsed-registration-msg',
			__( 'Registration Message', 'bp-rsed' ),
			array( $this, 'textarea_registration_msg' ),
			'buddypress',
			'bp_rsed',
			array( 'label_for' => 'bp_rsed_registration_msg' )
		);
	}

	/** FORM FIELDS ***************************************************/

	/**
	 * Callback for add_settings_field().
	 */
	public function textarea_whitelist_domains() {
		$value = isset( $this->settings['whitelist_domains'] ) ? $this->settings['whitelist_domains'] : '';
	?>

		<textarea class="large-text" rows="5" cols="45" id="bp_rsed_whitelist_domains" name="bp_rsed[whitelist_domains]"><?php echo esc_textarea( $value ); ?></textarea>
		<p class="description"><?php _e( 'Limit site registrations to certain domains. One domain per line.', 'bp-rsed' ); ?></p>

	<?php
	}

	/**
	 * Callback for add_settings_field().
	 */
	public function textarea_error_msg() {
		$value = stripslashes( $this->settings['error_msg'] );
	?>

		<textarea class="large-text" rows="5" cols="45" id="bp_rsed_error_msg" name="bp_rsed[error_msg]"><?php echo esc_textarea( $value ); ?></textarea>
		<p class="description"><?php _e( 'When a user enters an email address that does not match a whitelisted domain, this error message gets displayed.  You can use the {{domains}} token to list the email domains that are allowed.', 'bp-rsed' ); ?></p>

	<?php
	}

	/**
	 * Callback for add_settings_field().
	 */
	public function textarea_registration_msg() {
		$value = stripslashes( $this->settings['registration_msg'] );
	?>

		<textarea class="large-text" rows="5" cols="45" id="bp_rsed_registration_msg" name="bp_rsed[registration_msg]"><?php echo esc_textarea( $value ); ?></textarea>
		<p class="description"><?php _e( 'Enter a custom message to display on the registration page. This will be shown right before the registration form.  You can use the {{domains}} token to list the email domains that are allowed and the {{adminemail}} token to display the email address of the administrator.  Leave blank if desired.', 'bp-rsed' ); ?></p>

	<?php
	}
}
