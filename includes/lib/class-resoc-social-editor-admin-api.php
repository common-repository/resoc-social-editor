<?php

require_once plugin_dir_path( __FILE__ ) . 'class-resoc-social-editor-facebook-editor.php';
require_once plugin_dir_path( __FILE__ ) . 'class-resoc-social-editor-utils.php';
require_once plugin_dir_path( __FILE__ ) . 'class-resoc-social-editor-upgrades.php';

require_once ABSPATH . 'wp-admin/includes/plugin.php';

if ( ! defined( 'ABSPATH' ) ) exit;

class Resoc_Social_Editor_Admin_API {

	/**
	 * Constructor function
	 */
	public function __construct () {
		add_action( 'save_post',
			array( $this, 'save_social_data' ) );
		// Make sure to run this action just before Yoast SEO
		// (Yoast is using the default priority, which is 10)
		add_action( 'add_meta_boxes',
      array( $this, 'save_meta_boxes' ), 9 );

    add_action( 'admin_menu',
      array( $this, 'settings_menu' )
    );
    add_action('admin_init',
      array( $this, 'process_settings_form' )
    );

    add_action(
      'wp_ajax_' . Resoc_Social_Editor::PLUGIN_SLUG . '_create_overlay',
      array( $this, 'create_overlay' )
    );

    add_action( 'upgrader_process_complete', 'Resoc_Social_Editor_Upgrades::upgrade', 10, 2 );
  }

  public function create_overlay() {
    header("Content-type: application/json");

    // See http://stackoverflow.com/questions/2496455/why-are-post-variables-getting-escaped-in-php
    $data = json_decode( stripslashes( $_REQUEST['request'] ), true );

    $image_id = $data['image_id'];
    $master_image = Resoc_Social_Editor_Utils::get_image_content_by_id( $image_id );

    $image_settings = $data['image_settings'];

		$request = array(
      'master_image_base64' => base64_encode( $master_image ),
      'image_settings' => array(
        'center_x' => $image_settings['imageCenterX'],
        'center_y' => $image_settings['imageCenterY'],
        'scale' => $image_settings['imageContainerWidthRatio']
      )
    );
    $request = Resoc_Social_Editor_Utils::add_analytics_data( $request );

    try {
      $overlay_id = Resoc_Social_Editor_Utils::generate_resoc_image(
        'https://resoc.io/api/overlay',
        $request,
        'Resoc-Overlay-' . Resoc_Social_Editor_Utils::time_to_filename_fragment() . '.png'
      );
      update_option( Resoc_Social_Editor::OPTION_SKIP_OVERLAY_CREATION_SUGGESTION, true );

      echo json_encode(
        array(
          'image_id' => $overlay_id,
          'image_url' => wp_get_attachment_url( $overlay_id )
        ),
        true
      );
    }
    catch(Exception $e) {
      // TODO: Process the error
      error_log("ERROR: " . $e);
    }

    wp_die();
  }

	public function patch_yoast_seo_meta_box() {
		global $GLOBALS;

    // Useless if Yoast is not even installed and active
    if (! Resoc_Social_Editor_Utils::is_yoast_seo_active() ) {
      return false;
    }

		// This global is always supposed to be available, but if that's not the
		// case, just stop here
		if ( ! isset( $GLOBALS['wpseo_metabox'] ) ) return false;

		// At this point, Yoast SEO code is available
		require_once plugin_dir_path( __FILE__ ) . 'class-yoast-seo-enhanced-meta.php';

		$GLOBALS['wpseo_metabox'] = new RSE_WPSEO_Enhanced_Metabox( $GLOBALS['wpseo_metabox'] );

		return true;
	}

