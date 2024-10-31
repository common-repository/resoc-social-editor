<?php
class Resoc_Social_Editor_Upgrades {
  public static function upgrade( $upgrader_object, $options ) {
    Resoc_Social_Editor_Utils::write_log("Checking upgrades");

    $current_plugin_path_name = plugin_basename( __FILE__ );

    if ( $options['action'] == 'update' && $options['type'] == 'plugin' ) {
      foreach( $options['plugins'] as $each_plugin ) {
        if ( $each_plugin == $current_plugin_path_name ) {
          Resoc_Social_Editor_Utils::write_log("Running upgrades");
          Resoc_Social_Editor_Upgrades::upgrade_to_v0_0_11();
        }
      }
    }
    Resoc_Social_Editor_Utils::write_log("End of upgrades");
  }

  public static function upgrade_to_v0_0_11() {
    if ( Resoc_Social_Editor_Upgrades::check_and_mark_upgrade( '0.0.11' ) ) {
      return;
    }

    $page_size = 5;
    $offset = 0;
    $post_args = array(
      'post_type' => 'post',
      'posts_per_page' => $page_size,
      'offset' => $offset,
      'orderby' => 'ID',
      'post_status' => 'any'
    );
    $posts = get_posts( $post_args );
    while ( count( $posts ) > 0) {
      foreach( $posts as $post ) {
        Resoc_Social_Editor_Utils::write_log("Upgrading image of post {$post->ID}");
        $image_id = get_post_meta(
          $post->ID,
          Resoc_Social_Editor::OG_IMAGE_ID,
          true
        );
        if ( $image_id ) {
          Resoc_Social_Editor_Utils::write_log("Post {$post->ID} attached Resoc image is {$image_id}");
  
          $attach_data = wp_get_attachment_metadata( $image_id );
          if ( $attach_data && isset( $attach_data['file'] ) && ( $attach_data['file'] ) ) {
            $original_file = $attach_data['file'];
            $file = $upload_dir['basedir'] . '/' . $original_file;
            $original_image_filename = basename( $original_file );

            $title = get_the_title( $post->ID );
            $new_image_filename = Resoc_Social_Editor_Utils::post_title_to_image_file_name( $title );

            if ( $original_image_filename != $new_image_filename ) {
              Resoc_Social_Editor_Utils::write_log(
                "Image {$image_id}: {$original_image_filename} vs {$new_image_filename}, renamed it"
              );

              $new_image_id = Resoc_Social_Editor_Utils::rename_attached_image(
                $post->ID, $image_id, $new_image_filename
              );

              if ( $new_image_id ) {
                update_post_meta(
                  $post->ID,
                  Resoc_Social_Editor::OG_IMAGE_ID,
                  $new_image_id
                );
                Resoc_Social_Editor_Utils::write_log(
                  "Done! New image id for post {$post->ID} is {$new_image_id}"
                );
              }
              else {
                Resoc_Social_Editor_Utils::write_log( "No new image Id, do nothing" );
              }
            }
            else {
              Resoc_Social_Editor_Utils::write_log(
                "Image {$image_id}: no need to renamed it ({$original_image_filename})"
              );
            }
          }
          else {
            Resoc_Social_Editor_Utils::write_log(
              "Image {$image_id} has no attached data or no file name, ignore it"
            );
          }
        }
        else {
          Resoc_Social_Editor_Utils::write_log("Post {$post->ID} has no attached Resoc image");
        }
      }

      $post_args['offset'] += $page_size;
      $posts = get_posts( $post_args );
    }
  }

  public static function check_and_mark_upgrade( $version ) {
    Resoc_Social_Editor_Utils::write_log("Run upgrade for {$version}?");
    if ( get_option( Resoc_Social_Editor::OPTION_UPDGRADED_TO . $version ) ) {
      Resoc_Social_Editor_Utils::write_log("No (version {$version} already run)");
      return true;
    }

    update_option( Resoc_Social_Editor::OPTION_UPDGRADED_TO . $version, true );
    Resoc_Social_Editor_Utils::write_log("Yes (version {$version} never run)");
    return false;
  }
}
