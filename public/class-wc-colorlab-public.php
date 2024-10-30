<?php

/**
 * The public-facing functionality of the plugin.
 * @package    WC_Colorlab
 * @subpackage WC_Colorlab/public
 * @author     Printlane <info@printlane.com>
 */
class WC_Colorlab_Public {

    /**
     * @var string
     */
    private $plugin_name;

    /**
     * @var string
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     * @since    1.0.0
     *
     * @param      string $plugin_name The name of the plugin.
     * @param      string $version The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {

        $this->plugin_name = $plugin_name;
        $this->version     = $version;

    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     * @since    1.0.0
     */
    public function enqueue_styles() {
        if (function_exists('is_cart') && is_cart()) {
            wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/style.css', array(), $this->version, 'all' );
        }
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     * @since    1.0.0
     */
    public function enqueue_scripts() {
	    if (!function_exists('is_cart') || !function_exists('is_product')) return;

	    // load the library
        wp_enqueue_script( 'printlane', 'https://designer.printlane.com/js/include.js', array(), $this->version, true );

        // woocommerce integration
        wp_register_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/main.js', array(
            'jquery',
            'printlane'
        ), $this->version, true );
        $data = $this->get_colorlab_parameters();
        wp_localize_script( $this->plugin_name, 'woocommerce_printlane_data', $data );
        wp_enqueue_script( $this->plugin_name );
    }


    /**
     * Adds the printlane design token
     *
     * @param $cart_item_meta
     * @param $product_id
     * @param null $post_data
     * @param bool|false $test
     *
     * @return mixed
     */
    public function add_token_to_cart_item( $cart_item_meta, $product_id, $post_data = null, $test = false ) {
        if ( is_null( $post_data ) && isset( $_POST ) ) {
            $post_data = $_POST;
        }

        // append color lab token
        if ( isset( $post_data['colorlab_id'] ) && $post_data['colorlab_id'] ) {
            $cart_item_meta['colorlab_id'] = $post_data['colorlab_id'];
        }

        if ( isset( $post_data['colorlab_token'] ) && $post_data['colorlab_token'] ) {
            $cart_item_meta['colorlab_token'] = $post_data['colorlab_token'];
        }

        return $cart_item_meta;
    }


    /**
     * @param $cart_item
     * @param $values
     *
     * @return mixed
     */
    public function get_cart_item_from_session( $cart_item, $values ) {
        if ( ! empty( $values['colorlab_id'] ) ) {
            $cart_item['colorlab_id'] = $values['colorlab_id'];
        }

        if ( ! empty( $values['colorlab_token'] ) ) {
            $cart_item['colorlab_token'] = $values['colorlab_token'];
        }

        return $cart_item;
    }



    /**
     * Action executed on woocommerce_checkout_create_order_line_item responsible for saving the unique id and token of the design on the order line
     * @access public
     *
     * @param mixed $item
     * @param mixed $cart_item_key
     * @param mixed $values
     * @param mixed $order
     *
     * @return void
     */
    public function create_order_line_item_action($item, $cart_item_key, $values, $order) {
        if (!empty($values['colorlab_id'])) {
            $item->add_meta_data( 'colorlab_id', $values['colorlab_id'] );
        }
        if (!empty($values['colorlab_token'])) {
            $item->add_meta_data( '_colorlab_token', $values['colorlab_token'] );
        }
    }


    /**
     * Hide colorlab id for the customer
     *
     * @param $output
     * @param $item_meta
     *
     * @return string
     */
    public function hide_order_item_meta( $output, $item_meta ) {
        return '';
    }

    /**
     * Adds customize link for the products that have a token
     *
     * @param $html
     * @param $cart_item
     * @param $cart_item_key
     *
     * @return mixed
     */
    public function add_customize_link( $cart_item, $cart_item_key ) {
        // Check if the cart item is part of a wc product bundle, if so, don't apply printlane functionality as it is applied to the bundle container
        if (function_exists('wc_pb_is_bundled_cart_item') && wc_pb_is_bundled_cart_item($cart_item)) {
            return;
        }

        $text      = __( 'Change customization', 'woocommerce-colorlab' );
        $user_text = get_option( 'woocommerce_colorlab_customization_text' );
        if ( $user_text ) {
            $text = $user_text;
        }

        if ( isset( $cart_item['colorlab_token'] ) && $cart_item['colorlab_token'] ) {
            echo apply_filters( 'woocommerce_colorlab_customization_link', sprintf( '<div class="colorlab-edit-personalisation-wrapper"><a href="#" class="colorlab-edit-personalisation" data-colorlab-id="%s" data-colorlab-token="%s">%s</a></div>',
                $cart_item['colorlab_id'],
                $cart_item['colorlab_token'],
                $text
            ), $cart_item, $cart_item_key );
        }
    }


    /**
     * @param $display_key
     * @param $meta
     * @param $order
     *
     * @return string
     */
    public function rename_colorlab_id_to_reference($display_key, $meta, $order)
    {
        if ($display_key == 'colorlab_id' && ! is_admin()) {
            return __( 'reference', 'woocommerce-colorlab' );
        }

        return $display_key;
    }

    /**
     * @param $display_key
     * @param $meta
     * @param $order
     *
     * @return string
     */
    public function rename_colorlab_id_in_admin($display_key, $meta, $order)
    {
        if ($display_key == 'colorlab_id' && is_admin()) return 'Design&nbsp;reference';
        return $display_key;
    }

    /**
     * Gets colorlab params
     * @return array
     */
    private function get_colorlab_parameters() {
        $lang = explode( '-', get_bloginfo( 'language' ) );

        $add_to_cart_text      = __( 'Customize product', 'woocommerce-colorlab' ); // the default
        $user_text = get_option( 'woocommerce_colorlab_add_to_cart_button' );
        if ($user_text !== false) {
            $add_to_cart_text = __( $user_text, 'woocommerce-colorlab' );
        }

	    $change_customization_text      = __( 'Change customization', 'woocommerce-colorlab' ); // the default
	    $change_customization_text_setting_value = get_option( 'woocommerce_colorlab_customization_text' );
	    if ( $change_customization_text_setting_value ) {
		    $change_customization_text = __( $change_customization_text_setting_value, 'woocommerce-colorlab' );
	    }

        $btn_selector = get_option('woocommerce_printlane_button_selector', '.single_add_to_cart_button');
        if (empty($btn_selector)) {
            $btn_selector = '.single_add_to_cart_button';
        }

        $data = array(
			'enable_on_product_page' => $this->enabled_on_product_page(),
            'shop'            => get_option('woocommerce_colorlab_shop_id'),
            'cart_thumbnails' => get_option('woocommerce_colorlab_cart_thumbnails'),
            'language'        => $lang[0], // get ISO code only
            'add_to_cart_text' => $add_to_cart_text,
            'button_selector' => $btn_selector,
	        'change_customization_text' => $change_customization_text,
	        'on_cart_page' => is_cart(),
	        'has_block_layout' => class_exists( 'WC_Blocks_Utils' ),
        );

        if (is_product()) {
            $product         = wc_get_product($this->get_product_id());
            $data['product'] = $this->get_product_template_id($product);
            $wpml_enabled = has_filter('wpml_element_trid') && has_filter('wpml_get_element_translations');

            if ($product->is_type( 'variable' )) {
                $variations = $product->get_available_variations();

                foreach ($variations as $variation) {
                    $variation_id = $variation['variation_id'];

                    // Check if Printlane personalization is enabled for this product variation
                    $colorlab_enabled = get_post_meta($variation_id, '_enable_colorlab', true);
                    if ($colorlab_enabled === 'no') {
                        continue;
                    }

                    // Set the output variation_id to the non-translated variant_id
                    $output_variation_id = $variation_id;

                    // Get translated variation
                    if ($wpml_enabled) {
                        $trid = apply_filters('wpml_element_trid', NULL, $variation['variation_id']);
                        $translations = apply_filters( 'wpml_get_element_translations', NULL, $trid); // https://wpml.org/wpml-hook/wpml_get_element_translations/
                        $current_translation = $translations[$lang[0]];

                        if (isset($current_translation)) {
                            $variation_element_id = $current_translation->element_id;
                            // Update the variation id for the output
                            $output_variation_id = $variation_element_id;
                        }
                    }

                    $data['variations'][ $output_variation_id ] = $this->get_variation_template_id($variation, $product);
                }
            }
        }

        return $data;
    }

    /**
     * Get the Printlane Template ID for a WooCommerce product
     * @param WC_Product $product
     * @return string $colorlabTemplateId
     */
    private function get_product_template_id( $product ) {
        $colorlab_product = get_post_meta( $product->get_id(), '_colorlab_product', true );
        if ($colorlab_product) return $colorlab_product;
        if ($product->get_sku()) return $product->get_sku();
        return $product->get_id();
    }

    /**
     * Get the Printlane Template ID for a WooCommerce product variation
     * @return string $printlaneTemplateId
     */
    private function get_variation_template_id($variation, $product) {
        // 1. if a Printlane Template ID is set on the product variation use this
        $colorlab_product = get_post_meta($variation['variation_id'], '_colorlab_product', true);
        if ( $colorlab_product ) return $colorlab_product;

        // 2. if no Printlane Template ID is set on the product variation, check for an id on product level
        $colorlab_product = get_post_meta($product->get_id(), '_colorlab_product', true);
        if ( $colorlab_product ) return $colorlab_product;

        // 3. Use variant SKU as Template ID
        if ($variation['sku']) return $variation['sku'];

        // Default to variation id
        return $variation['variation_id'];
    }

    /**
     * Checks if this should be shown on the product page
     * @return bool
     */
    private function enabled_on_product_page() {
        // we don't have this enabled
        if ( is_product() ) {
            $product = wc_get_product($this->get_product_id());
            if ( ! ( $product->is_type( 'simple' ) || $product->is_type( 'variable' ) || $product->is_type( 'bundle' ) ) ) { // neither simple nor variable
                return false;
            }

            $use_colorlab = get_post_meta($this->get_product_id(), '_enable_colorlab', true );
            if ( $use_colorlab !== 'yes' ) { // not enabled
                return false;
            }
        }

        return true;
    }

    /**
     * Returns the product ID based on get_the_ID, or uses wpml_object_id to retrieve the ID of the source post that contains the colorlab metadata
     * @return the product id of the original (non-translated) post
     */
    private function get_product_id() {
        // Get the id of the product
        $id = get_the_ID();

        // WPML implementation to get the original product (since colorlab metadata is not stored on translations)
        if (has_filter('wpml_object_id') && has_filter('wpml_default_language')) {
            // Get the default language
            $default_lang = apply_filters('wpml_default_language', NULL);

            // Get the id of the source used for this translation
            $source_id = apply_filters('wpml_object_id', $id, 'product', TRUE, $default_lang);
            if (is_numeric($source_id)) {
                return $source_id;
            }
        }

        // Default
        return $id;
    }

    /**
     * Remove Printlane reference from order emails
     */
    public function filter_hide_colorlab_reference($formatted_meta, $item) {
        $hide_colorlab_reference = get_option('woocommerce_colorlab_hide_reference');
        // Hide colorlab reference on non admin pages and emails
        if (!is_admin() && $hide_colorlab_reference === 'yes') {
            foreach( $formatted_meta as $key => $meta ){
                if ($meta->key === 'colorlab_id') {
                    unset($formatted_meta[$key]);
                }
            }
        }
        return $formatted_meta;
    }

	/**
	 * Add a printlane link placeholder to the cart items when using WooCommerce blocks.
	 */
	public function add_printlane_link_to_wc_cart_blocks( $item_data, $cart_item_data ) {
		// Exit early in case we are not on the cart page or the block utils class does not exist
		if( !class_exists( 'WC_Blocks_Utils' ) || !isset( $cart_item_data['colorlab_id'], $cart_item_data['colorlab_token'] )) return $item_data;

		// Only enable when a woocommerce version newer than 8.3 is installed (from 8.3 cart blocks is enabled by default)
		if (version_compare( WC_VERSION, '8.3.0', '<' )) return $item_data;

        // In case the cart item is part of a product bundle, don't add the edit design link
        if (isset($cart_item_data['bundled_by'])) return $item_data;

		// Check if woocommerce blocks are used on the cart page
		$has_block_on_cart = WC_Blocks_Utils::has_block_in_page( wc_get_page_id('cart'), 'woocommerce/cart' );

		// Add printlane design link if the block layout is used in the cart or always add in the minicart
		if ( ($has_block_on_cart && is_cart()) || !is_cart()) {
			$printlane_link_item = array(
				'key'   => 'printlane-design-link',
				'value' => $cart_item_data['colorlab_id'] . ":" . $cart_item_data['colorlab_token'],
			);

			// Always show change design link as first item
			array_unshift($item_data, $printlane_link_item);
		}

		return $item_data;
	}
}
