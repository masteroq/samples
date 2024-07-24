<?php
/**
 * Plugin settings
 * @return mixed|void
 */
function apigames_settings() {

	$settings = get_option( 'apigames_settings' );

	return apply_filters( 'apigames/settings_page/opts', $settings );
}

/**
 * Retrieve a value from options
 *
 * @param $key
 * @param mixed|false $default
 * @param string $option
 *
 * @return bool|mixed
 */
function apigames_setting( $key, $default = false, string $option = 'apigames_settings' ) {

	$key     = apigames_sanitize_key( $key );
	$options = get_option( $option, false );
	return is_array( $options ) && isset( $options[ $key ] ) && ( $options[ $key ] === '0' || ! empty( $options[ $key ] ) ) ? apply_filters('apigames/settings_'.$key, $options[ $key ]) : $default;
}

/**
 * Create an object lazy way
 * @param $string
 *
 * @return array|mixed|object
 */
function apigames_to_obj( $string ) {
	return json_decode( json_encode( $string ) );
}

/**
 * Retrieve a value from the object cache. If it doesn't exist, run the $callback to generate and
 * cache the value.
 *
 * @param string   $key      The cache key.
 * @param callable $callback The callback used to generate and cache the value.
 * @param string   $group    Optional. The cache group. Default is empty.
 * @param int      $expire   Optional. The number of seconds before the cache entry should expire.
 *                           Default is 0 (as long as possible).
 *
 * @return mixed The value returned from $callback, pulled from the cache when available.
 */
function apigames_cache_remember( $key, $callback, $group = '', $expire = DAY_IN_SECONDS ) {
	$found  = false;
	$cached = wp_cache_get( $key, $group, false, $found );

	if ( false !== $found ) {
		return $cached;
	}

	$value = $callback();

	if ( ! is_wp_error( $value ) ) {
		wp_cache_set( $key, $value, $group, $expire );
	}

	return $value;
}


/**
 * Check permissions for currently logged in user.
 *
 * @return bool
 * @since 2.0.0
 *
 */
function apigames_current_user_can() {

	$capability = apigames_get_manage_capability();

	return apply_filters( 'apigames/current_user_can', current_user_can( $capability ), $capability );
}

/**
 * Get the default capability to manage everything for Slots Launch.
 *
 * @return string
 * @since 2.0.0
 *
 */
function apigames_get_manage_capability() {
	return apply_filters( 'apigames/manage_capability', 'manage_options' );
}

/**
 * Sanitizes string of CSS classes.
 *
 * @param array|string $classes
 * @param bool $convert True will convert strings to array and vice versa.
 *
 * @return string|array
 * @since 2.0.0
 *
 */
function apigames_sanitize_classes( $classes, $convert = false ) {

	$array = is_array( $classes );
	$css   = [];

	if ( ! empty( $classes ) ) {
		if ( ! $array ) {
			$classes = explode( ' ', trim( $classes ) );
		}
		foreach ( $classes as $class ) {
			if ( ! empty( $class ) ) {

				if ( strpos( $class, ' ' ) !== false ) {
					$css[] = apigames_sanitize_classes( $class, false );
				} else {
					$css[] = sanitize_html_class( $class );
				}
			}
		}
	}
	if ( $array ) {
		return $convert ? implode( ' ', $css ) : $css;
	} else {
		return $convert ? $css : implode( ' ', $css );
	}
}

/**
 * Sanitize key, primarily used for looking up options.
 *
 * @param string $key
 *
 * @return string
 */
function apigames_sanitize_key( string $key = '' ): string {

	return preg_replace( '/[^a-zA-Z0-9_\-\.\:\/]/', '', $key );
}

function apigames_current_page() {
	if( isset( $_GET['sl-page'] ) ) {
		return absint( $_GET['sl-page'] );
	}
	return 1;
}

function apigames_img_url( $pid = null ) {
	if( ! $pid ) {
		$pid = get_the_ID();
	}
	if ( has_post_thumbnail($pid) ) {
		$img = get_the_post_thumbnail_url( $pid );
	} else {
		$img = apigames_meta($pid, 'slimg');
	}
	return $img;
}

