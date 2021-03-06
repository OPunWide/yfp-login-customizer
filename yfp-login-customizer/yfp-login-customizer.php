<?php
/*
Plugin Name: YFP Login Form Customizer
Plugin URI: https://github.com/OPunWide/yfp-login-customizer
Description: Adds a box to the WordPress login form that must be properly filled in as part of the login process. The correct text to enter is displayed above the box, so humans can easily login, while robots are unlikely to know what to do.
Version: 1.0.1
Author: Paul Blakelock, Splendid Spider Web Design.
Author URI: http://SplendidSpider.com
License: GPL2
*/
/*  Copyright 2014  Paul Blakelock  (email : web@SplendidSpider.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


/**
* A simple concept: robots won't know how to put data in the extra field. Humans
* will figure out the simple instructions.
*
* This can be used in a very simple way that does not even save any data because
* what is needed is all transmitted when the form is submitted. No installation
* is needed, just copy this file into plugin area like any other plugin.
*
* :-( Cannot use namespaces yet because at this time WP still supports PHP version 5.2.4 or greater.
*/

if ( ! class_exists( 'Yfp_Plugin_Base' ) ) {
    require_once(plugin_dir_path( __FILE__ ) . 'includes/Yfp_Plugin_Base.php');
}
if ( ! class_exists( 'Yfp_Plugin_Settings_Section' ) ) {
    require_once(plugin_dir_path( __FILE__ ) . 'includes/Yfp_Plugin_Settings_Section.php');
}

class Yfp_Login_Customizer extends Yfp_Plugin_Base
{
    private static $instance = null;

	// Called KEY because they will be POST keys.
	const KEY_USER_RESPONSE = 'yfp-twice-the-answer';
	const KEY_CORRECT_ANSWER = 'yfp-atoe';	// Answer to Everything.
	const DEF_CORRECT_ANSWER = '4242';
	const DEF_LABEL_BEFORE = 'Enter the number:';
	const DEF_LABEL_AFTER = '';
	const DEF_QUOTE_ANSWER = 0;

    protected $pluginSettingsGroup;

	protected $correct_answer;
	protected $label_before;
	protected $label_after;
	protected $quote_answer;

	// Additions for the admin interface.
	const STR_PLUGIN_TITLE = 'YFP Login Form Customizer';
    // The menu text in the settings menu, so keep it short.
	const STR_SETTINGS_MENU_TEXT = 'YFP Login';

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
	protected function get_options_from_db() {
        // Returns false for no option found.
		$options = get_option( self::WP_OPTIONS_KEY_NAME );
		return $options;
	}


