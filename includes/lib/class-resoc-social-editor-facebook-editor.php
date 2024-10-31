<?php

class Resoc_Social_Editor_Facebook_Editor {

  public static function no_editor_conflict() {
    $plugin = Resoc_Social_Editor_Utils::conflicting_plugin();

    $email_subject = "Please make Resoc Social Editor compatible with " . $plugin;
    $email_subject = str_replace( '+', '%20', urlencode( $email_subject ) );
    $email_body = <<<EOF
Hi,

I cannot use the Resoc Social Editor WordPress plugin because it is not compatible with PLUGIN_NAME.

Could you update Resoc Social Editor to fix this and let me know when the update is available?

Regards
EOF;
    $email_body =
    str_replace( 'PLUGIN_NAME', $plugin,
      str_replace( '+', '%20', urlencode( $email_body ) )
    );
?>
<div>
<div class="rse-by-resoc-title">
  <h3>
    You cannot use Resoc Social Editor yet
  </h3>

  <p>
    The WordPress ecosystem is very rich and a lot of plugins
    manage the social networks metadata, as Resoc Social Editor does.
    You are currently using <?php echo $plugin ?>, which
    Resoc Social Editor is not compatible with yet.
  </p>

  <p>
    How to solve this? That's easy. Simply ask for it. Seriously.
    We are willing to make Resoc Social Editor usable in all situations,
    so clicking the button below is all it takes to ask us to
    fix this.
  </p>

  <a
    href="mailto:contact@resoc.io?subject=<?php echo $email_subject ?>&body=<?php echo $email_body ?>"
    class="button button-primary"
  >
    Please make Resoc Social Editor compatible with <?php echo $plugin ?>
  </a>

  <p>
    Note: You do <i>not</i> have to deactivate Resoc Social Editor.
    For now it is in idle mode, not affecting your visitors's experience
    and not causing any conflict. You will be able to start using it
    as soon as an update is released.
  </p>
</div>
<?php
  }

