<?php
/*
 * Plugin Name: Resoc Social Editor
 * Version: 0.0.12
 * Plugin URI: https://resoc.io/media/wordpress
 * Description: Craft the appearance of your content and show your brand when your visitors share your posts on Facebook and LinkedIn.
 * Author: Philippe Bernard
 * Author URI: https://resoc.io/
 * Requires at least: 4.0
 * Tested up to: 5.1
 *
 * Text Domain: resoc-social-editor
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author phbernard
 * @since 0.0.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Load plugin class files
require_once( 'includes/class-resoc-social-editor.php' );

// Load plugin libraries
require_once( 'includes/lib/class-resoc-social-editor-admin-api.php' );
require_once( 'includes/lib/class-resoc-social-editor-public.php' );

/**
 * Returns the main instance of Resoc_Social_Editor to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Resoc_Social_Editor
 */
function Resoc_Social_Editor () {
	$instance = Resoc_Social_Editor::instance( __FILE__, '0.0.12' );

	return $instance;
}

Resoc_Social_Editor();