	/**
	* Set up defaults, then override them with any saved options found.
    * This inializes all of the values that can be saved in the database.
	*/
	protected function initializeSavedSettings() {

		// Set the defaults.
		$this->correct_answer = self::DEF_CORRECT_ANSWER;
		$this->label_before = self::DEF_LABEL_BEFORE;
		$this->label_after = self::DEF_LABEL_AFTER;
		$this->quote_answer = self::DEF_QUOTE_ANSWER;

		// Use the saved options if they exist.
		$options = $this->get_options_from_db();
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
    * The unescaped html that will displayed above the login field. It
    * is also displayed on the settings page.
    *
    */
    protected function html_above_login_field() {
        $ans = $this->quote_answer ? '"' . $this->correct_answer . '"' : $this->correct_answer;
        $labelText = sprintf('%s %s %s', $this->label_before, $ans, $this->label_after);
        return $labelText;
    }

	/**
	* Echoes the added field to the login form.
	*/
	public function cb_echo_to_login_form(){
        $labelText = $this->html_above_login_field();
?>
    <p>
		<label for="<?php esc_attr_e(self::KEY_USER_RESPONSE); ?>"><?php esc_html_e($labelText); ?><br>
		<input type="text" size="20" value="" class="input" id="<?php esc_attr_e(self::KEY_USER_RESPONSE); ?>" name="<?php esc_attr_e(self::KEY_USER_RESPONSE); ?>"></label>
		<input type="hidden" name="<?php esc_attr_e(self::KEY_CORRECT_ANSWER); ?>" value="<?php esc_attr_e($this->correct_answer); ?>" />
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
	protected function initAdmin() {
		if (is_admin() ) {
            // Provide access to the setting page that will be created.
            $this->hook('admin_menu', 'create_menu_link_to_settings_page');
            $this->hook('admin_init', 'settings_api_init');
            $this->addPluginSettingsLink();
		}
	}

	/**
	 * Add the options page to the Settings menu.
	 */
	public function create_menu_link_to_settings_page() {

		// This page will be under "Settings"  in the admin area.
		// This function is a simple wrapper for a call to add_submenu_page().
		add_options_page(
			self::STR_PLUGIN_TITLE . ' Settings', // Title for the browser's title tag.
			self::STR_SETTINGS_MENU_TEXT, // Menu text, show under Settings.
			'manage_options', // Which users can use this.
			self::ADMIN_MENU_SLUG, // Menu slug
			array( $this, 'cb_build_settings_page' )
		);
	}

	/**
	 * Options page callback, this creates the admin settings page content.
	 */
	public function cb_build_settings_page() {
		?>
		<div class="wrap">
			<h2><?php echo self::STR_PLUGIN_TITLE; ?> Settings</h2>
			<p>The defaults will work for most people, but any of the values can be
			changed to different strings. Before and After text will be used in
			the form's label around "the answer".</p>

            <p>With the current settings, the text on the login page looks like this:</p>
            <div style="display: inline-block; padding: 1em; background: rgba(255,255,255,.5); border: 1px solid #aaa;"
                ><?php esc_html_e($this->html_above_login_field()); ?></div>

			<form method="post" action="options.php">
			<?php
				// The option group. This must match the group name used in register_setting().
                // This prints out all hidden setting fields for the page.
				settings_fields( $this->pluginSettingsGroup );
				// This will output the section titles wrapped in h3 tags and the settings fields wrapped in tables.
				do_settings_sections( self::ADMIN_MENU_SLUG );
				submit_button();
			?>
			</form>
		</div>
		<?php
	}

    protected function addSettingsSection1($secName, $pageSlug) {
        $ss1 = new Yfp_Plugin_Settings_Section(
                $secName, $pageSlug, 'All settings', array( $this, 'form_cb_section_1_html' ));
        $ss1->add_field('The answer', array( $this, 'form_cb_html_answer_input' ));
        $ss1->add_field('Wrap in quotes', array( $this, 'form_cb_html_quote_answer' ));
        // It is possible to send information in an optional array.
        $ss1->add_field('Before the answer', array( $this, 'form_cb_html_before_input' ), array('was_bad' => 'no', ));
        $ss1->add_field('After the answer', array( $this, 'form_cb_html_after_input' ));
    }


	/**
	 * Register and add settings.
	 */
	public function settings_api_init() {

        // Attach a page and settings group to a group of settings fields.
        $this->addSettingsSection1('somethingunique', self::ADMIN_MENU_SLUG);
        // Tie the wp_otions key to the settings group.
        register_setting(
            $this->pluginSettingsGroup, // Option group
            self::WP_OPTIONS_KEY_NAME, // tTe option name being registerd, same as used in the get_option() call.
            array( $this, 'sanitize_options' ) // Sanitize callback, used for all options
        );
    }

    /**
    * Compare a value to the value found in the key of an array.
    * It is considered changed if the is not found in the array.
    *
    * @param string $origKey
    * @param array $origArr
    * @param mixed $newValue
    * @return bool
    */
    protected function isValueDifferent($origKey, $origArr, $newValue) {
        return !array_key_exists($origKey, $origArr) || $origArr[$origKey] !== $newValue;
    }

	/**
	 * Sanitize each setting field as needed. If a key does not get returned
	 * it won't be saved. Because all of the used data is in a single options
	 * key, that means that values will return to the default. So code was
	 * added to fix that: each value is sanitized every time.
	 *
     * This is not called before the form fields are displayed, and so cannot be used
     * to modifiy any information in the form field text.
     *
	 * @param array $input - Contains all settings fields as array keys
	 * @return array - The sanitized input array.
	 */
	public function sanitize_options( $input ) {

		$new_input = array();
		// New installation or saving for the first time.
		$current_options = $this->get_options_from_db();
        // Will allow updating of everything because the keys will not exist.
		if (!is_array($current_options)) {$current_options = array();}

		// Determines the type of message displayed, will be changed if there is an error.
		$type = 'updated';
		//$data = debug_options_arr($input);
        $message = '';

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
                if ($this->isValueDifferent($check_key, $current_options, $new_input[$check_key])) {
					$message .= __('The "correct answer" response was updated. ');
				}
			}
			else {
				$type = 'error';
				$message .= __('The "answer" field cannot be empty, must only be alphanumeric, and must be 20 or fewer characters. ');
				// This was initialized to a default if the value was not otherwise set.
				$new_input[$check_key] = $this->correct_answer;
			}
		}

		// Check the Quote inputs, it's a checkbox.
		$check_key = self::WPO_KEY_QUOTE_ANSWER;
        $val = isset( $input[$check_key] ) ? 1 : 0;
		$new_input[$check_key] = $val;
		// Only update the message if the value has changed.
        if ($this->isValueDifferent($check_key, $current_options, $new_input[$check_key])) {
			$message .= __('The "Add Quotes" field updated. ');
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
                if ($this->isValueDifferent($check_key, $current_options, $new_input[$check_key])) {
					$message .= __('The "before" field updated. ');
				}
			}
			else {
				$type = 'error';
				$message .= __('The "before" field must be 100 or fewer characters. ');
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
                if ($this->isValueDifferent($check_key, $current_options, $new_input[$check_key])) {
					$message .= __('The "after" field was updated. ');
				}
			}
			else {
				$type = 'error';
				$message .= __('The "after" field must be 100 or fewer characters. ');
				// This was initialized to a default if the value was not otherwise set.
				$new_input[$check_key] = $this->label_after;
			}
		}

