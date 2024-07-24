<?php
/**
 * Enqueue scripts
 *
 * @package Fav WordPress Theme
 * @subpackage helpers
 * @version 1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function fav_quick_minify_js($js) {

    $js = preg_replace('/\/\*[^!][\s\S]*?\*\//', '', $js);

    $js = preg_replace('/\/\/[^\n\r]*/', '', $js);

    $js = preg_replace('/\s+/', ' ', $js);

    $js = preg_replace('/\s*([\(\)\{\};,=:\+\-\*\/&\|\?!<>\[\]])\s*/', '$1', $js);

    $js = preg_replace('/\s*;\s*/', ';', $js);


    return trim($js);
}


/**
 * Register/Enqueue frontend JS.
 *
 * @since 1.0
 */
function fav_register_js() {

	global $fav_options;

	$fav_theme_version = fav_get_theme_version();

	$src_dir = ( Redux_Helpers::is_local_host( )) ? FAV_SRC_URI : FAV_BUILD_URI;

	// Blocks
	wp_register_script( 'short1', FAV_BUILD_URI . 'js/short1.js', '', $fav_theme_version, true);

	//Build
	wp_register_script( 'home-js', FAV_BUILD_URI . 'js/script-home.js', '', $fav_theme_version, true);
	wp_register_script( 'script-page3', FAV_BUILD_URI . 'js/script-page3.js', '', $fav_theme_version, true);
	wp_register_script( 'script-page5', FAV_BUILD_URI . 'js/script-page5.js', '', $fav_theme_version, true);
	wp_register_script( 'script-page14', FAV_BUILD_URI . 'js/script-page14.js', '', $fav_theme_version, true);

	//Helper scripts
	wp_register_script( 'aos', FAV_THEME_URI . '/assets/js/aos.js', '', $fav_theme_version, true);
	wp_register_script( 'rp-js', FAV_THEME_URI . '/assets/js/rp.js', '', $fav_theme_version, true );
	wp_register_script( 'faq-yoast-js', FAV_THEME_URI . '/assets/js/faq-yoast.js', '', $fav_theme_version, true );

	wp_register_script('fav-ajax-pagination', FAV_THEME_URI . '/assets/js/fav-ajax-pagination.js', '', $fav_theme_version, true);
	wp_register_script('ajax-and-pagination', FAV_THEME_URI . '/assets/js/ajax-and-pagination.js', '', $fav_theme_version, true);

    wp_localize_script('ajax-and-pagination', 'fav_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
    ));

	wp_localize_script('fav-ajax-pagination', 'fav_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
    ));

	wp_enqueue_script( 'base-js', FAV_THEME_URI . '/assets/js/base.js', array('wp-i18n'), '', true );

	wp_localize_script('base-js', 'base_js', [
		'home_url' => get_bloginfo('url'),
		'ajax_url' => esc_url( admin_url( 'admin-ajax.php' ) ),
		'theme_uri' => FAV_THEME_URI,
		'email_text' => __('Enter correct email', 'fav')
	]);

	if($fav_options['yoast_like_accordeon'] == '1') {
		wp_enqueue_script( 'faq-yoast-js' );
	}

	if($fav_options['enable_reading_progress_bar'] == '1' && isset($fav_options['enable_reading_progress_bar'])) {
			wp_enqueue_script( 'rp-js' );
	}

	if($fav_options['fav_animated_blocks'] == '1'
	|| $fav_options['fav_casino_animated_blocks'] == '1'
	|| $fav_options['fav_slots_animated_blocks'] == '1'
	|| $fav_options['fav_animated_main_sections'] == '1') {
		wp_enqueue_script( 'aos' );
		wp_add_inline_script( 'aos', 'document.addEventListener("DOMContentLoaded", function() {AOS.init();});' );
	}

	if (!is_admin()) {
        wp_deregister_script('jquery');
        wp_dequeue_script('jquery');
    }

	if($fav_options['archive-pagination-type'] == 'ajax') {
		wp_enqueue_script( 'fav-ajax-pagination' );
	} elseif($fav_options['archive-pagination-type'] == 'ajax-and-pagination') {
		wp_enqueue_script( 'ajax-and-pagination' );
	}

}

add_action( 'wp_enqueue_scripts', 'fav_register_js' );


