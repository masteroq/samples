<?php

/**
 * Fired during plugin activation
 *
 * @link       https://favbet.ua
 * @since      1.0.0
 *
 * @package    Apigames
 * @subpackage Apigames/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Apigames
 * @subpackage Apigames/includes
 * @author     V.Kononenko
 */
class Apigames_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		// Check PHP version
		$phpversion = phpversion();
		if (version_compare($phpversion, '7.4', '<')) {
			// Deactivate the plugin
			deactivate_plugins(basename(APIGAMES_PLUGIN_FILE));
			// Show an error message
			wp_die('Apigames plugin requires PHP version 7.4 or higher. Please update your PHP version to use this plugin.');
		}

	}

}
