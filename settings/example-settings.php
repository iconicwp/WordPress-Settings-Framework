<?php
/**
 * WordPress Settings Framework
 *
 * @author Gilbert Pellegrom, James Kemp
 * @link https://github.com/gilbitron/WordPress-Settings-Framework
 * @license MIT
 */

/**
 * Define your settings
 * 
 * The first parameter of this filter should be wpsf_register_settings_[options_group],
 * in this case "my_example_settings".
 * 
 * Your "options_group" is the second param you use when running new WordPressSettingsFramework()
 * from your init function. It's importnant as it differentiates your options from others.
 * 
 * To use the tabbed example, simply change the second param in the filter below to 'wpsf_tabbed_settings'
 * and check out the tabbed settings function on line 156.
 */
 
add_filter( 'wpsf_register_settings_my_example_settings', 'wpsf_tabless_settings' );

/**
 * Tabless example
 */
function wpsf_tabless_settings( $wpsf_settings ) {

    // General Settings section
    $wpsf_settings[] = array(
        'section_id' => 'general',
        'section_title' => 'General Settings',
        'section_description' => 'Some intro description about this section.',
        'section_order' => 5,
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
                'id' => 'password',
                'title' => 'Password',
                'desc' => 'This is a description.',
                'placeholder' => 'This is a placeholder.',
                'type' => 'password',
                'std' => 'Example'
            ),
            array(
                'id' => 'textarea',
                'title' => 'Textarea',
                'desc' => 'This is a description.',
                'placeholder' => 'This is a placeholder.',
                'type' => 'textarea',
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
            array(
                'id' => 'radio',
                'title' => 'Radio',
                'desc' => 'This is a description.',
                'type' => 'radio',
                'std' => 'green',
                'choices' => array(
                    'red' => 'Red',
                    'green' => 'Green',
                    'blue' => 'Blue'
                )
            ),
            array(
                'id' => 'checkbox',
                'title' => 'Checkbox',
                'desc' => 'This is a description.',
                'type' => 'checkbox',
                'std' => 1
            ),
            array(
                'id' => 'checkboxes',
                'title' => 'Checkboxes',
                'desc' => 'This is a description.',
                'type' => 'checkboxes',
                'std' => array(
                    'red',
                    'blue'
                ),
                'choices' => array(
                    'red' => 'Red',
                    'green' => 'Green',
                    'blue' => 'Blue'
                )
            ),
            array(
                'id' => 'color',
                'title' => 'Color',
                'desc' => 'This is a description.',
                'type' => 'color',
                'std' => '#ffffff'
            ),
            array(
                'id' => 'file',
                'title' => 'File',
                'desc' => 'This is a description.',
                'type' => 'file',
                'std' => ''
            ),
            array(
                'id' => 'editor',
                'title' => 'Editor',
                'desc' => 'This is a description.',
                'type' => 'editor',
                'std' => ''
            )
        )
    );

    // More Settings section
    $wpsf_settings[] = array(
        'section_id' => 'more',
        'section_title' => 'More Settings',
        'section_order' => 10,
        'fields' => array(
            array(
                'id' => 'more-text',
                'title' => 'More Text',
                'desc' => 'This is a description.',
                'type' => 'text',
                'std' => 'This is std'
            ),
        )
    );

    return $wpsf_settings;
}

/**
 * Tabbed example
 */

function wpsf_tabbed_settings( $wpsf_settings ) {
    
    // Tabs
    
    // Tab 1
    $wpsf_settings['tabs'][] = array(
		'id' => 'tab_1',
		'title' => __('Tab 1'),
	);
	
	// Tab 2
	$wpsf_settings['tabs'][] = array(
		'id' => 'tab_2',
		'title' => __('Tab 2'),
	);
	
	// Settings

    // Settings Section
    $wpsf_settings['sections'][] = array(
        'tab_id' => 'tab_1',
        'section_id' => 'section_1',
        'section_title' => 'Section 1',
        'section_order' => 10,
        'fields' => array(
            array(
                'id' => 'text-1',
                'title' => 'Text',
                'desc' => 'This is a description.',
                'type' => 'text',
                'std' => 'This is std'
            ),
        )
    );
    
    // Settings Section
    $wpsf_settings['sections'][] = array(
        'tab_id' => 'tab_1',
        'section_id' => 'section_2',
        'section_title' => 'Section 2',
        'section_order' => 10,
        'fields' => array(
            array(
                'id' => 'text-2',
                'title' => 'Text',
                'desc' => 'This is a description.',
                'type' => 'text',
                'std' => 'This is std'
            ),
        )
    );
    
    // Settings Section
    $wpsf_settings['sections'][] = array(
        'tab_id' => 'tab_2',
        'section_id' => 'section_3',
        'section_title' => 'Section 3',
        'section_order' => 10,
        'fields' => array(
            array(
                'id' => 'text-3',
                'title' => 'Text',
                'desc' => 'This is a description.',
                'type' => 'text',
                'std' => 'This is std'
            ),
        )
    );

    return $wpsf_settings;
}
