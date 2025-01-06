<?php
/**
 * Plugin Name: Car Dealer - WP All Import Addon
 * Plugin URI:  http://www.potenzaglobalsolutions.com/
 * Description: This plugin contains important functions and features for "Car Dealer" theme.
 * Version:     1.2.0
 * Author:      Potenza Global Solutions
 * Author URI:  http://www.potenzaglobalsolutions.com/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: cd-wpai-addon
 *
 * @package cardealer-wp-all-import-addon
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require 'rapid-addon.php';

/**
 * CD_WPAI_Addon class.
 */
final class CD_WPAI_Addon {

	/**
	 * The single instance of the class.
	 *
	 * @var CD_WPAI_Addon|null
	 */
	protected static $instance;

	/**
	 * The RapidAddon instance.
	 *
	 * @var RapidAddon
	 */
	protected $add_on;
	/**
	 * Review stamp_limit.
	 *
	 * @var string|int
	 */
	protected $review_stamp_limit;

	/**
	 * Plugin name
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Gets the main CD_WPAI_Addon Instance.
	 *
	 * @static
	 * @return CD_WPAI_Addon instance
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * The Constructor.
	 */
	protected function __construct() {

		$this->name = esc_html__( 'Car Dealer - WP All Import Addon', 'cd-wpai-addon' );

		$this->load_plugin_textdomain();

		// Define the add-on.
		$this->add_on = new RapidAddon( 'Car Dealer Custom Fields', 'cd_custom_fields' );

		$this->add_on->add_field( 'vehicle_images', 'Vehicle Images URL separator (By default - ",")', 'images' );
		$this->add_on->add_field( 'regular_price', 'Regular price', 'text' );
		$this->add_on->add_field( 'sale_price', 'Sale price', 'text' );
		$this->add_on->add_field( 'tax_label', 'Tax Label', 'text' );
		$this->add_on->add_field( 'city_mpg', 'City MPG', 'text' );
		$this->add_on->add_field( 'highway_mpg', 'Highway MPG', 'text' );
		$this->add_on->add_field( 'brochure_upload', 'Brochure Upload', 'brochure_upload' );
		$this->add_on->add_field( 'video_link', 'Video Link', 'text' );
		$this->add_on->add_field(
			'car_status',
			'Vehicle Status',
			'radio',
			array(
				'sold'   => 'Sold',
				'unsold' => 'UnSold',
			)
		);
		$this->add_on->add_field( 'vehicle_overview', 'Vehicle Overview', 'textarea' );
		$this->add_on->add_field( 'technical_specifications', 'Technical Specifications', 'textarea' );
		$this->add_on->add_field( 'general_information', 'General Information', 'textarea' );
		$this->add_on->add_field( 'vehicle_location_address', 'Vehicle Location Address', 'text' );
		$this->add_on->add_field( 'vehicle_location_lat', 'Vehicle Location Lat', 'text' );
		$this->add_on->add_field( 'vehicle_location_lng', 'Vehicle Location Lng', 'text' );
		$this->add_on->add_field(
			'sell_vehicle_status',
			esc_html__( 'Sell Vehicle Online', 'cd-wpai-addo' ),
			'radio',
			array(
				'enable'  => esc_html__( 'Enable', 'cd-wpai-addo' ),
				'disable' => esc_html__( 'Disable', 'cd-wpai-addo' ),
			)
		);
		$this->add_on->add_field( 'total_vehicle_in_stock', esc_html__( 'Stock Quantity', 'cd-wpai-addo' ), 'text' );

		// Get Limit From theme option to create dynamicaly review stamp fields.
		$review_stamp_limit_opt   = ( class_exists( 'Redux' ) ) ? Redux::get_option( 'car_dealer_options', 'review_stamp_limit' ) : 1;
		$this->review_stamp_limit = isset( $review_stamp_limit_opt ) ? $review_stamp_limit_opt : 1;

		for ( $i = 1; $i <= $this->review_stamp_limit; $i++ ) {
			$this->add_on->add_field( 'review_stamp_logo_' . $i, 'Review Stamp Logo ' . $i, 'stemp_image' );
			$this->add_on->add_field( 'review_stamp_link_' . $i, 'Review Stamp Link ' . $i, 'text' );
		}

		$this->add_on->set_import_function( array( $this, 'import' ) );

		// Set post type icons.
		$this->add_on->set_post_type_image(
			array(
				'cars',
				'cars_geofencing',
				'testimonials',
				'teams',
				'faqs',
				'cars_promocodes',
			),
			array(
				untrailingslashit( plugins_url( '/img/cardealer-icon.png', __FILE__ ) ),
				'dashicons-location',
				'dashicons-testimonial',
				'dashicons-groups',
				'dashicons-info',
				'dashicons-tickets-alt',
			)
		);

		// Remove Pst types.
		$this->add_on->remove_post_type( array( 'pgs_export_log', 'pgs_import_log', 'financial_inquiry', 'pgs_inquiry', 'make_offer', 'schedule_test_drive' ) );
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Undocumented function
	 *
	 * @param string|int $post_id         Post ID.
	 * @param array      $data            Posted data.
	 * @param array      $import_options  Import Options.
	 * @param array      $article         Article.
	 * @return void
	 */
	public function import( $post_id, $data, $import_options, $article ) {

		$fields = array(
			'regular_price',
			'sale_price',
			'tax_label',
			'city_mpg',
			'highway_mpg',
			'brochure_upload',
			'video_link',
			'car_status',
			'vehicle_overview',
			'technical_specifications',
			'general_information',
			'vehicle_location_address',
			'vehicle_location_lat',
			'vehicle_location_lng',
			'sell_vehicle_status',
			'total_vehicle_in_stock',
		);

		foreach ( $fields as $field ) {
			// Make sure the user has allowed this field to be updated.
			if ( empty( $article['ID'] ) || $this->add_on->can_update_meta( $field, $import_options ) ) {
				update_field( $field, $data[ $field ], $post_id );
			}
		}

		for ( $i = 1; $i <= $this->review_stamp_limit; $i++ ) {
			// Make sure the user has allowed this field to be updated.
			if ( empty( $article['ID'] ) || $this->add_on->can_update_meta( $field, $import_options ) ) {
				update_field( 'review_stamp_link_' . $i, $data[ 'review_stamp_link_' . $i ], $post_id );
			}
		}

		$field_name = 'vehicle_location';
		$value      = array(
			'address' => $data['vehicle_location_address'],
			'lat'     => $data['vehicle_location_lat'],
			'lng'     => $data['vehicle_location_lng'],
			'zoom'    => '10',
		);
		update_field( $field_name, $value, $post_id );
	}

	/**
	 * Init function.
	 *
	 * @return void
	 */
	public function init() {

		$this->add_on->admin_notice();

		$this->add_on->run(
			array(
				'post_types' => array( 'cars' ),
			)
		);
	}

	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present
	 *
	 * @access public
	 * @return void
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'cd-wpai-addon' );

		load_plugin_textdomain( 'cd-wpai-addon', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
}

CD_WPAI_Addon::get_instance();
