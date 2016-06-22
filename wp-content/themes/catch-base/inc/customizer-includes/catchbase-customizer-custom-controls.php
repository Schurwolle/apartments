<?php
/**
 * The template for adding Customizer Custom Controls
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

	//Custom control for dropdown category multiple select
	class Catchbase_Customize_Dropdown_Categories_Control extends WP_Customize_Control {
		public $type = 'dropdown-categories';

		public $name;

		public function render_content() {
			$dropdown = wp_dropdown_categories(
				array(
					'name'             => $this->name,
					'echo'             => 0,
					'hide_empty'       => false,
					'show_option_none' => false,
					'hide_if_empty'    => false,
				)
			);

			$dropdown = str_replace('<select', '<select multiple = "multiple" style = "height:95px;" ' . $this->get_link(), $dropdown );

			printf(
				'<label class="customize-control-select"><span class="customize-control-title">%s</span> %s</label>',
				$this->label,
				$dropdown
			);

			echo '<p class="description">'. __( 'Hold down the Ctrl (windows) / Command (Mac) button to select multiple options.', 'catch-base' ) . '</p>';
		}
	}

	//Custom control for dropdown category multiple select
	class Catchbase_Important_Links extends WP_Customize_Control {
        public $type = 'important-links'; 
        
        public function render_content() {
        	//Add Theme instruction, Support Forum, Changelog, Donate link, Review, Facebook, Twitter, Google+, Pinterest links
            $important_links = array(
							'theme_instructions' => array( 
								'link'	=> esc_url( 'http://catchthemes.com/theme-instructions/catch-base/' ),
								'text' 	=> __( 'Theme Instructions', 'catch-base' ),
								),
							'support' => array( 
								'link'	=> esc_url( 'http://catchthemes.com/support/' ),
								'text' 	=> __( 'Support', 'catch-base' ),
								),
							'changelog' => array( 
								'link'	=> esc_url( 'http://catchthemes.com/changelogs/catch-base-theme/' ),
								'text' 	=> __( 'Changelog', 'catch-base' ),
								),
							'donate' => array( 
								'link'	=> esc_url( 'http://catchthemes.com/donate/' ),
								'text' 	=> __( 'Donate Now', 'catch-base' ),
								),
							'review' => array( 
								'link'	=> esc_url( 'https://wordpress.org/support/view/theme-reviews/catch-base' ),
								'text' 	=> __( 'Review', 'catch-base' ),
								),
							'facebook' => array( 
								'link'	=> esc_url( 'https://www.facebook.com/catchthemes/' ),
								'text' 	=> __( 'Facebook', 'catch-base' ),
								),
							'twitter' => array( 
								'link'	=> esc_url( 'https://twitter.com/catchthemes/' ),
								'text' 	=> __( 'Twitter', 'catch-base' ),
								),
							'gplus' => array( 
								'link'	=> esc_url( 'https://plus.google.com/+Catchthemes/' ),
								'text' 	=> __( 'Google+', 'catch-base' ),
								),
							'pinterest' => array( 
								'link'	=> esc_url( 'http://www.pinterest.com/catchthemes/' ),
								'text' 	=> __( 'Pinterest', 'catch-base' ),
								),
							);
			foreach ( $important_links as $important_link) {
				echo '<p><a target="_blank" href="' . $important_link['link'] .'" >' . esc_attr( $important_link['text'] ) .' </a></p>';
			}
        }
    }