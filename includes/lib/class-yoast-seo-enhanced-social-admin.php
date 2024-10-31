<?php

require_once plugin_dir_path( __FILE__ ) . 'class-resoc-social-editor-facebook-editor.php';

class RSE_WPSEO_Enhanced_Social_Admin extends WPSEO_Social_Admin {

  public function get_meta_section() {
    global $GLOBALS;

    // Get the original section
    $section = parent::get_meta_section();

    // Replace the Facebook entry
    for ( $i = 0; $i < count( $section->tabs ); $i++ ) {
      $tab = $section->tabs[$i];

      // Read the private variable "name"
      // See http://stackoverflow.com/questions/1762135/accessing-private-variable-from-member-function-in-php/2448499#2448499
      if (version_compare(PHP_VERSION, '5.3.0') >= 0 ) {
        $myClassReflection = new ReflectionClass( get_class( $tab ) );
        $secret = $myClassReflection->getProperty( 'name' );
        $secret->setAccessible( true );
        $name = $secret->getValue( $tab );
      }
      else {
        $tabAsArray = ( array ) $tab;
        $name = $tabAsArray['name'];
      }

      if ( $name == 'facebook' ) {
        // The same, but different
        $section->tabs[$i] = new WPSEO_Metabox_Form_Tab(
  				'facebook',
  				Resoc_Social_Editor_Facebook_Editor::facebook_editor( $GLOBALS['post'] ),
          '<span class="screen-reader-text">' . __( 'Facebook / LinkedIn / Open Graph metadata', 'wordpress-seo' ) . '</span><span class="dashicons dashicons-facebook-alt"></span>',
  				array(
            'link_aria_label' => __( 'Facebook / LinkedIn / Open Graph metadata', 'wordpress-seo' ),
            'link_class'      => 'yoast-tooltip yoast-tooltip-se',
            'single'          => $single,
          )
        );
      }
    }

    // Return patched section
    return $section;
  }
}
