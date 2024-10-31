<?php
class Resoc_Social_Editor_Utils {
  /**
   * Returns the name of the plugin which might cause a conflict.
   * Returns NULL if there is no such conflicting plugin.
   */
  public static function conflicting_plugin() {
    if ( is_plugin_active( 'business-directory-plugin/business-directory-plugin.php' ) ) {
      return "Business Directory Plugin";
    }
    if ( is_plugin_active( 'wonderm00ns-simple-facebook-open-graph-tags/wonderm00n-open-graph.php' ) ) {
      return "Open Graph for Facebook, Google+ and Twitter Card Tags";
    }
    // TODO: Add additional conflicting plugins

    return NULL;
  }

  public static function is_yoast_seo_active() {
		return is_plugin_active( 'wordpress-seo/wp-seo.php' );
  }

  // All In One SEO Pack
  public static function is_aiosp_active() {
    return defined( 'AIOSEOP_VERSION' );
  }

  public static function add_image_to_media_library( $image_data, $post_id, $filename = 'og-image.jpg', $attach_id = NULL ) {
    $upload_dir = wp_upload_dir();

    // If an existing attachement exists, take its file path and name.
    // This is because using wp_update_attachment_metadata
    // with new file path and name does not affect
    // wp_get_attachment_image_url, which still returns the previous
    // file path and name.
    $file = NULL;
    if ( $attach_id ) {
      $attach_data = wp_get_attachment_metadata( $attach_id );
      if ( $attach_data && isset( $attach_data['file'] ) && ( $attach_data['file'] ) ) {
        $file = $upload_dir['basedir'] . '/' . $attach_data['file'];
      }
    }

    if ( ! $file ) {
      if (wp_mkdir_p($upload_dir['path'])) {
        $file = $upload_dir['path'] . '/' . $filename;
      }
      else {
        $file = $upload_dir['basedir'] . '/' . $filename;
      }
    }

    file_put_contents($file, $image_data);

    if ( ! $attach_id ) {
      // Create new attachement if there is none
      // (else, the image is attached to the existing attachement)
      $wp_filetype = wp_check_filetype($filename, null);
      $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title' => sanitize_file_name($filename),
        'post_content' => '',
        'post_status' => 'inherit'
      );

      $attach_id = wp_insert_attachment( $attachment, $file, $post_id );
    }

    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $file);
    wp_update_attachment_metadata($attach_id, $attach_data);

    return $attach_id;
  }

  public static function generate_resoc_image($api_entry_point_url, $request, $filename = NULL, $attach_id = NULL) {
		$response = wp_remote_post($api_entry_point_url, array(
      'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
      'body'    => json_encode( $request ),
      'timeout' => 10
		));

		if ( is_wp_error( $response ) ) {
      Resoc_Social_Editor_Utils::write_log( "Error while generating: " . $response->get_error_message() );
			throw new Exception( $response->get_error_message() );
    }

    return Resoc_Social_Editor_Utils::add_image_to_media_library( $response['body'], $post_id, $filename, $attach_id );
  }

  public static function get_image_content_by_id( $image_id ) {
		$image_url = wp_get_attachment_url( $image_id );
		$result = wp_remote_get( $image_url );
		if (is_wp_error( $result )) {
			Resoc_Social_Editor_Utils::write_log( "Cannot download image: " . $result->get_error_message() );
			throw new Exception( $result->get_error_message() );
		}
		return wp_remote_retrieve_body( $result );
  }

  // Returns '20181030-114327'
  public static function time_to_filename_fragment() {
    return date('Ymd-his');
  }

  // Analytics / anonymization

  public static function generate_salt() {
    return hash('sha256', strval( rand() ) );
  }

  public static function get_salt() {
    $salt = get_option( Resoc_Social_Editor::OPTION_SALT );
    if ( ! $salt ) {
      $salt = Resoc_Social_Editor_Utils::generate_salt();
      update_option( Resoc_Social_Editor::OPTION_SALT, $salt );
    }
    return $salt;
  }

  public static function anonymize_data( $data ) {
    return hash('sha256',
    Resoc_Social_Editor_Utils::get_salt() . $data
    );
  }

  public static function add_analytics_data( $api_request ) {
    if ( get_option( Resoc_Social_Editor::OPTION_SEND_ANONYMOUS_DATA, false ) ) {
      $api_request['analytics'] = array(
        'hashed_site_url' => Resoc_Social_Editor_Utils::anonymize_data( get_site_url() ),
        'hashed_post_id' => Resoc_Social_Editor_Utils::anonymize_data( get_the_ID() ),
        'hashed_user_id' => Resoc_Social_Editor_Utils::anonymize_data( get_current_user_id() )
      );
    }
    return $api_request;
  }

  public static function write_log( $log )  {
    if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
      return;
    }

    $prefix = "[Resoc Social Editor] ";
    if ( is_array( $log ) || is_object( $log ) ) {
      error_log( $prefix . print_r( $log, true ) );
    } else {
      error_log( $prefix . $log );
    }
  }

  /**
   * $title As returned by get_the_title
   */
  public static function post_title_to_image_file_name( $title ) {
    $title = sanitize_title( $title, 'og-image-' . $post_id );
    if ( !$title || strlen( $title ) <= 0 ) {
      $title = 'og-image-' . $post_id;
    }
    return $title . ".jpg";
  }

  public static function rename_attached_image( $post_id, $image_id, $new_image_filename) {
    $image_file = get_attached_file( $image_id );
    if ( ! $image_file || ! file_exists(  $image_file ) ) {
      Resoc_Social_Editor_Utils::write_log("No actual file for image {$image_id} ({$image_file}");
      return NULL;
    }

    $new_image_file = dirname( $image_file ) . DIRECTORY_SEPARATOR . $new_image_filename;
    Resoc_Social_Editor_Utils::write_log("Copy {$image_file} to {$new_image_file}");
    if ( ! copy( $image_file, $new_image_file ) ) {
      Resoc_Social_Editor_Utils::write_log("Copy failed");
      return NULL;
    }

    if ( wp_delete_attachment( $image_id, true ) === false ) {
      Resoc_Social_Editor_Utils::write_log("Cannot delete existing attachement");
      return NULL;
    }

    // BEST: Refactor add_image_to_media_library and call it here

    $wp_filetype = wp_check_filetype( $new_image_filename, null );
    $attachment = array(
      'post_mime_type' => $wp_filetype['type'],
      'post_title' => sanitize_file_name($new_image_filename),
      'post_content' => '',
      'post_status' => 'inherit'
    );

    $new_image_id = wp_insert_attachment( $attachment, $new_image_file, $post_id );

    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($new_image_id, $new_image_file);
    wp_update_attachment_metadata($new_image_id, $attach_data);

    return $new_image_id;
  }
}
