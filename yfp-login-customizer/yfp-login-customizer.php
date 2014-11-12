<?php
/*
Plugin Name: YFP Login Form Customizer
Description: Add a box that must be filled in to login. The text to enter is given above the box.
Version: 1.0
Author: Paul Blakelock, Splendid Spider Web Design.
*/

/**
* A simple concept: robots won't know how to put data in the extra field. Humans
* will figure out the simple instructions.
*
* This can be used in a very simple way that does not even save any data because
* what is needed is all transmitted when the form is submitted. No installation
* is needed, just copy this file into plugin area like any other plugin.
*
* To use this without the admin interface
* comment out the loader in the is_admin() conditional before installing the plugin:
* // new YfpLoginAddition
*
* That will
*
* Cannot use namespaces yet because at this time WP supports PHP version 5.2.4 or greater.
*/

class Yfp_Login_Customizer
{
	// Called KEY because they will be POST keys.
	const KEY_USER_RESPONSE = 'yfp-twice-the-answer';
	const KEY_CORRECT_ANSWER = 'yfp-atoe';	// Answer to Everything.
	const DEF_CORRECT_ANSWER = '4242';
	const DEF_LABEL_BEFORE = 'Enter the number:';
	const DEF_LABEL_AFTER = '';
	const DEF_QUOTE_ANSWER = 0;


	protected $correct_answer;
	protected $label_before;
	protected $label_after;
	protected $quote_answer;

	// Additions for the admin interface.
	const STR_PLUGIN_TITLE = 'YFP Login Form Customizer';
	const STR_BROWSER_MENU_TEXT = 'YFP Login Form Customizer';

	// The name of the key to use for all of this plugin's options.
	const WP_OPTIONS_KEY_NAME = 'yfp_login_additions';
	const WPO_KEY_QUOTE_ANSWER = 'quote_answer';
	const WPO_KEY_ANSWER = 'answer';
	const WPO_KEY_LBL_BEFORE = 'l_before';
	const WPO_KEY_LBL_AFTER = 'l_after';

	const ADMIN_MENU_SLUG = 'yfp-login-additions-admin';
	const TPL_INPUT_ELEM = '<input type="text" id="%s" name="%s" value="%s" />';

	// Copy in these lib functions and add them to the class. Fix the names if this
	// gets popular, but good enough for now.
	/**
	* @param string $str
	* @return bool
	*/
	function isNonEmptyString($str) {
		return is_string($str) && '' !== $str;
	}

	/**
	* @param string $key
	* @param array $arr
	* @return bool
	*/
	function hasKey($key, $arr) {
		return is_array($arr) && array_key_exists($key, $arr);
	}

	/**
	* @param string $key
	* @param array $arr
	* @return bool
	*/
	function keyHasString($key, $arr) {
		return ($this->hasKey($key, $arr) && is_string($arr[$key]));
	}

	/**
	* @param string $key
	* @param array $arr
	* @return bool
	*/
	function keyHasStringContent($key, $arr) {
		return $this->keyHasString($key, $arr) && '' !== $arr[$key];
	}


	/**
	* Just so the key doesn't need to be remembered.
	*/
	protected function get_plugin_options() {
		$options = get_option( self::WP_OPTIONS_KEY_NAME );
		return $options;
	}


	/**
	* Short term: copy the defaults.
	* Later: get the values from wp_options() and use those.
	*/
	protected function update_config_properties() {

		// Set the defaults.
		$this->correct_answer = self::DEF_CORRECT_ANSWER;
		$this->label_before = self::DEF_LABEL_BEFORE;
		$this->label_after = self::DEF_LABEL_AFTER;
		$this->quote_answer = self::DEF_QUOTE_ANSWER;
		// Use the saved options if they exist.
		$options = $this->get_plugin_options();
		if (is_array($options)) {

			if ($this->keyHasStringContent(self::WPO_KEY_ANSWER, $options)) {
				$this->correct_answer = $options[self::WPO_KEY_ANSWER];
			}
			if ($this->hasKey(self::WPO_KEY_QUOTE_ANSWER, $options)) {
				$this->quote_answer = $options[self::WPO_KEY_QUOTE_ANSWER];
			}
			if ($this->keyHasString(self::WPO_KEY_LBL_BEFORE, $options)) {
				$this->label_before = $options[self::WPO_KEY_LBL_BEFORE];
			}
			if ($this->keyHasString(self::WPO_KEY_LBL_AFTER, $options)) {
				$this->label_after = $options[self::WPO_KEY_LBL_AFTER];
			}
		}
	}


