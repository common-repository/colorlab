<?php

/**
 * The core plugin class.
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 * @since      1.0.0
 * @package    WC_Colorlab
 * @subpackage WC_Colorlab/includes
 * @author     Printlane <info@printlane.com>
 */
class WC_Colorlab {

    /**
     * @var string
     */
    protected $plugin_name;

    /**
     * @var string
     */
    protected $version;

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     * @since    1.0.0
     * @access   protected
     * @var      WC_Colorlab_Loader $loader Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * @since    1.0.1
     */
    public function __construct() {

        $this->plugin_name = 'woocommerce-colorlab';
        $this->version     = '1.5.5';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();

    }

    /**
     * Load the required dependencies for this plugin.
     * Include the following files that make up the plugin:
     * - WC_Colorlab_Loader. Orchestrates the hooks of the plugin.
     * - WC_Colorlab_Admin. Defines all hooks for the admin area.
     * - WC_Colorlab_Public. Defines all hooks for the public side of the site.
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-colorlab-loader.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-colorlab-order-sync.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wc-colorlab-admin.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-wc-colorlab-public.php';

        $this->loader = new WC_Colorlab_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        $this->loader->add_action( 'plugins_loaded', $this, 'load_plugin_textdomain' );
    }

    /**
     * Load the plugin text domain for translation.
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'woocommerce-colorlab',
            false,
            dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
        );
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {

        $plugin_admin = new WC_Colorlab_Admin( $this->get_plugin_name(), $this->get_version() );

        // add woocommerce tab
        $this->loader->add_filter( 'woocommerce_get_settings_pages', $plugin_admin, 'settings', 15 );

        // hide colorlab order item meta data
        $this->loader->add_filter('woocommerce_hidden_order_itemmeta', $plugin_admin, 'hide_order_itemmeta', 10);

        // add colorlab product field on product page
        $this->loader->add_action( 'woocommerce_product_options_general_product_data', $plugin_admin, 'extend_product_form_with_colorlab_fields' );
        $this->loader->add_action( 'woocommerce_process_product_meta', $plugin_admin, 'save_colorlab_fields' );

        // add colorlab fields on variations
        $this->loader->add_action( 'woocommerce_product_after_variable_attributes', $plugin_admin, 'extend_product_variation_form_with_colorlab_fields', 10, 3 );
        $this->loader->add_action( 'woocommerce_save_product_variation', $plugin_admin, 'save_colorlab_fields', 10, 2 );

        // Sync order with colorlab api
        $this->loader->add_action('woocommerce_checkout_order_processed', $plugin_admin, 'create_order_in_colorlab_api', 10, 3);
        $this->loader->add_action('woocommerce_update_order', $plugin_admin, 'update_order_in_colorlab_api', 10, 1);

        // Add actions to line items to open/download design
        $this->loader->add_action( 'woocommerce_after_order_itemmeta', $plugin_admin, 'render_design_links_on_order_detail_page', 10, 3 );
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {

        $plugin_public = new WC_Colorlab_Public( $this->get_plugin_name(), $this->get_version() );

        // enqueue
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

        // add token and id on add to cart
        $this->loader->add_action( 'woocommerce_add_cart_item_data', $plugin_public, 'add_token_to_cart_item', 10, 2 );

        // load cart data from session, on page load
        $this->loader->add_filter( 'woocommerce_get_cart_item_from_session', $plugin_public, 'get_cart_item_from_session', 20, 2 );

        // add the meta to the order
        $this->loader->add_action( 'woocommerce_checkout_create_order_line_item', $plugin_public, 'create_order_line_item_action', 10, 4 );

        // @deprecated since 3.0
        // left to be used for earlier versions
        // hide order meta on the thank you page/emails
        $this->loader->add_filter( 'woocommerce_order_items_meta_display', $plugin_public, 'hide_order_item_meta', 10, 2 );

        // Remove reference to colorlab customization
        $this->loader->add_filter( 'woocommerce_order_item_get_formatted_meta_data', $plugin_public, 'filter_hide_colorlab_reference', 10, 2 );

        // add customize link on the cart
        $this->loader->add_action( 'woocommerce_after_cart_item_name', $plugin_public, 'add_customize_link', 10, 3 );

        // rename 'colorlab_id' with 'reference' in frontend
        $this->loader->add_action( 'woocommerce_order_item_display_meta_key', $plugin_public, 'rename_colorlab_id_to_reference', 10, 3 );

        // rename 'colorlab_id' with 'Design reference' in backend
        $this->loader->add_action( 'woocommerce_order_item_display_meta_key', $plugin_public, 'rename_colorlab_id_in_admin', 11, 3 );

		// Add a printlane change customization link placeholder when using woocommerce blocks
	    $this->loader->add_filter( 'woocommerce_get_item_data', $plugin_public, 'add_printlane_link_to_wc_cart_blocks', 10, 2 );
	}

    /**
     * Run the loader to execute all of the hooks with WordPress.
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     * @since     1.0.0
     * @return    WC_Colorlab_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}