function apigames_clear_slots() {
	global $wpdb;
	$wpdb->query( "DELETE a,b,c FROM $wpdb->posts a LEFT JOIN $wpdb->term_relationships b ON (a.ID = b.object_id) LEFT JOIN $wpdb->postmeta c ON (a.ID = c.post_id) WHERE a.post_type = 'api-games'" );
}

function set_featured_image( $image_url, $post_id  ){
	$upload_dir = wp_upload_dir();
	$image_data = file_get_contents($image_url);
	$filename = basename($image_url);
	if(wp_mkdir_p($upload_dir['path']))
		$file = $upload_dir['path'] . '/' . $filename;
	else
		$file = $upload_dir['basedir'] . '/' . $filename;
	file_put_contents($file, $image_data);

	$wp_filetype = wp_check_filetype($filename, null );
	$attachment = array(
		'post_mime_type' => $wp_filetype['type'],
		'post_title' => sanitize_file_name($filename),
		'post_content' => '',
		'post_status' => 'inherit'
	);
	$attach_id = wp_insert_attachment( $attachment, $file, $post_id );
	require_once(ABSPATH . 'wp-admin/includes/image.php');
	$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
	wp_update_attachment_metadata( $attach_id, $attach_data );
	set_post_thumbnail( $post_id, $attach_id );
}

function import_function_output() {
	require_once(APIGAMES_PLUGIN_DIR . 'admin/view/import_filter.php');
}

function apigames_get_providers_list() {
	$json_data = file_get_contents(APIGAMES_PLUGIN_DIR . 'admin/json/all_games_data.json');

	$games = json_decode($json_data, true);

	if (json_last_error() !== JSON_ERROR_NONE) {
		echo json_last_error_msg();
		exit;
	}

	$providers = [];

	foreach ($games as $game) {
		$providers[] = $game['provider'];
	}

	$unique_providers = array_unique($providers);

	sort($unique_providers);


	return $unique_providers;
}

function check_if_game_exists($game_name) {
    $args = array(
        'post_type' => 'api-games',
        'title' => $game_name,
        'posts_per_page' => 1
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        return true;
    }

    return false;
}

function get_plugin_template_part( $slug, $name = null ) {
    $template = '';

    $template_path = 'templates/';

    if ( $name ) {
        $template = locate_template(array("{$template_path}{$slug}-{$name}.php", "{$template_path}{$slug}/{$name}.php"));
    }

    if ( !$template && $name && file_exists(APIGAMES_PLUGIN_DIR . "{$template_path}{$slug}-{$name}.php") ) {
        $template = APIGAMES_PLUGIN_DIR . "{$template_path}{$slug}-{$name}.php";
    }

    if ( !$template && file_exists(APIGAMES_PLUGIN_DIR . "{$template_path}{$slug}.php") ) {
        $template = APIGAMES_PLUGIN_DIR . "{$template_path}{$slug}.php";
    }

    if ( $template ) {
        include $template;
    }

    return '';
}

function apigames_import_games_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Import Games', 'apigames'); ?></h1>
        <?php import_function_output(); ?>
    </div>
    <?php
}

function updateGameImage($jsonData, $newBaseUrl) {
    $data = json_decode($jsonData, true);
    
    foreach ($data as &$record) {
        if (isset($record['game_image'])) {
            $pathParts = explode('/', $record['game_image']);
            $fileName = end($pathParts);
            $record['game_image'] = rtrim($newBaseUrl, '/') . '/' . $fileName;
        }
    }

    
    return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

function api_games_template($template) {
    if (is_singular('api-games')) {
        $plugin_template = APIGAMES_PLUGIN_DIR . 'templates/api-game.php';
        error_log($plugin_template);
        if (file_exists($plugin_template)) {
            return $plugin_template;
        } else {
            error_log('Template file does not exist.');
        }
    }
    return $template;
}
add_filter('template_include', 'api_games_template');

?>