	/**
	* Echoes the added field to the login form.
	*/
	function echo_field_to_form(){
		$ans = $this->quote_answer ? '"' . $this->correct_answer . '"' : $this->correct_answer;
		$labelText = sprintf('%s %s %s', $this->label_before, $ans, $this->label_after);
	?><p>
		<label for="<?php echo self::KEY_USER_RESPONSE; ?>"><?php echo $labelText; ?><br>
		<input type="text" size="20" value="" class="input" id="<?php echo self::KEY_USER_RESPONSE; ?>" name="<?php echo self::KEY_USER_RESPONSE; ?>"></label>
		<input type="hidden" name="<?php echo self::KEY_CORRECT_ANSWER; ?>" value="<?php echo $this->correct_answer; ?>" />
	</p>

<?php
	}


	/**
	* Hook into authentication to make sure the user entered a value and that it is correct.
	* None of the supplied parameters are used, but $user must be returned if everything is okay.
	*
	* @param mixed $user
	* @param mixed $password
	*/
	function form_authenticate( $user, $password ){
		// Authenticate seems to get called even while displaying a new login screen, but the field data won't exist
		if ($this->keyHasStringContent(self::KEY_CORRECT_ANSWER, $_POST)) {

			//Get POSTED value
			$submitted_value = $this->hasKey(self::KEY_USER_RESPONSE, $_POST) ? $_POST[self::KEY_USER_RESPONSE] : '';

			if (empty($submitted_value) || $submitted_value != $this->correct_answer){
				//User note found, or no value entered or doesn't match stored value - don't proceed.
				remove_action('authenticate', 'wp_authenticate_username_password', 20);
				//Create an error to return to user
				return new WP_Error( 'denied', __("<strong>ERROR</strong>: You forgot to answer with " . $this->correct_answer . ".") );
			}
		}
		return $user;
	}

	/////////////////////////////////////////////////////
	// The rest of this is admin interface and variable storage.
	/////////////////////////////////////////////////////
	protected function init_admin() {

		if ($this->use_admin && is_admin() ) {
			// Init some variables used in the setup.
			//$this->options = get_option( self::WP_OPTIONS_KEY_NAME );
			$this->settings_group_name = self::WP_OPTIONS_KEY_NAME;

			// Add actions that are methods in theis class.
			add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
		}
	}

	/**
	 * Add the options page to the Settings menu.
	 */
	public function add_plugin_page() {

		// This page will be under "Settings"  in the admin area.
		// This function is a simple wrapper for a call to add_submenu_page().
		add_options_page(
			'Settings Admin', // Title for the browser's title tag.
			self::STR_BROWSER_MENU_TEXT, // Menu text, show under Settings.
			'manage_options', // Which users can use this.
			self::ADMIN_MENU_SLUG, // Menu slug
			array( $this, 'create_admin_page' )
		);
	}

