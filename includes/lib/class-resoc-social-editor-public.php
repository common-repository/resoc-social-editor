<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class Resoc_Social_Editor_Public {

	public function __construct () {
		// Disable Jetpack Open Graph markups
    add_filter( 'jetpack_enable_open_graph', '__return_false' );

    if ( Resoc_Social_Editor_Utils::conflicting_plugin() ) {
      return;
    }

    if ( Resoc_Social_Editor_Utils::is_yoast_seo_active() ) {
      add_filter(
        'wpseo_opengraph_title',
        array( $this, 'get_og_title' )
      );
      add_filter(
        'wpseo_opengraph_desc',
        array( $this, 'get_og_description' )
      );
      add_filter(
        'wpseo_add_opengraph_images',
        array( $this, 'get_og_image' )
      );
    }
    else if ( Resoc_Social_Editor_Utils::is_aiosp_active() ) {
      add_filter(
        'aiosp_opengraph_meta',
        array( $this, 'aiosp_override_image_meta' ),
        10, 3
      );
    }
    else {
      // No Yoast, add markups manually
      add_action( 'wp_head', array( $this, 'add_opengraph_markups' ) );
    }

    // In any case, update oEmbed
    // See get_oembed_response_data_rich in WordPress/wp-includes/embed.php
    add_filter(
      'oembed_response_data',
      array( $this, 'patch_oembed_response_data_rich' ),
      15, // WordPress/wp-includes/default-filters.php is using 10, so we are "just a little later"
      4
    );
  }

  public function patch_oembed_response_data_rich( $data, $post, $width, $height ) {
    // At this point, $data['thumbnail_url'], $data['thumbnail_width'] and $data['thumbnail_height']
    // were already set by get_oembed_response_data_rich.
    // They are updated if needed.

    $image_id = get_post_meta(
      $post->ID,
      Resoc_Social_Editor::OG_IMAGE_ID,
      true
    );
    if ( $image_id ) {
      $image_data = wp_get_attachment_metadata( $image_id );
      if ( is_array( $image_data ) ) {
        $data['thumbnail_url']    = wp_get_attachment_image_url( $image_id, 'full' );
        if ( $image_data['width'] && $image_data['height'] ) {
          $data['thumbnail_width']  = $image_data['width'];
          $data['thumbnail_height'] = $image_data['height'];
        }
      }
    }

    $title = get_post_meta(
      $post->ID,
      Resoc_Social_Editor::OG_TITLE,
      true
    );
    if ( $title ) {
      $data['title'] = $title;
    }

    return $data;
  }

  public function aiosp_override_image_meta( $value, $network, $meta ) {
    if ( ( $network === 'facebook') && (
      ( $meta === 'thumbnail' ) || ( $meta === 'width' ) || ( $meta === 'height' )
    ) ) {
      $specific_image_id = get_post_meta(
        get_the_ID(),
        Resoc_Social_Editor::OG_IMAGE_ID,
        true
      );
      if ( $specific_image_id ) {
        if ( $meta === 'thumbnail' ) {
          return wp_get_attachment_image_url( $specific_image_id, 'full' );
        }
        else {
          $image_data = wp_get_attachment_metadata( $specific_image_id );
          if ( is_array( $image_data ) ) {
            if ( ( $meta === 'width' ) && $image_data['width'] ) {
              return $image_data['width'];
            }
            if ( ( $meta === 'height' ) && $image_data['height'] ) {
              return $image_data['height'];
            }
          }
        }
      }
    }

    return $value;
  }

  public function add_opengraph_markups() {
    $post_id = get_the_ID();

    $title = get_post_meta(
      $post_id,
      Resoc_Social_Editor::OG_TITLE,
      true
    );
    if ( $title ) {
      echo '<meta name="og:title" value="' . htmlspecialchars( $title ) . '">' . "\n";
    }

    $description = get_post_meta(
      $post_id,
      Resoc_Social_Editor::OG_DESCRIPTION,
      true
    );
    if ( $description ) {
      echo '<meta name="og:description" value="' . htmlspecialchars( $description ) . '">' . "\n";
    }

    $image_id = get_post_meta(
      $post_id,
      Resoc_Social_Editor::OG_IMAGE_ID,
      true
    );
    if ( $image_id ) {
      $image_data = wp_get_attachment_metadata( $image_id );
      if ( is_array( $image_data ) ) {
        $image_data['url'] = wp_get_attachment_image_url( $image_id, 'full' );
        echo '<meta name="og:image" value="' . $image_data['url'] . '">' . "\n";
        if ( $image_data['width'] && $image_data['height'] ) {
          echo '<meta name="og:image:width" value="' . $image_data['width'] . '">' . "\n";
          echo '<meta name="og:image:height" value="' . $image_data['height'] . '">' . "\n";
        }
      }
    }
  }

  public function get_og_title( $original_title ) {
    $post_id = get_the_ID();
    $specific_title = get_post_meta(
      $post_id,
      Resoc_Social_Editor::OG_TITLE,
      true
    );
    return $specific_title ? $specific_title : $original_title;
  }

  public function get_og_description( $original_description ) {
    $post_id = get_the_ID();
    $specific_description = get_post_meta(
      $post_id,
      Resoc_Social_Editor::OG_DESCRIPTION,
      true
    );
    return $specific_description ? $specific_description : $original_description;
  }

  public function get_og_image( $wpseo_opengraph_image ) {
    $post_id = get_the_ID();
    $specific_image_id = get_post_meta(
      $post_id,
      Resoc_Social_Editor::OG_IMAGE_ID,
      true
    );
    if ( $specific_image_id ) {
      $wpseo_opengraph_image->add_image_by_id( $specific_image_id );
    }
  }

	public static function get_the_author_full_name() {
		$fn = get_the_author_meta('first_name');
		$ln = get_the_author_meta('last_name');
		if ( ( ! empty( $fn ) ) || ( ! empty( $ln ) ) ) {
			return trim( "$fn $ln" );
		}
		else {
			return get_the_author();
		}
	}

}