	public function save_social_data ( $post_id ) {
    // Title and description
    $title = $_POST['rse-title'];
    $description = $_POST['rse-description'];

    update_post_meta( $post_id,
      Resoc_Social_Editor::OG_TITLE, $title );
    update_post_meta( $post_id,
      Resoc_Social_Editor::OG_DESCRIPTION, $description );

    // Image
    $image_id = $_POST['rse-og-image-id'];
    if (! $image_id) {
      // Not set? Maybe this is a new post, or user didn't assign it.
      // In any case, there is nothing to do
      return;
    }

		$image_settings = $_POST['rse-og-image-settings'];
		// See http://stackoverflow.com/questions/2496455/why-are-post-variables-getting-escaped-in-php
    $image_settings = stripslashes( $image_settings );
    $overlay_id = $_POST['rse-og-overlay-image-id'];

		// Check if the data have changed
		$existing_image_settings = get_post_meta( $post_id,
			Resoc_Social_Editor::OG_MASTER_IMAGE_SETTINGS, true );
		$existing_image_id = get_post_meta( $post_id,
			Resoc_Social_Editor::OG_MASTER_IMAGE_ID, true );
    $existing_overlay_id = get_post_meta( $post_id,
			Resoc_Social_Editor::OG_OVERLAY_IMAGE_ID, true );
		if (
      $existing_image_settings &&
      $existing_image_settings == $image_settings &&
      $existing_image_id == $image_id &&
      $existing_overlay_id == $overlay_id
    ) {
      // No change in the data: nothing to do
      error_log("No change, nothing to do");
			return true;
		}

		update_post_meta( $post_id,
			Resoc_Social_Editor::OG_MASTER_IMAGE_SETTINGS, $image_settings );
		update_post_meta( $post_id,
			Resoc_Social_Editor::OG_MASTER_IMAGE_ID, $image_id );
    update_post_meta( $post_id,
      Resoc_Social_Editor::OG_OVERLAY_IMAGE_ID, $overlay_id );
    // Save the fact that the user made a choice regarding the overlay
    update_post_meta( $post_id,
      Resoc_Social_Editor::OG_OVERLAY_IMAGE_SET, true );

    $image_settings = json_decode( $image_settings, true );
		$favicon_design = $image_settings;

		$pic_path = $this->get_picture_url( $post_id );

		$master_image_url = wp_get_attachment_url( $image_id );
		$master_image_result = wp_remote_get( $master_image_url );
		if (is_wp_error( $master_image_result )) {
			// BEST: is there a best way to handle the error?
			error_log( "Cannot download master image: " . $master_image_result->get_error_message() );
			return;
		}
		$master_image = wp_remote_retrieve_body( $master_image_result );

    $overlay_image = NULL;
    if ( $overlay_id ) {
      $overlay_url = wp_get_attachment_url( $overlay_id );
      $overlay_response = wp_remote_get( $overlay_url );
      if (is_wp_error( $overlay_response )) {
        // BEST: is there a best way to handle the error?
        error_log( "Cannot download overlay image: " . $overlay_response->get_error_message() );
        return;
      }
      $overlay_image = wp_remote_retrieve_body( $overlay_response );
    }

		$request = array(
      'master_image_base64' => base64_encode( $master_image ),
      'image_settings' => array(
        'center_x' => $favicon_design['imageCenterX'],
        'center_y' => $favicon_design['imageCenterY'],
        'scale' => $favicon_design['imageContainerWidthRatio']
      )
    );
    if ( $overlay_image ) {
      $request['overlay_image_base64'] = base64_encode( $overlay_image );
    }
    $request = Resoc_Social_Editor_Utils::add_analytics_data( $request );

    // Compute image file name
    $title = get_the_title( $post_id );
    $image_filename = Resoc_Social_Editor_Utils::post_title_to_image_file_name( $title );

    // Get existing OpenGraph image to update it as an attachement
    $existing_og_image_id = get_post_meta( $post_id, Resoc_Social_Editor::OG_IMAGE_ID, true );

    try {
      $og_image_id = Resoc_Social_Editor_Utils::generate_resoc_image(
        'https://resoc.io/api/og-image',
        $request,
        $image_filename,
        $existing_og_image_id
      );

      update_post_meta(
        $post_id,
        Resoc_Social_Editor::OG_IMAGE_ID, $og_image_id
      );
      update_option( Resoc_Social_Editor::OPTION_SKIP_OVERLAY_CREATION_SUGGESTION, true );
    }
    catch(Exception $e) {
      error_log("Error while generating the OpenGraph image: " . $e );
			// BEST: is there a best way to handle the error?
    }

		return true;
  }

