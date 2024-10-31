<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class Resoc_Social_Editor {

	const PLUGIN_PREFIX = 'rse';

	const OPTION_HTML_CODE                   = 'rse_html_code';
	const OPTION_OG_SERIALIZED_DATA          = 'rse_og_serialized_data';
	const OPTION_OG_IMAGE_ID                 = 'rse_og_image_id';

	const PLACEHOLDER_URL                    = 'RSE_Placeholder_Url';
	const PLACEHOLDER_SITE_NAME              = 'RSE_Placeholder_Site_Name';
	const PLACEHOLDER_LOCALE                 = 'RSE_Placeholder_Locale';
	const PLACEHOLDER_ARTICLE_PUBLISHED_TIME = '2016-10-13T15:44:04+0000';
	const PLACEHOLDER_ARTICLE_MODIFIED_TIME  = '2016-10-13T15:45:05+0000';
	const PLACEHOLDER_ARTICLE_AUTHOR         = 'RSE_Placeholder_Article_Author';
	const PLACEHOLDER_ARTICLE_SECTION        = 'RSE_Placeholder_Article_Section';
	const PLACEHOLDER_ARTICLE_TAG            = 'RSE_Placeholder_Article_Tag';
	const PLACEHOLDER_ARTICLE_PUBLISHER      = 'RSE_Placeholder_Article_Publisher';

	const PLUGIN_SLUG                        = 'resoc-social-editor';

  const OG_MASTER_IMAGE_ID       = 'RSE_OpenGraph_Master_Image_Id';
  const OG_MASTER_IMAGE_SETTINGS = 'RSE_OpenGraph_Master_Image_Settings';
  const OG_OVERLAY_IMAGE_ID      = 'RSE_OpenGraph_Overlay_Image_Id';
  // Boolean - If the user has made a choice regarding the overlay
  // (including "no overlay") or not.
  const OG_OVERLAY_IMAGE_SET     = 'RSE_OpenGraph_Overlay_Image_Set';
  // Note: the previous name of this meta was
  // 'RSE_OG_Image_Id'. For some reasons,
  // it was not possible to update the meta
  // whith that name. I couldn't figure out why.
  // Thus it was artificially changed.
  const OG_IMAGE_ID              = 'RSE_OpenGraph_Image_Id';
  const OG_TITLE                 = 'RSE_OpenGraph_Title';
  const OG_DESCRIPTION           = 'RSE_OpenGraph_Description';

  const SETTINGS_FORM = "RSE_Settings_Form";

  const OPTION_DEFAULT_OVERLAY_ID = 'RSE_Option_Default_Overlay_Id';
  const OPTION_SKIP_OVERLAY_CREATION_SUGGESTION = 'RSE_Option_Skip_Overlay_Creation_Suggestion';

  const OPTION_SALT = 'RSE_Option_Salt';
  const OPTION_SEND_ANONYMOUS_DATA = 'RSE_Send_Anonymous_Data';

  const OPTION_UPDGRADED_TO = 'RSE_Option_Upgraded_To_';

  const MENU_SETTINGS = 'resoc_social_editor_settings_menu';

	/**
	 * The single instance of Resoc_Social_Editor.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * Settings class object
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = null;

	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_version;

	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_token;

	/**
	 * The main plugin file.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $file;

	/**
	 * The main plugin directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $dir;

	/**
	 * The plugin assets directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_dir;

	/**
	 * The plugin assets URL.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_url;

	/**
	 * Suffix for Javascripts.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $script_suffix;

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function __construct ( $file = '', $version = '1.0.0' ) {
		$this->_version = $version;
		$this->_token = 'resoc_social_editor';

		// Load plugin environment variables
		$this->file = $file;
		$this->dir = dirname( $this->file );
		$this->assets_dir = trailingslashit( $this->dir ) . 'plugin-assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/plugin-assets/', $this->file ) ) );

		$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		register_activation_hook( $this->file, array( $this, 'install' ) );

		// Load admin JS & CSS
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );

		// Load API for generic admin functions
		if ( is_admin() ) {
			new Resoc_Social_Editor_Admin_API();
		}
		else {
			new Resoc_Social_Editor_Public();
		}

		// Handle localisation
		$this->load_plugin_textdomain();
		add_action( 'init', array( $this, 'load_localisation' ), 0 );
	} // End __construct ()

	/**
	 * Load admin CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_styles ( $hook = '' ) {
		wp_register_style( $this->_token . '-admin', esc_url( $this->assets_url ) . 'css/admin.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-admin' );
	} // End admin_enqueue_styles ()

	/**
	 * Load admin Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_scripts ( $hook = '' ) {
    $resoc_components_version = '0.0.8';

		wp_enqueue_script( $this->_token . '-react',
			'https://unpkg.com/react@16/umd/react.development.js',
      array( ), $this->_version );
    wp_enqueue_script( $this->_token . '-react-dom',
			'https://unpkg.com/react-dom@16/umd/react-dom.development.js',
      array( $this->_token . '-react' ), $this->_version );
    wp_enqueue_script( $this->_token . '-rse-bundle',
      'https://resoc.io/components/resoc-social-editor-components/' .
        $resoc_components_version . '/bundle.js',
        array( $this->_token . '-react-dom' ), $this->_version);
    wp_enqueue_script( $this->_token . '-admin',
      esc_url( $this->assets_url ) . 'js/admin' . $this->script_suffix . '.js',
      array( $this->_token . '-rse-bundle' ), $this->_version );
    wp_enqueue_script( $this->_token . '-overlay-editor',
      esc_url( $this->assets_url ) . 'js/overlay-editor' . $this->script_suffix . '.js',
      array( $this->_token . '-rse-bundle' ), $this->_version );
  }

	/**
	 * Load plugin localisation
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_localisation () {
		load_plugin_textdomain( 'resoc-social-editor', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_localisation ()

	/**
	 * Load plugin textdomain
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_plugin_textdomain () {
	    $domain = 'resoc-social-editor';

	    $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

	    load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
	    load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_plugin_textdomain ()

	/**
	 * Main Resoc_Social_Editor Instance
	 *
	 * Ensures only one instance of Resoc_Social_Editor is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Resoc_Social_Editor()
	 * @return Main Resoc_Social_Editor instance
	 */
	public static function instance ( $file = '', $version = '1.0.0' ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version );
		}
		return self::$_instance;
	} // End instance ()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __clone ()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __wakeup ()

	/**
	 * Installation. Runs on activation.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function install () {
		$this->_log_version_number();
	} // End install ()

	/**
	 * Log the plugin version number.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	private function _log_version_number () {
		update_option( $this->_token . '_version', $this->_version );
	} // End _log_version_number ()




	/**
	 * Returns /www/wordpress/wp-content/uploaded/rse
	 */
	public static function get_files_dir( $post_id = NULL ) {
		$up_dir = wp_upload_dir();
		return $up_dir['basedir'] . '/' .
			Resoc_Social_Editor::PLUGIN_PREFIX . '/' .
			( $post_id ? $post_id . '/' : '' );
	}

	/**
	 * Returns http//somesite.com/blog/wp-content/upload/rse/
	 */
	public static function get_files_url( $post_id ) {
		$up_dir = wp_upload_dir();
		$base_url = $up_dir['baseurl'];
		// Make sure to no duplicate the '/'
		// This is especially important when the base URL is the root directory:
		// When this happens, the generated URL would be
		// "http//somesite.com//fbrfg/" and then "//fbrfg/" when the host name is
		// stripped. But this path is wrong, as it looks like a "same protocol" URL.
		$separator = (substr($base_url, -1) == '/') ? '' : '/';
		return $base_url . $separator .
			Resoc_Social_Editor::PLUGIN_PREFIX . '/' . $post_id . '/';
	}

	public static function get_tmp_dir() {
		return Resoc_Social_Editor::get_files_dir() . 'tmp/';
	}

	public static function remove_directory($directory) {
		foreach( scandir( $directory ) as $v ) {
			if ( is_dir( $directory . '/' . $v ) ) {
				if ( $v != '.' && $v != '..' ) {
					Resoc_Social_Editor::remove_directory( $directory . '/' . $v );
				}
			}
			else {
				unlink( $directory . '/' . $v );
			}
		}
		rmdir( $directory );
	}

	// See https://www.justinsilver.com/technology/writing-to-the-php-error_log-with-var_dump-and-print_r/
	public static function var_error_log( $object = NULL ) {
		ob_start();
		var_dump( $object );
		$contents = ob_get_contents();
		ob_end_clean();
		error_log( $contents );
	}
}
