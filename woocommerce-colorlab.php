<?php

/**
 * The plugin bootstrap file
 *
 * @link              https://printlane.com
 * @since             1.0.0
 * @package           WC_Colorlab
 *
 * @wordpress-plugin
 * Plugin Name:       WooCommerce Printlane
 * Plugin URI:        https://help.printlane.com/integrations/wordpress.html
 * Description:       Integration of the Printlane™ Product Designer for personalizable products in your WooCommerce e-commerce store
 * Version:           1.5.5
 * Author:            Printlane™
 * Author URI:        https://printlane.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woocommerce-colorlab
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


/**
 * Checks requirements
 *
 * @return bool
 */
function wc_colorlab_requirements_met(){
	require_once( ABSPATH . '/wp-admin/includes/plugin.php' ) ;

	if ( ! is_plugin_active ( 'woocommerce/woocommerce.php' ) ) {
		return false ;
	}

	return true;
}

/**
 * Admin notice
 */
function wc_colorlab_requirements_error () {
	?>
	<div class="error notice">
		<p><?php _e( 'WooCommerce Printlane can not be used because WooCommerce is not available. Please install and activate the WooCommerce plugin.', 'woocommerce-colorlab' ); ?></p>
	</div>
	<?php
}


/**
 * The code that runs during plugin activation.
 */
function activate_wc_colorlab() {
	// nothing to do at the moment
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_wc_colorlab() {
	// nothing to do at the moment
}


if(wc_colorlab_requirements_met()){
	register_activation_hook( __FILE__, 'activate_wc_colorlab' );
	register_deactivation_hook( __FILE__, 'deactivate_wc_colorlab' );

	/**
	 * The core plugin class that is used to define internationalization,
	 * admin-specific hooks, and public-facing site hooks.
	 */
	require plugin_dir_path( __FILE__ ) . 'includes/class-wc-colorlab.php';

	/**
	 * Begins execution of the plugin.
	 *
	 * Since everything within the plugin is registered via hooks,
	 * then kicking off the plugin from this point in the file does
	 * not affect the page life cycle.
	 *
	 * @since    1.0.0
	 */
	function run_plugin_name() {

		$plugin = new WC_Colorlab();
		$plugin->run();

	}
	run_plugin_name();
} else {
	add_action( 'admin_notices', 'wc_colorlab_requirements_error' );
}

