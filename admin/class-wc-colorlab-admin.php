<?php

/**
 * Handles all admin related actions and filters
 * @package    WC_Colorlab
 * @subpackage WC_Colorlab/admin
 * @author     Printlane <info@printlane.com>
 */
class WC_Colorlab_Admin {

    /**
     * @var
     */
    private $plugin_name;

    /**
     * @var
     */
    private $version;

    /**
     * WC_Colorlab_Admin constructor.
     *
     * @param $plugin_name
     * @param $version
     */
    public function __construct( $plugin_name, $version ) {

        $this->plugin_name = $plugin_name;
        $this->version     = $version;

    }


    /**
     * Adds the settings page
     * @return WC_Colorlab_Settings
     */
    public function settings($settings) {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wc-colorlab-settings.php';
        new WC_Colorlab_Settings( $this->plugin_name );
        return $settings;
    }


    /**
     * Extends the product variation form with an "enable Printlane personalization" checkbox and Printlane ID field
     */
    public function extend_product_form_with_colorlab_fields() {
        global $thepostid, $post;
        $thepostid              = empty( $thepostid ) ? $post->ID : $thepostid;


        echo '<div class="options_group show_if_simple show_if_variable">';
        $enabled_value = get_post_meta($thepostid, '_enable_colorlab', true );
        woocommerce_wp_checkbox(
            array(
                'id'          => '_enable_colorlab['.$thepostid.']',
                'label'       => __( 'Enable Printlane personalization', 'woocommerce-colorlab' ),
                'type'        => 'text',
                'desc_tip'    => 'true',
                'description' => __( 'Tick this checkbox if the user can personalize this product', 'woocommerce-colorlab' ),
                'value'       => ($enabled_value) ? $enabled_value : 'no',
            )
        );
        echo '</div>';

        echo '<div class="options_group">';
        woocommerce_wp_text_input(
            array(
                'id'          => '_colorlab_product['.$thepostid.']',
                'label'       => __( 'Printlane Template ID (optional)', 'woocommerce-colorlab' ),
                'type'        => 'text',
                'desc_tip'    => 'true',
                'description' => __( 'Enter the Printlane Template ID (optional). If no value is entered, the SKU of the product will be used. If no SKU is set, the internal WooCommerce ID is used.', 'woocommerce-colorlab' ),
                'value'       => get_post_meta( $thepostid, '_colorlab_product', true )
            )
        );
        echo '</div>';
    }

    /**
     * Extends the product variation form with an enable Printlane checkbox and Template ID field
     */
    public function extend_product_variation_form_with_colorlab_fields($loop, $variation_data, $variation) {
        woocommerce_wp_text_input(
            array(
                'id'          => '_colorlab_product['. $variation->ID .']',
                'label'       => __( 'Printlane Template ID (optional)', 'woocommerce-colorlab' ),
                'type'        => 'text',
                'desc_tip'    => 'true',
                'description' => __( 'Enter the Printlane Template ID (optional). If no value is entered, the Printlane Template ID of the product is used. If no Template ID is set, the SKU of the variation is used.', 'woocommerce-colorlab' ),
                'value'       => get_post_meta( $variation->ID, '_colorlab_product', true )
            )
        );

        $enabled_value = get_post_meta($variation->ID, '_enable_colorlab', true );
        woocommerce_wp_checkbox(
            array(
                'id'          => '_enable_colorlab['. $variation->ID .']',
                'label'       => __( 'Enable Printlane personalization', 'woocommerce-colorlab' ),
                'type'        => 'text',
                'desc_tip'    => 'true',
                'description' => __( 'Tick this checkbox if the user can personalize this variant', 'woocommerce-colorlab' ),
                'value'       => ($enabled_value) ? $enabled_value : 'yes',
            )
        );
    }

    /**
     * Store values of Printlane Form Fields in the database
     *
     * @param $post_id
     */
    public function save_colorlab_fields( $post_id ) {
        // Store Printlane Enabled value
        $enable_colorlab = isset($_POST['_enable_colorlab'][$post_id ]) ? 'yes' : 'no';
        update_post_meta( $post_id, '_enable_colorlab', $enable_colorlab );

        // Store Printlane Template ID value
        $colorlab_product = $_POST['_colorlab_product'][$post_id];
        update_post_meta( $post_id, '_colorlab_product', esc_attr( $colorlab_product ) );
    }

    /**
     * @param $hidden
     *
     * @return array
     */
    public function hide_order_itemmeta( $hidden ) {
        return array_merge( $hidden, array(
            '_colorlab_token'
        ) );
    }

    /**
     * Send data to colorlab on a new order.
     *
     * @param $order_id
     * @param $posted_data
     * @param WC_Order $order
     */
    public function create_order_in_colorlab_api( $order_id, $posted_data, $order ) {
        if ( ! $this->should_sync_order_with_colorlab_api( $order ) ) {
            return;
        }

        ( new WC_Colorlab_Order_Sync( $order ) )->create();
    }


    /**
     * @param $order_id
     */
    public function update_order_in_colorlab_api($order_id){
        $order    = wc_get_order( $order_id );

        if ( ! $this->should_sync_order_with_colorlab_api( $order ) ) {
            return;
        }

        ( new WC_Colorlab_Order_Sync( $order ) )->update();
    }

    /**
     * Display links to open or download the design from the order detail page
     * @param  string        $value  The meta value
     * @param  WC_Meta_Data  $meta   The meta object
     * @param  WC_Order_Item $item   The order item object
     * @return string        The title
     */
    public function render_design_links_on_order_detail_page($item_id, $item, $product)
    {
        // Get the ID of the design
        $design_id = $item->get_meta('colorlab_id');

        // Exit early if not design is set
        if (empty($design_id)) return;

        // Get the token and store ID
        $design_token = $item->get_meta('_colorlab_token');
        $store_id = get_option('woocommerce_colorlab_shop_id');

        // Show links
        $design_url = 'https://studio.printlane.com/designs?id=' . $design_id;
        $open_link = '<a target="_blank" href="' . $design_url . '">Open design</a>';

        $download_url = 'https://export.printlane.com/' . $store_id . '/' . $design_id . '.' . $design_token;
        $download_link = '<a target="_blank" href="' . $download_url . '">Download design</a>';

        // Helper for adding spaces
        $spaces = '&nbsp;&nbsp;';

        // Show links
        echo $open_link . $spaces . $download_link;
    }

    /**
     * @param $order
     *
     * @return bool
     */
    private function should_sync_order_with_colorlab_api( $order ) {
        $api_key    = get_option( 'woocommerce_colorlab_api_key' );
        $api_secret = get_option( 'woocommerce_colorlab_secret_key' );

        return $this->has_colorlab_customization_in_order( $order ) && ( $api_key && $api_secret );
    }

    /**
     * @param WC_Order $order
     *
     * @return bool
     */
    private function has_colorlab_customization_in_order( $order ) {
        foreach ( $order->get_items() as $item_key => $item ) {
            $meta_items = $item->get_meta_data();
            foreach ( $meta_items as $meta ) {
                if ( $meta->key == 'colorlab_id' ) {
                    return true;
                }
            }
        };

        return false;
    }
}
