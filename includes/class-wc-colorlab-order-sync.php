<?php

/**
 * The core plugin class.
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 * @since      1.0.7
 * @package    WC_Colorlab_Order_Sync
 * @subpackage WC_Colorlab_Order_Sync/includes
 * @author     Printlane <info@printlane.com>
 */
class WC_Colorlab_Order_Sync {
	/**
	 * @var WC_Order
	 */
	protected $order;

	/**
	 * @var string
	 */
	protected $endpoint;

	/**
	 * @var string
	 */
	protected $shop_id;

	/**
	 * @var string
	 */
	protected $api_key;

	/**
	 * @var string
	 */
	protected $api_secret;

	/**
	 * WC_Colorlab_Order_Sync constructor.
	 *
	 * @param $order
	 */
	public function __construct( $order ) {
		$this->order      = $order;
		$this->endpoint   = 'https://api.printlane.com/2023-10/orders';
		$this->shop_id    = get_option( 'woocommerce_colorlab_shop_id' );
		$this->api_key    = get_option( 'woocommerce_colorlab_api_key' );
		$this->api_secret = get_option( 'woocommerce_colorlab_secret_key' );
	}

	public function create() {
		$args     = array(
			'headers' => $this->get_headers(),
			'body'    => $this->get_body()
		);
		$response = wp_remote_post( $this->endpoint, $args );

		if ( $response instanceof WP_Error ) {
			// Maybe log.
		}
	}

	public function update() {
		$args = array(
			'headers' => $this->get_headers(),
			'body'    => $this->get_body()
		);

		$endpoint = $this->endpoint . '/' . $this->order->get_order_number();
		$response = wp_remote_post( $endpoint, $args );

		if ( $response instanceof WP_Error ) {
			// Maybe log.
		}
	}


	/**
	 * @return array
	 */
	protected function get_headers() {
		return array(
			'Content-Type'             => 'application/json',
			'X-Printlane-Store'          => $this->shop_id,
			'X-Printlane-Api-Key'       => $this->api_key,
			'X-Printlane-Api-Signature' => $this->get_api_signature()
		);
	}

	/**
	 * @return array
	 */
	protected function get_body(  ) {
		$user = $this->order->get_user();

		$shipping_details = $this->get_shipping_details_in_api_format();
		$billing_details = $this->get_billing_details_in_api_format();

		return json_encode(array(
			'billingDetails'  => $this->get_billing_details_in_api_format(),
			'created'         => $this->order->get_date_created()->format(DATE_ISO8601),
			'domain'          => $this->get_site_domain(),
			'email'           => $user ? $user->user_email : $this->order->get_billing_email(),
			'firstName'       => $user ? $user->user_firstname : $this->order->get_billing_first_name(),
			'lastName'        => $user ? $user->user_lastname : $this->order->get_billing_last_name(),
			'lineItems'       => $this->get_line_items_in_api_format(),
			'orderId'         => (string) $this->order->get_order_number(),
			'shippingDetails' => ($shipping_details) ? $shipping_details : $billing_details,
			'status'          => $this->order->get_status(),
			'updated'         => $this->order->get_date_modified()->format(DATE_ISO8601)
		));
	}


	/**
	 * @return false|string
	 */
	protected function get_api_signature() {
		$verification_string = $this->shop_id . $this->order->get_order_number();

		return hash_hmac( 'sha256', $verification_string, $this->api_secret );
	}

	/**
	 * @return mixed
	 */
	protected function get_site_domain() {
		$url_parts = parse_url( home_url() );

		return $url_parts['host'];
	}

	/**
	 * @return array
	 */
	protected function get_billing_details_in_api_format() {
		$country_code = $this->order->get_billing_country();

		return array(
			'address1'    => $this->order->get_billing_address_1(),
			'address2'    => $this->order->get_billing_address_2(),
			'city'        => $this->order->get_billing_city(),
			'companyName' => $this->order->get_billing_company(),
			'country'     => $this->get_country_name_from_code($country_code),
			'countryCode' => $country_code,
			'firstName'   => $this->order->get_billing_first_name(),
			'lastName'    => $this->order->get_billing_last_name(),
			'phone'       => $this->order->get_billing_phone(),
			'province'    => $this->order->get_billing_state(),
			'zip'         => $this->order->get_billing_postcode(),
		);
	}

	/**
	 * @return array
	 */
	protected function get_shipping_details_in_api_format() {
		$country_code = $this->order->get_shipping_country();

		// Check if required fields for Printlane Order API are set.
		if (empty($this->order->get_shipping_first_name()) || empty($this->order->get_shipping_last_name())) {
			return [];
		}

		return array(
			'address1'    => $this->order->get_shipping_address_1(),
			'address2'    => $this->order->get_shipping_address_2(),
			'city'        => $this->order->get_shipping_city(),
			'companyName' => $this->order->get_shipping_company(),
			'country'     => $this->get_country_name_from_code($country_code),
			'countryCode' => $country_code,
			'firstName'   => $this->order->get_shipping_first_name(),
			'lastName'    => $this->order->get_shipping_last_name(),
			'phone'       => $this->order->get_shipping_phone(),
			'province'    => $this->order->get_shipping_state(),
			'zip'         => $this->order->get_shipping_postcode(),
		);
	}

	/**
	 * @return array
	 */
	protected function get_line_items_in_api_format() {
		$line_items = array();

		foreach ( $this->order->get_items() as $item ) {
			$colorlab_id = $this->get_product_id_from_order_item( $item );

			// No id, means that this item is not a customized product.
			if ( ! $colorlab_id ) {
				continue;
			}

			$line_items[] = array(
				'id'       => $colorlab_id,
				'token'    => $this->get_product_token_from_order_item($item),
				'quantity' => $item->get_quantity(),
				'price'    => $this->order->get_item_total($item),
			);
		};

		return $line_items;
	}


	/**
	 * @param WC_Order_Item $item
	 *
	 * @return mixed
	 */
	protected function get_product_id_from_order_item( $item ) {
		$meta_items = $item->get_meta_data();
		foreach ( $meta_items as $meta ) {
			if ( $meta->key == 'colorlab_id' ) {
				return $meta->value;
			}
		}

		return false;
	}

	/**
	 * @param WC_Order_Item $item
	 *
	 * @return mixed
	 */
	protected function get_product_token_from_order_item( $item ) {
		$meta_items = $item->get_meta_data();
		foreach ( $meta_items as $meta ) {
			if ( $meta->key == '_colorlab_token' ) {
				return $meta->value;
			}
		}

		return false;
	}

	/**
	 * @param $code
	 *
	 * @return mixed
	 */
	protected function get_country_name_from_code( $code ) {
		$countries = WC()->countries->countries;

		return isset( $countries[ $code ] ) ? $countries[ $code ] : $code;
	}
}
