<?php
/**
 * The template for adding Featured Content Settings in Customizer
 *
 * @package Catch Themes
 * @subpackage Catch Base
 * @since Catch Base 1.0 
 */

if ( ! defined( 'CATCHBASE_THEME_VERSION' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}
	// Featured Content Options
	if ( 4.3 > get_bloginfo( 'version' ) ) {
		$wp_customize->add_panel( 'catchbase_featured_content_options', array(
		    'capability'     => 'edit_theme_options',
			'description'    => __( 'Options for Featured Content', 'catch-base' ),
		    'priority'       => 400,
		    'title'    		 => __( 'Featured Content', 'catch-base' ),
		) );


		$wp_customize->add_section( 'catchbase_featured_content_settings', array(
			'panel'			=> 'catchbase_featured_content_options',
			'priority'		=> 1,
			'title'			=> __( 'Featured Content Options', 'catch-base' ),
		) );
	}
	else {
		$wp_customize->add_section( 'catchbase_featured_content_settings', array(
			'priority'      => 400,
			'title'			=> __( 'Featured Content', 'catch-base' ),
		) );
	}

	$wp_customize->add_setting( 'catchbase_theme_options[featured_content_option]', array(
		'capability'		=> 'edit_theme_options',
		'default'			=> $defaults['featured_content_option'],
		'sanitize_callback' => 'catchbase_sanitize_select',
	) );

	$catchbase_featured_slider_content_options = catchbase_featured_slider_content_options();
	$choices = array();
	foreach ( $catchbase_featured_slider_content_options as $catchbase_featured_slider_content_option ) {
		$choices[$catchbase_featured_slider_content_option['value']] = $catchbase_featured_slider_content_option['label'];
	}

	$wp_customize->add_control( 'catchbase_theme_options[featured_content_option]', array(
		'choices'  	=> $choices,
		'label'    	=> __( 'Enable Featured Content on', 'catch-base' ),
		'priority'	=> '1',
		'section'  	=> 'catchbase_featured_content_settings',
		'settings' 	=> 'catchbase_theme_options[featured_content_option]',
		'type'	  	=> 'select',
	) );

	$wp_customize->add_setting( 'catchbase_theme_options[featured_content_layout]', array(
		'capability'		=> 'edit_theme_options',
		'default'			=> $defaults['featured_content_layout'],
		'sanitize_callback' => 'catchbase_sanitize_select',
	) );

	$catchbase_featured_content_layout_options = catchbase_featured_content_layout_options();
	$choices = array();
	foreach ( $catchbase_featured_content_layout_options as $catchbase_featured_content_layout_option ) {
		$choices[$catchbase_featured_content_layout_option['value']] = $catchbase_featured_content_layout_option['label'];
	}

	$wp_customize->add_control( 'catchbase_theme_options[featured_content_layout]', array(
		'active_callback'	=> 'catchbase_is_featured_content_active',
		'choices'  			=> $choices,
		'label'    			=> __( 'Select Featured Content Layout', 'catch-base' ),
		'priority'			=> '2',
		'section'  			=> 'catchbase_featured_content_settings',
		'settings' 			=> 'catchbase_theme_options[featured_content_layout]',
		'type'	  			=> 'select',
	) );

	$wp_customize->add_setting( 'catchbase_theme_options[featured_content_position]', array(
		'capability'		=> 'edit_theme_options',
		'default'			=> $defaults['featured_content_position'],
		'sanitize_callback' => 'catchbase_sanitize_checkbox'
	) );

	$wp_customize->add_control( 'catchbase_theme_options[featured_content_position]', array(
		'active_callback'	=> 'catchbase_is_featured_content_active',
		'label'				=> __( 'Check to Move above Footer', 'catch-base' ),
		'priority'			=> '3',
		'section'  			=> 'catchbase_featured_content_settings',
		'settings'			=> 'catchbase_theme_options[featured_content_position]',
		'type'				=> 'checkbox',
	) );

	$wp_customize->add_setting( 'catchbase_theme_options[featured_content_type]', array(
		'capability'		=> 'edit_theme_options',
		'default'			=> $defaults['featured_content_type'],
		'sanitize_callback'	=> 'catchbase_sanitize_select',
	) );

	$catchbase_featured_content_types = catchbase_featured_content_types();
	$choices = array();
	foreach ( $catchbase_featured_content_types as $catchbase_featured_content_type ) {
		$choices[$catchbase_featured_content_type['value']] = $catchbase_featured_content_type['label'];
	}

	$wp_customize->add_control( 'catchbase_theme_options[featured_content_type]', array(
		'active_callback'	=> 'catchbase_is_featured_content_active',
		'choices'  			=> $choices,
		'label'    			=> __( 'Select Content Type', 'catch-base' ),
		'priority'			=> '4',
		'section'  			=> 'catchbase_featured_content_settings',
		'settings' 			=> 'catchbase_theme_options[featured_content_type]',
		'type'	  			=> 'select',
	) );

	$wp_customize->add_setting( 'catchbase_theme_options[featured_content_headline]', array(
		'capability'		=> 'edit_theme_options',
		'default'			=> $defaults['featured_content_headline'],
		'sanitize_callback'	=> 'wp_kses_post',
		'transport'			=> 'postMessage',
	) );

	$wp_customize->add_control( 'catchbase_theme_options[featured_content_headline]' , array(
		'active_callback'	=> 'catchbase_is_demo_featured_content_inactive',
		'description'		=> __( 'Leave field empty if you want to remove Headline', 'catch-base' ),
		'label'    			=> __( 'Headline for Featured Content', 'catch-base' ),
		'priority'			=> '5',
		'section'  			=> 'catchbase_featured_content_settings',
		'settings' 			=> 'catchbase_theme_options[featured_content_headline]',
		'type'	   			=> 'text',
		)
	);

	$wp_customize->add_setting( 'catchbase_theme_options[featured_content_subheadline]', array(
		'capability'		=> 'edit_theme_options',
		'default'			=> $defaults['featured_content_subheadline'],
		'sanitize_callback'	=> 'wp_kses_post',
		'transport'			=> 'postMessage',
	) );

	$wp_customize->add_control( 'catchbase_theme_options[featured_content_subheadline]' , array(
		'active_callback'	=> 'catchbase_is_demo_featured_content_inactive',
		'description'		=> __( 'Leave field empty if you want to remove Sub-headline', 'catch-base' ),
		'label'    			=> __( 'Sub-headline for Featured Content', 'catch-base' ),
		'priority'			=> '6',
		'section'  			=> 'catchbase_featured_content_settings',
		'settings' 			=> 'catchbase_theme_options[featured_content_subheadline]',
		'type'	   			=> 'text',
		) 
	);

	$wp_customize->add_setting( 'catchbase_theme_options[featured_content_number]', array(
		'capability'		=> 'edit_theme_options',
		'default'			=> $defaults['featured_content_number'],
		'sanitize_callback'	=> 'catchbase_sanitize_number_range',
	) );

	$wp_customize->add_control( 'catchbase_theme_options[featured_content_number]' , array(
		'active_callback'	=> 'catchbase_is_demo_featured_content_inactive',
		'description'		=> __( 'Save and refresh the page if No. of Featured Content is changed (Max no of Featured Content is 20)', 'catch-base' ),
			'input_attrs' 	=> array(
					            'style' => 'width: 45px;',
					            'min'   => 0,
					            'max'   => 20,
					            'step'  => 1,
					        	),
		'label'    			=> __( 'No of Featured Content', 'catch-base' ),
		'priority'			=> '7',
		'section'  			=> 'catchbase_featured_content_settings',
		'settings' 			=> 'catchbase_theme_options[featured_content_number]',
		'type'	   			=> 'number',
		) 
	);

	$wp_customize->add_setting( 'catchbase_theme_options[featured_content_show]', array(
		'capability'		=> 'edit_theme_options',
		'default'			=> $defaults['featured_content_show'],
		'sanitize_callback'	=> 'catchbase_sanitize_select',
	) ); 

	$catchbase_featured_content_show = catchbase_featured_content_show();
	$choices = array();
	foreach ( $catchbase_featured_content_show as $catchbase_featured_content_shows ) {
		$choices[$catchbase_featured_content_shows['value']] = $catchbase_featured_content_shows['label'];
	}

	$wp_customize->add_control( 'catchbase_theme_options[featured_content_show]', array(
		'active_callback'	=> 'catchbase_is_demo_featured_content_inactive',
		'choices'  			=> $choices,
		'label'    			=> __( 'Display Content', 'catch-base' ),
		'priority'			=> '8',
		'section'  			=> 'catchbase_featured_content_settings',
		'settings' 			=> 'catchbase_theme_options[featured_content_show]',
		'type'	  			=> 'select',
	) );

	//loop for featured page content
	for ( $i=1; $i <= $options['featured_content_number'] ; $i++ ) {
		$wp_customize->add_setting( 'catchbase_theme_options[featured_content_page_'. $i .']', array(
			'capability'		=> 'edit_theme_options',
			'sanitize_callback'	=> 'catchbase_sanitize_page',
		) );

		$wp_customize->add_control( 'catchbase_featured_content_page_'. $i .'', array(
			'active_callback'	=> 'catchbase_is_demo_featured_content_inactive',
			'label'    			=> __( 'Featured Page', 'catch-base' ) . ' ' . $i ,
			'priority'			=> '9' . $i,
			'section'  			=> 'catchbase_featured_content_settings',
			'settings' 			=> 'catchbase_theme_options[featured_content_page_'. $i .']',
			'type'	   			=> 'dropdown-pages',
		) );
	}
// Featured Content Setting End