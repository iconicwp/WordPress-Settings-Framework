WordPress Settings Framework
============================

The WordPress Settings Framework aims to take the pain out of creating settings pages for your WordPress plugins
by effectively creating a wrapper around the WordPress settings API and making it super simple to create and maintain
settings pages.

This repo is actually a working plugin which demonstrates how to implement WPSF in your plugins. See `wpsf-test.php`
for details.

Setting Up Your Plugin
----------------------

1. Drop `wp-settings-framework.php` in the root of your plugin folder.
2. Create a "settings" folder in your plugin root.
3. Create a settings file in your new "settings" folder (e.g. `settings-general.php`)

Now you can set up your plugin like:

```php
class WPSFTest {

    private $plugin_path;
    private $wpsf;

    function __construct()
    {
        $this->plugin_path = plugin_dir_path( __FILE__ );

        // Include and create a new WordPressSettingsFramework
        require_once( $this->plugin_path .'wp-settings-framework.php' );
        $this->wpsf = new WordPressSettingsFramework( $this->plugin_path .'settings/settings-general.php', 'prefix_settings_general' );
        // Add an optional settings validation filter (recommended)
        add_filter( $this->wpsf->get_option_group() .'_settings_validate', array(&$this, 'validate_settings') );

        // ...
    }

    // This page is added using add_menu_page()
    function settings_page()
	{
	    ?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"></div>
			<h2>WP Settings Framework Example</h2>
			<?php
			// Output your settings form
			$this->wpsf->settings();
			?>
		</div>
		<?php
	}

	function validate_settings( $input )
	{
	    // Do your settings validation here
	    // Same as $sanitize_callback from http://codex.wordpress.org/Function_Reference/register_setting
    	return $input;
	}

    // ...

}
```

Your settings values can be accessed by getting the whole array:

```php
// Get settings
$settings = wpsf_get_settings( 'prefix_settings_general' );
```

Or by getting individual settings:

```php
// Get individual setting
$setting = wpsf_get_setting( 'prefix_settings_general', 'general', 'text' );
```


The Settings Files
------------------

The settings files work by filling the global `$wpsf_settings` array with data in the following format:

```php
$wpsf_settings[] = array(
    'section_id' => 'general', // The section ID (required)
    'section_title' => 'General Settings', // The section title (required)
    'section_description' => 'Some intro description about this section.', // The section description (optional)
    'section_order' => 5, // The order of the section (required)
    'fields' => array(
        array(
            'id' => 'text',
            'title' => 'Text',
            'desc' => 'This is a description.',
            'placeholder' => 'This is a placeholder.',
            'type' => 'text',
            'std' => 'This is std'
        ),
        array(
            'id' => 'select',
            'title' => 'Select',
            'desc' => 'This is a description.',
            'type' => 'select',
            'std' => 'green',
            'choices' => array(
                'red' => 'Red',
                'green' => 'Green',
                'blue' => 'Blue'
            )
        ),

        // add as many fields as you need...

    )
);
```

Valid `fields` values are:

* `id` - Field ID
* `title` - Field title
* `desc` - Field description
* `placeholder` - Field placeholder
* `type` - Field type (text/password/textarea/select/radio/checkbox/checkboxes/color/file)
* `std` - Default value (or selected option)
* `choices` - Array of options (for select/radio/checkboxes)

See `settings/example-settings.php` for an example of possible values.


API Details
-----------

    new WordPressSettingsFramework( string $settings_file [, string $option_group = ''] )

Creates a new settings [option_group](http://codex.wordpress.org/Function_Reference/register_setting) based on a setttings file.

* `$settings_file` - path to the settings file
* `$option_group` - optional "option_group" override (by default this will be set to the basename of the settings file)

<pre>wpsf_get_option_group( $settings_file )</pre>

Converts the settings file name to option group id

* `$settings_file` - path to the settings file

<pre>wpsf_get_settings( $option_group )</pre>

Get an array of settings by the option group id

* `$option_group` - option group id

<pre>wpsf_get_setting( $option_group, $section_id, $field_id )</pre>

Get a setting from an option group

* `$option_group` - option group id
* `$section_id` - section id
* `$field_id` - field id

Note: You can use `wpsf_get_option_group()` to get the option group id from the settings file path.

<pre>wpsf_delete_settings( $option_group )</pre>

Delete all the saved settings from a option group

* `$option_group` - option group id

Hooks & Filters
---------------

**Filters**

* `wpsf_register_settings` - The filter used to register your settings. See `settings/example-settings.php` for an example.
* `[option_group_id]_settings_validate` - Basically the `$sanitize_callback` from [register_setting](http://codex.wordpress.org/Function_Reference/register_setting). Use `$wpsf->get_option_group()` to get the option group id.
* `wpsf_defaults` - Default args for a settings field

**Hooks**

* `wpsf_before_field` - Before a field HTML is output
* `wpsf_before_field_[field_id]` - Before a field HTML is output
* `wpsf_after_field` - After a field HTML is output
* `wpsf_after_field_[field_id]` - After a field HTML is output
* `wpsf_before_settings` - Before settings form HTML is output
* `wpsf_after_settings` - After settings form HTML is output
* `wpsf_before_settings_fields` - Before settings form fields HTML is output (inside the `<form>`)

Credits
-------

The WordPress Settings Framework was created by [Gilbert Pellegrom](http://gilbert.pellegrom.me) from [Dev7studios](http://dev7studios.com).

Please contribute by [reporting bugs](https://github.com/gilbitron/WordPress-Settings-Framework/issues) and submitting [pull requests](https://github.com/gilbitron/WordPress-Settings-Framework/pulls).

Want to say thanks? [Consider tipping me](https://www.gittip.com/gilbitron).

License (MIT)
-------------
Copyright © 2012 Dev7studios

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation
files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy,
modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software
is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR
IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