        //$message .= '<br />The input array: ' . print_r($input, true);
		//$message .= '<br />The new input: ' . print_r($new_input, true);

		//$message .= ' | debug: ' . debug_options_arr($new_input);
		if ('' !== $message) {

			add_settings_error(
				'unusedUniqueIdentifyer42',
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
	public function form_cb_section_1_html() {

		_e('All setting for the plugin are on this page. Enter any changes to the current values below:');
        if (0) {
            $tpl = 'Name: "%s" &mdash; Value: "%s"<br />';
            $vars = array(
                'answer' => $this->correct_answer,
                'quote it' => $this->quote_answer,
                'before' => $this->label_before,
                'after' => $this->label_after,
            );
		    echo 'Setting at form loading: <br />';
		    foreach ($vars as $varname => $val) {
			    printf($tpl, $varname, $val);
		    }
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
	 * This echoes and input element with name and id, and any extra description information.
     * All of the form_cb_html_* functions are called to build the settings page.
     * Which page and which sections they will display in was defined where the
     * callback names were defined.
	 */
	public function form_cb_html_answer_input() {
		// params are: id, name, value
		printf(self::TPL_INPUT_ELEM,
			$this->form_input_id_in_array(self::WP_OPTIONS_KEY_NAME, self::WPO_KEY_ANSWER),
			$this->form_input_name_in_array(self::WP_OPTIONS_KEY_NAME, self::WPO_KEY_ANSWER),
			esc_html__($this->correct_answer, ENT_HTML5)
		);
		echo '<br />This is the text that the user must enter to login. It must be alpha-numeric and be less than 20 characters. It will be displayed as part of the login screen.';
	}

	public function form_cb_html_quote_answer() {
		$tplChk = '<input id="%s" name="%s" type="checkbox" value="1" %s />';
		printf($tplChk,
			$this->form_input_id_in_array(self::WP_OPTIONS_KEY_NAME, self::WPO_KEY_QUOTE_ANSWER),
			$this->form_input_name_in_array(self::WP_OPTIONS_KEY_NAME, self::WPO_KEY_QUOTE_ANSWER),
			checked( 1, $this->quote_answer, false )
		);
		echo '<br />The "answer" on the login screen will be wrapped in quotes if this box is checked.';
	}

    // It is possible to send information in an optional array.
	public function form_cb_html_before_input($opt=array()) {
		// params are: id, name, value
		printf(self::TPL_INPUT_ELEM,
			$this->form_input_id_in_array(self::WP_OPTIONS_KEY_NAME, self::WPO_KEY_LBL_BEFORE),
			$this->form_input_name_in_array(self::WP_OPTIONS_KEY_NAME, self::WPO_KEY_LBL_BEFORE),
			esc_html__($this->label_before)
		);
		echo '<br />Optional. This text will be displayed immediately before "the answer".';
		//htmlspecialchars( print_r($opt) );
	}

	public function form_cb_html_after_input() {
		// params are: id, name, value
		printf(self::TPL_INPUT_ELEM,
			$this->form_input_id_in_array(self::WP_OPTIONS_KEY_NAME, self::WPO_KEY_LBL_AFTER),
			$this->form_input_name_in_array(self::WP_OPTIONS_KEY_NAME, self::WPO_KEY_LBL_AFTER),
            esc_html__($this->label_after)
		);
		echo '<br />Optional. This text will be displayed immediately after "the answer".';
	}


    protected function initializePluginSettings() {
        $fqFile = __FILE__;
        $this->plugin_basename = plugin_basename($fqFile);
        $this->plugin_path = dirname($fqFile);
        $this->plugin_url_dir = plugins_url('', $fqFile);
        // Needed by the parent class to create a link.
        $this->plugin_menu_slug = self::ADMIN_MENU_SLUG;
        // Used on input forms for this plugin.
        $this->pluginSettingsGroup = $this->keySanitize($this->plugin_basename) . '_settings_group';
        $this->initializeSavedSettings();
    }


    /**
    * Add the hooks that make this run.
    */
    protected function init() {
        // Provides info to parent class, so do it early.
        $this->initializePluginSettings();
        add_action('login_form', array($this, 'cb_echo_to_login_form', ));
        add_filter('wp_authenticate_user', array($this, 'form_authenticate'), 10, 3);
        $this->initAdmin();
        parent::__construct();
    }


    /**
    * The trend seems to be to make a singleton so the object can be
    * accessed once it is built.
    * So we'll try that.
    */
    protected function __construct(){}
    public static function instance(){
        if (!isset(self::$instance)) {
            self::$instance = new self;
            self::$instance->init();
        }
        return self::$instance;
    }
}


//new Yfp_Login_Customizer();
// No point in creating the slideshow just to uninstall it.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    // Launch the plugin.
    Yfp_Login_Customizer::instance();
}

