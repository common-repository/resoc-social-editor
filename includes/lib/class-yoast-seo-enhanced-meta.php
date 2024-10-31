<?php

require_once plugin_dir_path( __FILE__ ) . 'class-yoast-seo-enhanced-social-admin.php';

class RSE_WPSEO_Enhanced_Metabox extends WPSEO_Metabox {

  public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'wp_insert_post', array( $this, 'save_postdata' ) );
		add_action( 'edit_attachment', array( $this, 'save_postdata' ) );
		add_action( 'add_attachment', array( $this, 'save_postdata' ) );
		add_action( 'post_submitbox_start', array( $this, 'publish_box' ) );
		add_action( 'admin_init', array( $this, 'setup_page_analysis' ) );
		add_action( 'admin_init', array( $this, 'translate_meta_boxes' ) );

		$this->options = WPSEO_Options::get_options( array( 'wpseo', 'wpseo_social' ) );

		// Check if one of the social settings is checked in the options, if so, initialize the social_admin object.
		if ( $this->options['opengraph'] === true || $this->options['twitter'] === true ) {
      // The only real difference: return an instance of
      // RSE_WPSEO_Enhanced_Social_Admin instead of WPSEO_Social_Admin
			$this->social_admin = new RSE_WPSEO_Enhanced_Social_Admin( $this->options );
		}

		$this->editor = new WPSEO_Metabox_Editor();
		$this->editor->register_hooks();

		$this->analysis_seo = new WPSEO_Metabox_Analysis_SEO();
		$this->analysis_readability = new WPSEO_Metabox_Analysis_Readability();
	}

}
