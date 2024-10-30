<?php

/**
 * Adds setting page under woocommerce tabs
 * @package    WC_Colorlab_Settings
 * @subpackage WC_Colorlab_Settings/admin
 * @author     Printlane <info@printlane.com>
 */
class WC_Colorlab_Settings extends WC_Settings_Page {

	/**
	 * Setup settings class
	 * @since  1.0
	 */
	public function __construct( $id ) {

		$this->id    = $id;
		$this->label = __( 'Printlane', 'woocommerce-colorlab' );

		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
		add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );
	}


	/**
	 * Get settings array
	 * @since 1.0.0
	 *
	 * @param string $current_section Optional. Defaults to empty string.
	 *
	 * @return array Array of settings
	 */
	public function get_settings( $current_section = '' ) {

		$settings = apply_filters( 'woocommerce_colorlab_section1_settings', array(
			array(
				'name' => __( 'Printlane Settings', 'woocommerce-colorlab' ),
				'type' => 'title',
				'desc' => '',
				'id'   => 'woocommerce_colorlab_options'
			),
			array(
				'type'     => 'text',
				'id'       => 'woocommerce_colorlab_shop_id',
				'name'     => __( 'Store ID', 'woocommerce-colorlab' ),
				'desc' => __( 'Enter the Store ID from your Printlane account', 'woocommerce-colorlab' )
			),
			array(
				'type'     => 'text',
				'id'       => 'woocommerce_colorlab_api_key',
				'name'     => __( 'API key', 'woocommerce-colorlab' ),
				'desc' => __( 'The API key created in your Printlane account', 'woocommerce-colorlab' )
			),
			array(
				'type'     => 'text',
				'id'       => 'woocommerce_colorlab_secret_key',
				'name'     => __( 'API secret', 'woocommerce-colorlab' ),
				'desc' => __( 'The secret linked to your API key', 'woocommerce-colorlab' )
			),
			array(
				'type'     => 'text',
				'id'       => 'woocommerce_colorlab_add_to_cart_button',
				'name'     => __( 'Button text', 'woocommerce-colorlab' ),
				'desc' => __( 'Text shown on the add to cart button when a product is personalizable. Leave empty to not replace the default button text.', 'woocommerce-colorlab' )
			),
            array(
                'name'     => __( 'Button CSS Selector', 'woocommerce-colorlab' ),
                'desc' => __( 'A valid <a href="https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_selectors" target="_blank">CSS selector string</a> that matches a button of type "submit" that opens the designer on product pages. The selector must point to a single button of type "submit" inside the product form. (!) Do not change unless you want to keep using the default Add To Cart button to start designing. Please visit the <a href="https://help.printlane.com/integrations/wordpress.html" target="_blank">Help Center</a> for more information about this setting.', 'woocommerce-colorlab' ),
                'type'     => 'text',
                'id'       => 'woocommerce_printlane_button_selector',
                'default' => '.single_add_to_cart_button',
            ),
			array(
				'type'     => 'text',
				'id'       => 'woocommerce_colorlab_customization_text',
				'name'     => __( 'Change design text', 'woocommerce-colorlab' ),
				'desc' => __( 'Text shown in a link on the cart page that allows users to review or change their design.', 'woocommerce-colorlab' )
			),
            array(
                'name'    => __('Enable thumbnails in cart (beta)', 'woocommerce-colorlab' ),
                'desc'    => __('Replace product images in the cart with a thumbnail of the user design.', 'woocommerce-colorlab' ),
                'id'      => 'woocommerce_colorlab_cart_thumbnails',
                'std'     => 'no', // WooCommerce < 2.0
                'default' => 'no', // WooCommerce >= 2.0
                'type'    => 'checkbox'
            ),
            array(
                'name'    => __('Hide Printlane reference', 'woocommerce-colorlab' ),
                'desc'    => __('Hide the Printlane reference on order emails and pages', 'woocommerce-colorlab' ),
                'id'      => 'woocommerce_colorlab_hide_reference',
                'std'     => 'no', // WooCommerce < 2.0
                'default' => 'no', // WooCommerce >= 2.0
                'type'    => 'checkbox'
            ),
			array(
				'type' => 'sectionend',
				'id'   => 'woocommerce_colorlab_options'
			),

		) );


		return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings, $current_section );
	}


	/**
	 * Output the settings
	 * @since 1.0
	 */
	public function output() {

		global $current_section;

		$settings = $this->get_settings( $current_section );
		WC_Admin_Settings::output_fields( $settings );
	}


	/**
	 * Save settings
	 * @since 1.0
	 */
	public function save() {

		global $current_section;

		$settings = $this->get_settings( $current_section );
		WC_Admin_Settings::save_fields( $settings );
	}
}
