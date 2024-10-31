<div class="wrap">
	<?php screen_icon() ?>
	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

	<form action="<?php echo $social_editor_admin_url ?>" method="post" id="rse-settings-form">
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">Default overlay</th>
					<td>
            <div id="overlay-preview" style="display:none">
              <img
                class="rse-overlay-preview-img"
                style="background-image: url('<?php echo plugin_dir_url( __FILE__ ) . '../plugin-assets/images/overlay-background.png' ?>')"
              />
            </div>
            <div id="no-overlay" style="display:none">
              <p>
                <strong>
                  No default overlay
                  </strong>
              </p>
            </div>

            <?php
              require('overlay-editor.php');
            ?>
            <button class="rse-image-selection-button button-secondary">Select existing overlay</button>
            <button class="rse-image-reset-button button-secondary">Reset default overlay</button>

            <p class="description">
              The default overlay is automatically applied to all of your new posts and can be overriden manually.
              <br/>
              You probably want to choose an overlay that integrates your logo,
              so all your pages have a uniform branding when shared on social networks.
            </p>
					</td>
				</tr>
        <tr>
          <th scope="row">Usage</th>
          <td>
            <label>
              <input
                type="checkbox"
                name="<?php echo Resoc_Social_Editor::OPTION_SEND_ANONYMOUS_DATA ?>"
                value="1"
                <?php echo get_option( Resoc_Social_Editor::OPTION_SEND_ANONYMOUS_DATA ) ? 'checked' : '' ?>
              />
              Allow the plugin to send anonymous usage data
            </label>
          </td>
        </tr>
			</tbody>
		</table>

    <input type="hidden" name="<?php echo Resoc_Social_Editor::SETTINGS_FORM ?>" value="1">
		<input
      type="hidden"
      name="<?php echo Resoc_Social_Editor::OPTION_DEFAULT_OVERLAY_ID ?>"
      value="<?php echo $default_overlay_id ?>"
    >

		<input name="Submit" type="submit" class="button-primary" value="Save changes">
	</form>

  <script>
    jQuery(document).ready(function() {
      var form = jQuery('#rse-settings-form');
      var fileFrame;

      function overlayEditorCallback(imageData) {
        console.log("Callback called with " + imageData.image_id);
        showOverlayPreview(imageData.image_id, imageData.image_url);
        form.find(
          'input[name="<?php echo Resoc_Social_Editor::OPTION_DEFAULT_OVERLAY_ID ?>"]'
        ).val(imageData.image_id);
      }

<?php
      init_rse_overlay_editor( 'overlayEditorCallback' );
?>

      function showOverlayPreview(overlayId, overlayUrl) {
        if (overlayId) {
          form.find('#overlay-preview img').attr('src', overlayUrl);
          form.find('#no-overlay').hide();
          form.find('#overlay-preview').show();
        }
        else {
          form.find('#no-overlay').show();
          form.find('#overlay-preview').hide();
        }
      }

<?php
        if ( $default_overlay_id) {
?>
          showOverlayPreview("<?php echo $default_overlay_id ?>", "<?php echo $default_overlay_url ?>");
<?php
        }
        else {
?>
          showOverlayPreview(undefined, undefined);
<?php
        }
?>
      form.find('.rse-image-reset-button').live('click', function(event) {
        event.preventDefault();
        form.find(
          'input[name="<?php echo Resoc_Social_Editor::OPTION_DEFAULT_OVERLAY_ID ?>"]'
        ).val(undefined);
        showOverlayPreview(undefined, undefined);
      });

      form.find('.rse-image-selection-button').live('click', function(event) {
        event.preventDefault();
    
        if (fileFrame) {
          fileFrame.open();
          return;
        }
    
        // Create the media frame.
        fileFrame = wp.media.frames.file_frame = wp.media({
          title: "Default overlay - 1200x630 PNG image with transparent regions",
          button: {
            text: "Set as default overlay",
          },
          multiple: false,
          library: {
            type: 'image'
          }
        });
    
        fileFrame.on('select', function() {
          attachment = fileFrame.state().get('selection').first().toJSON();

          imageId = attachment.id;
          form.find(
            'input[name="<?php echo Resoc_Social_Editor::OPTION_DEFAULT_OVERLAY_ID ?>"]'
          ).val(imageId);
          showOverlayPreview(imageId, attachment.url);
        });
    
        fileFrame.open();
      });
    });
  </script>
</div>
