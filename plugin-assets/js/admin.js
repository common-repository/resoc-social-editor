
var rseInitOpenGraphEditor = function(
  editorContainer,
  title, description,
  imageId, imageSettings, imageUrl,
  overlayImageSrc, overlayImageId, defaultOverlayId,
  siteUrl,
  featuredImageId, featuredImageUrl
) {
  var openGraphEditor;
  var imageSelectionFrame;
  var overlaySelectionFrame;

  var titleEdited = (title !== undefined);
  var imageEdited = (imageId !== undefined);

  const e = React.createElement;
  console.log(ResocSocialEditor);
  const domContainer = editorContainer.find('.open-graph-editor-container')[0];
  ReactDOM.render(e(
    ResocSocialEditor.StandaloneOpenGraphEditor, {onCreated: function(obj) {
      openGraphEditor = obj;

      if (imageId) {
        setImage(imageId, imageUrl, imageSettings);
      }
      else {
        setDefaultImage(featuredImageId, featuredImageUrl, undefined);
      }
      openGraphEditor.setView('facebook');
      openGraphEditor.setUrl(siteUrl);
      openGraphEditor.setTitle(title);
      openGraphEditor.setDescription(description || '');
      setOverlay(overlayImageId, overlayImageSrc);
    }}), domContainer);

  initForm(editorContainer, title, description);
  initImageSelection(editorContainer);
  initOverlayImageSelection(editorContainer);
  initMainFieldsListeners(editorContainer);

  var postForm = jQuery.find('#post');
  jQuery(document).on('submit', postForm, function() {
    editorContainer.find('input[name="rse-og-image-settings"]').val(
      JSON.stringify(openGraphEditor.getImageEditionState())
    );
  });

  openGraphEditor.onImageEditionChange(function(state) {
    editorContainer.find('input[name="rse-og-image-settings"]').val(
      JSON.stringify(state)
    );
  });

  function initMainFieldsListeners(editorContainer) {
    var rseTitleField = editorContainer.find('input[name=rse-title]');
    var titleField = jQuery(document).find('input[name="post_title"]');

    // For WordPress 5 / Gutenberg
    if (wp && wp.data && wp.data.subscribe) {
      var dataSubscriber = wp.data.subscribe( function() {
        var coreEditor = wp.data.select("core/editor");

        if (coreEditor) {
          var newTitle = coreEditor.getEditedPostAttribute('title');
          if (newTitle) {
            setDefaultTitle(newTitle);
            console.log("Set default title to " + newTitle);
          }

          var featuredImageId = coreEditor.getEditedPostAttribute('featured_media');
          if (featuredImageId) {
            wp.media.attachment(featuredImageId).fetch().then(function (data) {
              setDefaultImage(featuredImageId, data.url);
              console.log("Set default image to " + featuredImageId + " / " + data.url);
            });
          }
        }
      });
    }

    if (rseTitleField.val() === '') {
      var newTitle = titleField.val();
      setDefaultTitle(newTitle);
    }

    titleField.on('change paste keyup', function() {
      var newTitle = titleField.val();
      setDefaultTitle(newTitle);
    });

    function setDefaultTitle(title) {
      if (! titleEdited) {
        rseTitleField.val(title);
        openGraphEditor.setTitle(title);
      }
    }
  }

  function initForm(editorContainer, title, description) {
    var titleField = editorContainer.find('input[name=rse-title]');
    titleField.val(title);
    titleField.on('input', function() {
      titleEdited = true;
      openGraphEditor.setTitle(this.value);
    });

    var descriptionField = editorContainer.find('textarea[name=rse-description]');
    descriptionField.val(description);
    descriptionField.on('input propertychange', function() {
      openGraphEditor.setDescription(this.value);
    });

    editorContainer.find('input[name=rse-view]').change(function() {
      openGraphEditor.setView(this.value);
    });
  }

  function initImageSelection(editorContainer) {
    editorContainer.find('.rse-image-selection-button').live('click', function(event) {
      event.preventDefault();
  
      if (imageSelectionFrame) {
        imageSelectionFrame.open();
        return;
      }
  
      // Create the media frame.
      imageSelectionFrame = wp.media.frames.file_frame = wp.media({
        title: "Image for Facebook and LinkedIn",
        multiple: false,
        library: {
          type: 'image'
        }
      });
  
      imageSelectionFrame.on('select', function() {
        attachment = imageSelectionFrame.state().get('selection').first().toJSON();

        imageUrl = attachment.url;
        setImage(attachment.id, attachment.url, undefined);
        imageEdited = true;
      });
  
      imageSelectionFrame.open();
    });

    // When the featured image is set,
    // auto-populate the OpenGraph editor
    $(document).ajaxComplete(function (event, xhr, settings) {
      if (typeof settings.data === 'string'
      && /action=get-post-thumbnail-html/.test(settings.data)
      && xhr.responseJSON && typeof xhr.responseJSON.data === 'string') {
        var srcMatch = /<img[^>]+src="([^"]+)"/.exec(xhr.responseJSON.data);
        var idMatch = /<input[^>]+value="([^"]+)"/.exec(xhr.responseJSON.data);
        if (srcMatch && idMatch) {
          var imageSrc = srcMatch[1];
          var imageId = idMatch[1];

          setDefaultImage(imageId, imageSrc, undefined);
        }
        // No match? Maybe there is no feature image
        // (eg. it was just unset)
      }
    });
  }

  function initOverlayImageSelection(editorContainer) {
    editorContainer.find('.rse-overlay-image-reset-button').live('click', function(event) {
      event.preventDefault();

      setOverlay(undefined, undefined);
    });

    editorContainer.find('.rse-overlay-image-selection-button').live('click', function(event) {
      event.preventDefault();

      if (overlaySelectionFrame) {
        overlaySelectionFrame.open();
        return;
      }

      // Create the media frame.
      overlaySelectionFrame = wp.media.frames.file_frame = wp.media({
        title: "Overlay - 1200x630 PNG image with transparent regions",
        button: {
          text: "Select as overlay",
        },
        multiple: false,
        library: {
          type: 'image'
        }
      });

      overlaySelectionFrame.on('select', function() {
        attachment = overlaySelectionFrame.state().get('selection').first().toJSON();

        setOverlay(attachment.id, attachment.url);
      });

      overlaySelectionFrame.open();
    });
  }

  var currentDefaultImageId;

  // Set what could be a good default (eg. the featured image)
  function setDefaultImage(imageId, imageUrl, imageSettings) {
    // We assign the featured image to the OpenGraph editor only
    // if the user made no choice yet.
    // Also, prevent multiple, unecessary calls to setImage.
    if (! imageEdited && (imageId !== currentDefaultImageId)) {
      currentDefaultImageId = imageId;
      setImage(imageId, imageUrl, imageSettings);
    }
  }

  function setImage(imageId, imageUrl, imageSettings) {
    openGraphEditor.setImage(imageUrl, imageSettings);
    editorContainer.find('input[name="rse-og-image-id"]').val(imageId);
    if (imageId) {
      editorContainer.find('.rse-image-selection-button')
      .removeClass('button-primary')
      .addClass('button-secondary')
      .html('Select another image');
    }
    else {
      editorContainer.find('.rse-image-selection-button')
        .removeClass('button-secondary')
        .addClass('button-primary')
        .html('Select image');
    }
  }

  function setOverlay(overlayId, overlayUrl) {
    openGraphEditor.setOverlayImageSrc(overlayUrl);
    editorContainer.find('input[name="rse-og-overlay-image-id"]').val(overlayId);

    if (overlayId) {
      editorContainer.find('.rse-overlay-image-selection-button')
        .removeClass('button-primary')
        .addClass('button-secondary');
      editorContainer.find('.rse-overlay-image-reset-button').removeAttr('disabled');

      if (!defaultOverlayId || (overlayId !== defaultOverlayId)) {
        editorContainer.find('.rse-make-it-default-overlay').show();
      }
    }
    else {
      editorContainer.find('.rse-overlay-image-selection-button')
        .removeClass('button-secondary')
        .addClass('button-primary');
      editorContainer.find('.rse-overlay-image-reset-button').attr('disabled', 'disabled');
    }
  }

  console.log("OPENGRAPH Editor Init Completed");

  return function(imageData) {
    setOverlay(imageData.image_id, imageData.image_url);
  }
}