	public static function facebook_editor( $post ) {
    if ( Resoc_Social_Editor_Utils::conflicting_plugin() ) {
      Resoc_Social_Editor_Facebook_Editor::no_editor_conflict();
      return;
    }

    $title =
      get_post_meta( $post->ID,
        Resoc_Social_Editor::OG_TITLE, true );
    $description =
      get_post_meta( $post->ID,
        Resoc_Social_Editor::OG_DESCRIPTION, true );

		$image_settings =
			get_post_meta( $post->ID,
        Resoc_Social_Editor::OG_MASTER_IMAGE_SETTINGS, true );

		$image_d =
			get_post_meta( $post->ID,
				Resoc_Social_Editor::OG_MASTER_IMAGE_ID, true );
		if ($image_d) {
			$image_url = wp_get_attachment_url( $image_d );
    }

    $default_overlay_id = get_option( Resoc_Social_Editor::OPTION_DEFAULT_OVERLAY_ID );

    $overlay_id = NULL;
    $overlay_url = NULL;
    $overlay_choice = get_post_meta( $post->ID, Resoc_Social_Editor::OG_OVERLAY_IMAGE_SET, true );
    if ( $overlay_choice ) {
      $overlay_id = get_post_meta( $post->ID,
        Resoc_Social_Editor::OG_OVERLAY_IMAGE_ID, true );
    }
    else {
      $overlay_id = $default_overlay_id;
    }
    if ( $overlay_id ) {
      $overlay_url = wp_get_attachment_url( $overlay_id );
    }

		ob_start();
?>
	<div class="custom-field-panel rse-editor" id="rse-editor">
		<div class="rse-by-resoc-title">
			<h3>
        By <a href="https://resoc.io" target="_blank">Resoc</a>
      </h3>
      <span class="rse-feeback-link">
        - <a href="mailto:contact@resoc.io">Send feedback</a>
      </span>
		</div>

		<div class="rse-editor-overall-container">
      <div class="rse-preview-container">
				<div class="open-graph-editor-container"></div>
			</div>

			<div class="rse-fields">
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><label for="rse-title">Title</label></th>
						<td><input type="text" name="rse-title" placeholder="A title you should change"></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="rse-description">Description</label></th>
						<td><textarea rows="3" name="rse-description" placeholder="A description you can edit"></textarea></td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="rse-image">Image</label></th>
						<td>
              <button class="rse-image-selection-button button-primary">Select image</button>
            </td>
					</tr>
          <tr valign="top">
            <th scope="row"><label for="rse-image">Overlay</label></th>
            <td class="rse-image-selection-container">
              <?php
                require(
                  plugin_dir_path(__FILE__) . '../../views' . DIRECTORY_SEPARATOR . 'overlay-editor.php'
                );
              ?>
              <button class="rse-overlay-image-selection-button button-secondary">Select existing overlay</button>
              <button class="rse-overlay-image-reset-button button-secondary">No overlay</button>
              <p class="description">
                Use an overlay to show something on top of your image. For example, your logo.
              </p>
              <p class="rse-make-it-default-overlay description" style="display:none">
                To have the current overlay applied to new posts,
                <a
                  href="<?php echo admin_url( 'options-general.php?page=' . Resoc_Social_Editor::MENU_SETTINGS ) ?>"
                  target="_blank"
                >
                  make it the default overlay.
                </a>
              </p>
            </td>
          </tr>
					<tr valign="top">
						<th scope="row"><label for="rse-view">View</label></th>
						<td>
              <label for="rse-view-facebook">
                <input type="radio" name="rse-view" value="facebook" id="rse-view-facebook" checked="checked" />
                Facebook
              </label>
              <label for="rse-view-facebook-caption">
                <input type="radio" name="rse-view" value="facebook_caption" id="rse-view-facebook-caption" />
                Facebook - Focus on text
              </label>
              <label for="rse-view-linkedin">
                <input type="radio" name="rse-view" value="linkedin" id="rse-view-linkedin" />
                LinkedIn
              </label>
              <label for="rse-view-linkedin-caption">
                <input type="radio" name="rse-view" value="linkedin_caption" id="rse-view-linkedin-caption" />
                LinkedIn - Focus on text
              </label>
            </td>
					</tr>
				</table>
			</div>

			<div class="rse-clear-fix"></div>
      <input
        type="hidden"
        name="rse-og-image-settings"
      >
      <input
        type="hidden"
        name="rse-og-image-id"
        value="<?php echo $image_d ?>"
      >
      <input
        type="hidden"
        name="rse-og-overlay-image-id"
        value="<?php echo $overlay_id ?>"
      >
		</div>
	</div>

	<div id="rse-upgrade-notice" style="display:none">
		Your version of the plugin is outdated.
		Please <a href="<?php echo get_site_url( null, '/wp-admin/plugins.php' ) ?>" target="_blank">
			visit your plugins page</a> and update <strong>Resoc Social Editor</strong>.
	</div>

	<script>
  /*
		jQuery(document).ready(function(e) {
			var imageId = <?php echo $image_d ? $image_d : 'undefined' ?>;
			var imageUrl = <?php echo $image_url ? '"' . $image_url . '"' : 'undefined' ?>;
			rseInitSocialEditor(
				jQuery('#rse-editor'),
				imageId, imageUrl,
				<?php echo $openGraphSerializedData ? $openGraphSerializedData : 'undefined' ?>,
				'<?php echo get_site_url() ?>');
    });
    */
  </script>

  <script>
		jQuery(document).ready(function(e) {
      var title = <?php echo $title ? json_encode( $title ) : 'undefined' ?>;
      var description = <?php echo $description ? json_encode( $description ) : 'undefined' ?>;

      var imageId = <?php echo $image_d ? $image_d : 'undefined' ?>;
      var imageSettings = <?php echo $image_settings ? $image_settings : 'undefined' ?>;
      var imageUrl = <?php echo $image_url ? '"' . $image_url . '"' : 'undefined' ?>;
      var overlayUrl = <?php echo $overlay_url ? '"' . $overlay_url . '"' : 'undefined' ?>;
      var overlayId = <?php echo $overlay_id ? '"' . $overlay_id . '"' : 'undefined' ?>;
      var defaultOverlayId = <?php echo $default_overlay_id ? '"' . $default_overlay_id . '"' : 'undefined' ?>;
      console.log("IMAGE URL=" + imageUrl);

      <?php if ( has_post_thumbnail( $post->ID ) ) { ?>
        var featuredImageId = <?php echo get_post_thumbnail_id( $post->ID ) ?>;
        var featuredImageUrl = "<?php echo wp_get_attachment_url( get_post_thumbnail_id( $post->ID ) ) ?>";
      <?php } else { ?>
        var featuredImageId = undefined;
        var featuredImageUrl = undefined;
      <?php } ?>

      var editorContainer = jQuery('#rse-editor');

      var setOverlayData = rseInitOpenGraphEditor(
        editorContainer,
        title,
        description,
        imageId, imageSettings, imageUrl,
        overlayUrl, overlayId, defaultOverlayId,
        '<?php echo get_site_url() ?>',
        featuredImageId, featuredImageUrl
      );

<?php
      init_rse_overlay_editor( 'setOverlayData' );
?>
    });
  </script>
  
<?php
		return ob_get_clean();
	}
}
