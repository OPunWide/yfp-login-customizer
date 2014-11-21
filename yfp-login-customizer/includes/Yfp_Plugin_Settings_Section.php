<?php
/**
* This is only a class to make it easier to remember how the parts work together.
* It wraps the two functions involved in making a settings section.
*
* To empahsize that there is only one add_settings_section() call per
* settings sections, it is part of the constructor.
*
* The function add_settings_section is more of an organizational tool than anything
* else. It does not have to display any html, but provides information about how to
* display the various add_settings_field calls that are made in the admin section.
*/
class Yfp_Plugin_Settings_Section
{
    const FIELD_ID_PREFIX = 'autoid';
    protected $uid;
    protected $page;
    protected $fieldId = 0;

    /**
    * To empahsize that there is only one add_settings_section() call per
    * settings sections, it is part of the constructor.
    *
    * Use array( $this, 'method_name' ) to provide a method callback.
    *
    * @param mixed $uid - any plug-in wide unique id string.
    * @param mixed $page_slug - where this content should be displayed.
    * @param mixed $title - the string title of this section. Will be wrapped in a H3 tag.
    * @param mixed $html_callback - a function that will display the html for the start of the section.
    */
    public function __construct($uid, $page_slug, $title, $html_callback) {
        $this->uid = $uid;
        $this->page = $page_slug;

        add_settings_section($this->uid, $title, $html_callback, //array( $this, 'section_1_html' )
            $this->page // Options page slug, the page where this section will be displayed.
        );
    }

    /**
    * Adds a call to WP's add_settings_field().
    * Per the codex the $id in add_settings_field should match the name attribute of the
    * forn element that will be created by the html_callback.
    * Per the WP code, the id is a: Slug-name to identify the field. Used in the 'id' attribute of tags.
    *
    * But it is not actually used that way. It only needs
    * to be unique within a settings section. It does not show up in the HTML.
    *
    * @param mixed $id - not used. (Use add_field_with_id if you want to control this)
    * @param mixed $label - The HTML for the field. It will be displayed in a label tag, that is wrapped in TH.
    * @param mixed $html_callback - a function that will display the html for the form element and any other HTML.
    * @param mixed $args - optional arguments needed by the callback.
    */
    public function add_field($label, $html_callback, $args = array()) {
        // Create a unique id within this section.
        $rid = self::FIELD_ID_PREFIX . $this->fieldId++;
        $this->add_field_with_id($rid, $label, $html_callback, $args);
    }
    public function add_field_with_id($id, $label, $html_callback, $args = array()) {
        add_settings_field($id, $label, $html_callback, $this->page, $this->uid, $args);
    }
}