	public function get_picture_dir( $post_id ) {
		return Resoc_Social_Editor::get_files_dir( $post_id );
	}

	/**
	 * Returns http//somesite.com/blog/wp-content/upload/fbrfg/
	 */
	public function get_picture_url( $post_id ) {
		return Resoc_Social_Editor::get_files_url( $post_id );
	}

	/**
	 * Add meta box to the dashboard
	 * @param string $id            Unique ID for metabox
	 * @param string $title         Display title of metabox
	 * @param array  $post_types    Post types to which this metabox applies
	 * @param string $context       Context in which to display this metabox ('advanced' or 'side')
	 * @param string $priority      Priority of this metabox ('default', 'low' or 'high')
	 * @param array  $callback_args Any axtra arguments that will be passed to the display function for this metabox
	 * @return void
	 */
	public function add_meta_box ( $id = '', $title = '', $post_types = array(), $context = 'advanced', $priority = 'default', $callback_args = null ) {
		// Get post type(s)
		if ( ! is_array( $post_types ) ) {
			$post_types = array( $post_types );
		}

		// Generate each metabox
		foreach ( $post_types as $post_type ) {
			add_meta_box( $id, $title, array( $this, 'meta_box_content' ), $post_type, $context, $priority, $callback_args );
		}
	}

	/**
	 * Display metabox content
	 * @param  object $post Post object
	 * @param  array  $args Arguments unique to this metabox
	 * @return void
	 */
	public function meta_box_content ( $post, $args ) {
		echo Resoc_Social_Editor_Facebook_Editor::facebook_editor( $post );
	}

	/**
	 * Save metabox fields
	 * @param  integer $post_id Post ID
	 * @return void
	 */
	public function save_meta_boxes ( $post_id = 0 ) {
		// Try to patch Yoast SEO. If that works, there is nothing more to do
		if ( $this->patch_yoast_seo_meta_box() ) return;

		$this->add_meta_box('rse-meta-facebook', 'Share on Facebook and LinkedIn',
			get_post_types( array( 'public' => true ) ) );
	}

  public function settings_menu() {
    add_options_page(
      'Settings',
      'Resoc Social Editor',
      'manage_options',
      Resoc_Social_Editor::MENU_SETTINGS,
      array( $this, 'create_social_editor_settings_page' )
    );
  }

  public function create_social_editor_settings_page() {
    global $current_user;

    // Prepare variables
    $social_editor_admin_url = admin_url(
      'options-general.php?page=' . Resoc_Social_Editor::MENU_SETTINGS
    );

    $default_overlay_id = get_option( Resoc_Social_Editor::OPTION_DEFAULT_OVERLAY_ID );
    $default_overlay_url = wp_get_attachment_url( $default_overlay_id );

    wp_enqueue_media();

    // Template time!
    include_once( plugin_dir_path(__FILE__) . '../../views' . DIRECTORY_SEPARATOR . 'settings.php' );
  }

  public function process_settings_form() {
    if (
      isset( $_REQUEST[Resoc_Social_Editor::SETTINGS_FORM] ) &&
      '1' == $_REQUEST[Resoc_Social_Editor::SETTINGS_FORM]
    ) {
      $new_id = $_REQUEST[Resoc_Social_Editor::OPTION_DEFAULT_OVERLAY_ID];
      update_option( Resoc_Social_Editor::OPTION_DEFAULT_OVERLAY_ID, $new_id );
      update_option( Resoc_Social_Editor::OPTION_SKIP_OVERLAY_CREATION_SUGGESTION, true );

      $new_usage = ( $_REQUEST[Resoc_Social_Editor::OPTION_SEND_ANONYMOUS_DATA] ) &&
        (1 == $_REQUEST[Resoc_Social_Editor::OPTION_SEND_ANONYMOUS_DATA] );
      update_option( Resoc_Social_Editor::OPTION_SEND_ANONYMOUS_DATA, $new_usage );
    }
  }
}