	/**
	 * Options page callback, this creates the admin settings page content.
	 */
	public function create_admin_page() {
		?>
		<div class="wrap">
			<h2><?php echo self::STR_PLUGIN_TITLE; ?> settings</h2>
			<p>The defaults will work for most people, but any of the values can be
			changed to different strings. Before and After text will be used in
			the form's label around "the answer".</p>

			<form method="post" action="options.php">
			<?php
				// The option group. This should match the group name used in register_setting().
				settings_fields( $this->settings_group_name );
				// This prints out all hidden setting fields for the page.
				// This will output the section titles wrapped in h3 tags and the settings fields wrapped in tables.
				do_settings_sections( self::ADMIN_MENU_SLUG );
				submit_button();
				// The key used matches the Option name in register_settings.
				//print debug_options_arr($this->get_plugin_options());
			?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register and add settings.
	 */
	public function register_settings() {

		//Although much code shows doing this for a single value, it is recommended that all
		// plugin setting are put in one array. This just saves the name and tells how to sanitize.
		register_setting(
			$this->settings_group_name, // Option group
			self::WP_OPTIONS_KEY_NAME, // tTe option name being registerd, same as used in the get_option() call.
			array( $this, 'sanitize_options' ) // Sanitize callback, used for all options
		);

		// There is only one section, so any ID will do.
		add_settings_section(
			'yfp_login_section_1', // ID
			'All settings', // Title
			array( $this, 'section_1_html' ), // Callback, provides the html for the section.
			self::ADMIN_MENU_SLUG // Options page slug, used in do_settings_sections.
		);

		// Need a field for each option that can be changed. There output the html for each field.
		add_settings_field(
			//'answer_input', // ID
			$this->form_input_id_in_array(self::WP_OPTIONS_KEY_NAME, self::WPO_KEY_ANSWER),
			'The answer', // Title, looks like the input label in this case.
			array( $this, 'form_html_answer_input' ), // Callback to make the html for this field
			self::ADMIN_MENU_SLUG, // Options page slug
			'yfp_login_section_1' // Section for this field, which section this lives in.
		);

		add_settings_field(
			// ID
			$this->form_input_id_in_array(self::WP_OPTIONS_KEY_NAME, self::WPO_KEY_QUOTE_ANSWER),
			'Wrap in quotes',
			array( $this, 'form_html_quote_answer' ),
			self::ADMIN_MENU_SLUG, // Options page slug
			'yfp_login_section_1' // Section for this field
		);

		add_settings_field(
			// ID
			$this->form_input_id_in_array(self::WP_OPTIONS_KEY_NAME, self::WPO_KEY_LBL_BEFORE),
			'Before the answer',
			array( $this, 'form_html_before_input' ),
			self::ADMIN_MENU_SLUG, // Options page slug
			'yfp_login_section_1' // Section for this field
		);

		add_settings_field(
			$this->form_input_id_in_array(self::WP_OPTIONS_KEY_NAME, self::WPO_KEY_LBL_AFTER),
			'After the answer',
			array( $this, 'form_html_after_input' ),
			self::ADMIN_MENU_SLUG, // Options page slug
			'yfp_login_section_1' // Section for this field
		);

}


	/**
	 * Sanitize each setting field as needed. If a key does not get returned
	 * it won't be saved. Because all of the used data is in a single options
	 * key, that means that values will return to the default. So code was
	 * added to fix that: each value is sanitized every time.
	 *
	 * @param array $input - Contains all settings fields as array keys
	 * @return array - The sanitized input array.
	 */
	public function sanitize_options( $input ) {

		$new_input = array();
		// New installation or saving for the first time.
		$current_options = $this->get_plugin_options();

		if (!is_array($current_options)) {
			// Will allow updating of everything because the keys will not exist.
			$current_options = array();
		}

		// Determines the type of message displayed, will be changed if there is an error.
		$type = 'updated';
		//$data = debug_options_arr($input);
		$message = 'The input array: ' . print_r($input, true);
		//$message .= 'pre update values: ' . debug_options_arr($this->options) . ' | ';

		// Use this variable some more code can be copied, but it is somewhat unique, no not a method.
		$check_key = self::WPO_KEY_ANSWER;
		// Check the answer inputs
		if( isset( $input[$check_key] ) ) {
			$val = preg_replace('/[^a-zA-Z0-9_]/', '', $input[$check_key]);
			$val = sanitize_text_field( $val );
			$chars = strlen( $val );
			if ( 0 !== $chars && 20 >= $chars ) {
				$new_input[$check_key] = $val;
				// Only update the message if the value has changed.
				if ( !array_key_exists($check_key, $current_options) ||
						$current_options[$check_key] !== $new_input[$check_key] ) {
					$message .= __('Answer field updated. ');
				}
			}
			else {
				$type = 'error';
				$message .= __('The Answer field cannot be empty, must only be alphanumeric, and must be 20 or fewer characters. ');
				// This was initialized to a default if the value was not otherwise set.
				$new_input[$check_key] = $this->correct_answer;
			}
		}

		// Check the Quote inputs, it's a checkbox.
		$check_key = self::WPO_KEY_QUOTE_ANSWER;
		// Check the answer inputs
		if( isset( $input[$check_key] ) ) {
			$val = 1;
		}
		else {
			$val = 0;
		}
		$new_input[$check_key] = $val;
			// Only update the message if the value has changed.
		if ( !array_key_exists($check_key, $current_options) ||
				$current_options[$check_key] !== $new_input[$check_key] ) {
			$message .= __('Add Quotes field updated. ');
		}

		// Check the Before inputs
		$check_key = self::WPO_KEY_LBL_BEFORE;
		// Check the answer inputs
		if( isset( $input[$check_key] ) ) {
			$val = sanitize_text_field( $input[$check_key] );
			$chars = strlen( $val );
			if ( 100 >= $chars ) {
				$new_input[$check_key] = $val;
				// Only update the message if the value has changed.
				if ( !array_key_exists($check_key, $current_options) ||
						$current_options[$check_key] !== $new_input[$check_key] ) {
					$message .= __('Before field updated. ');
				}
			}
			else {
				$type = 'error';
				$message .= __('The Before field must be 100 or fewer characters. ');
				// This was initialized to a default if the value was not otherwise set.
				$new_input[$check_key] = $this->label_before;
			}
		}

		// Check the After inputs
		$check_key = self::WPO_KEY_LBL_AFTER;
		// Check the answer inputs
		if( isset( $input[$check_key] ) ) {
			$val = sanitize_text_field( $input[$check_key] );
			$chars = strlen( $val );
			if ( 100 >= $chars ) {
				$new_input[$check_key] = $val;
				// Only update the message if the value has changed.
				if ( !array_key_exists($check_key, $current_options) ||
						$current_options[$check_key] !== $new_input[$check_key] ) {
					$message .= __('After field updated. ');
				}
			}
			else {
				$type = 'error';
				$message .= __('The After field must be 100 or fewer characters. ');
				// This was initialized to a default if the value was not otherwise set.
				$new_input[$check_key] = $this->label_after;
			}
		}

		$message .= '<br />The new input: ' . print_r($new_input, true);

		//$message .= ' | debug: ' . debug_options_arr($new_input);
		if ('' !== $message) {

			add_settings_error(
				'unusedUniqueIdentifyer',
				esc_attr( 'settings_updated' ),
				$message,
				$type
			);
		}
		return $new_input;
	}

	/**
	 * Print the Section html
	 */
	public function section_1_html() {

		$tpl = 'Name: "%s" &mdash; Value: "%s"<br />';
		$vars = array(
			'answer' => $this->correct_answer,
			'quote it' => $this->quote_answer,
			'before' => $this->label_before,
			'after' => $this->label_after,
		);
		_e('All setting for the plugin are on this page. Enter your settings below:');
		echo 'Setting at form loading: <br />';
		foreach ($vars as $varname => $val) {
			printf($tpl, $varname, $val);
		}
	}

	/**
	* The options are array elements, so this makes the text that goes in the
	* name portion of an input field.
	*
	* @param string $array_name
	* @param string $key_name
	* @return string
	*/
	protected function form_input_name_in_array($array_name, $key_name) {
		return $array_name . '[' . $key_name . ']';
	}
	protected function form_input_id_in_array($array_name, $key_name) {
		return $array_name . '_' . $key_name;
	}

	/**
	 * Get the settings option array and print one of its values
	 * This echoes and input element with name and id.
	 * It is unclear when this is called (the state of the options array.) I'm assuming
	 * that the one update of the class' constructor handles the state of the data.
	 */
	public function form_html_answer_input() {

		$key = self::WPO_KEY_ANSWER;
		// params are: id, name, value
		printf(self::TPL_INPUT_ELEM,
			$this->form_input_id_in_array(self::WP_OPTIONS_KEY_NAME, self::WPO_KEY_ANSWER),
			$this->form_input_name_in_array(self::WP_OPTIONS_KEY_NAME, self::WPO_KEY_ANSWER),
			str_replace( '"', '&quot;', $this->correct_answer)
		);
		echo '<br />This is the text that the user must enter to login. It must be alpha-numeric and be less than 20 characters. It will be displayd as part of the login screen.';
	}

	public function form_html_quote_answer() {
		$tplChk = '<input id="%s" name="%s" type="checkbox" value="1" %s />';
		printf($tplChk,
			$this->form_input_id_in_array(self::WP_OPTIONS_KEY_NAME, self::WPO_KEY_QUOTE_ANSWER),
			$this->form_input_name_in_array(self::WP_OPTIONS_KEY_NAME, self::WPO_KEY_QUOTE_ANSWER),
			checked( 1, $this->quote_answer, false )
		);
		echo '<br />Wrap the Answer in quotes when it is shown on the login screen.';
	}

	public function form_html_before_input($opt=array()) {
		// params are: id, name, value
		printf(self::TPL_INPUT_ELEM,
			$this->form_input_id_in_array(self::WP_OPTIONS_KEY_NAME, self::WPO_KEY_LBL_BEFORE),
			$this->form_input_name_in_array(self::WP_OPTIONS_KEY_NAME, self::WPO_KEY_LBL_BEFORE),
			str_replace( '"', '&quot;', $this->label_before)
		);
		echo '<br />This text will be displayed immediately before "the answer".';
		htmlspecialchars( print_r($opt) );
	}
	public function form_html_after_input() {
		// params are: id, name, value
		printf(self::TPL_INPUT_ELEM,
			$this->form_input_id_in_array(self::WP_OPTIONS_KEY_NAME, self::WPO_KEY_LBL_AFTER),
			$this->form_input_name_in_array(self::WP_OPTIONS_KEY_NAME, self::WPO_KEY_LBL_AFTER),
			str_replace( '"', '&quot;', $this->label_after)
		);
		echo '<br />This text will be displayed immediately after "the answer".';
	}


	/**
	* Add the hooks that make this run.
	*/
	function __construct($useAdmin=true) {

		$this->use_admin = !!$useAdmin;
		$this->update_config_properties();
		add_action('login_form', array($this, 'echo_field_to_form', ));
		add_filter('wp_authenticate_user', array($this, 'form_authenticate'), 10, 3);
		$this->init_admin();
	}
}
// Launch the plugin.
new Yfp_Login_Customizer();




////////////////////////////////////////////////////////
// Everything else it to handle an admin screen
////////////////////////////////////////////////////////

/**
* Not used, but a way to detect if a plugin is active.
*/
/*
function sswd_is_plugin_active($plugin_var) {
	$res = in_array(
		$plugin_var. '/' .$plugin_var. '.php',
		apply_filters( 'active_plugins', get_option( 'active_plugins' ) )
	);
	return $res;
}
*/

