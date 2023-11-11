<?php

namespace Sitewide_Sales\modules;

use Sitewide_Sales\classes;

defined( 'ABSPATH' ) || die( 'File cannot be accessed directly' );

class SWSales_Module_Custom {

	/**
	 * Initial plugin setup
	 *
	 * @package sitewide-sale/modules
	 */
	public static function init() {
		// Register sale type.
		add_filter( 'swsales_sale_types', array( __CLASS__, 'register_sale_type' ) );

		// Add fields to Edit Sitewide Sale page.
		add_action( 'swsales_after_choose_sale_type', array( __CLASS__, 'swsales_after_choose_sale_type' ) );

		// Enable saving of fields added above.
		add_action( 'swsales_save_metaboxes', array( __CLASS__, 'swsales_save_metaboxes' ), 10, 2 );

		// Enqueue JS for Edit Sitewide Sale page.
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );

		// For the swsales_coupon helper function.
		add_filter( 'swsales_coupon', array( __CLASS__, 'swsales_coupon' ), 10, 2 );
	
		// Track conversions.
		add_action( 'wp', array( __CLASS__, 'track_conversions' ) );
	
		// Filter reports.
		add_filter( 'swsales_get_checkout_conversions', array( __CLASS__, 'checkout_conversions' ), 10, 2 );
		add_filter( 'swsales_get_revenue', array( __CLASS__, 'sale_revenue' ), 10, 2 );
	} // end init()

	/**
	 * Register custom module with SWSales
	 *
	 * @param  array $sale_types that are registered in SWSales.
	 * @return array
	 */
	public static function register_sale_type( $sale_types ) {
		$sale_types['custom'] = 'Custom';
		return $sale_types;
	} // end register_sale_type()

	/**
	 * Adds text field to enter coupon.
	 *
	 * @param SWSales_Sitewide_Sale $cur_sale that is being edited.
	 */
	public static function swsales_after_choose_sale_type( $cur_sale ) {
		?>
		<tr class='swsales-module-row swsales-module-row-custom'>
			<?php
			$current_coupon = $cur_sale->get_meta_value( 'swsales_custom_coupon', '' );
			?>
			<th><label for="swsales_custom_coupon"><?php esc_html_e( 'Coupon', 'sitewide-sales' ); ?></label></th>
			<td>
				<input type="text" class="swsales_option" id="swsales_custom_coupon" name="swsales_custom_coupon" value='<?php esc_html_e( $current_coupon ); ?>'>
				<p class="description"><?php esc_html_e( "If you would like a coupon associated with your sale, you can set it up in whatever eCommerce platform you are using and enter the code here.", 'sitewide-sales' ) ?></p>
			</td>
		</tr>
		<tr class='swsales-module-row swsales-module-row-custom'>
			<?php
			$confirmation_url = $cur_sale->get_meta_value( 'swsales_custom_confirmation_url', '' );
			?>
			<th><label for="swsales_custom_confirmation_url"><?php esc_html_e( 'Confirmation Page URL', 'sitewide-sales' ); ?></label></th>
			<td>
				<input type="text" class="swsales_option" id="swsales_custom_confirmation_url" name="swsales_custom_confirmation_url" value='<?php echo esc_url( $confirmation_url ); ?>'>
				<p class="description"><?php esc_html_e( "If you would like to track checkout conversions, enter the full URL that your users are sent to after completing checkout.", 'sitewide-sales' ) ?></p>
			</td>
		</tr>
		<tr class='swsales-module-row swsales-module-row-custom'>
			<?php
			$average_order_value = number_format( floatval( $cur_sale->get_meta_value( 'swsales_custom_average_order_value', 0 ) ), 2 );
			?>
			<th><label for="swsales_custom_average_order_value"><?php esc_html_e( 'Average Order Value', 'sitewide-sales' ); ?></label></th>
			<td>
				<input type="number" class="swsales_option" id="swsales_custom_average_order_value" name="swsales_custom_average_order_value" step=0.01 value='<?php esc_html_e( $average_order_value ); ?>'>
				<p class="description"><?php esc_html_e( "If you would like to estimate the revenue generated by your sale, enter the average sale price of each order.", 'sitewide-sales' ) ?></p>
			</td>
		</tr>
		<?php
	} // end swsales_after_choose_sale_type()

	/**
	 * Saves custom module fields when saving Sitewide Sale.
	 *
	 * @param int     $post_id of the sitewide sale being edited.
	 * @param WP_Post $post object of the sitewide sale being edited.
	 */
	public static function swsales_save_metaboxes( $post_id, $post ) {
		if ( isset( $_POST['swsales_custom_coupon'] ) ) {
			update_post_meta( $post_id, 'swsales_custom_coupon', sanitize_text_field( $_POST['swsales_custom_coupon'] ) );
		}
		if ( isset( $_POST['swsales_custom_confirmation_url'] ) ) {
			update_post_meta( $post_id, 'swsales_custom_confirmation_url', sanitize_text_field( $_POST['swsales_custom_confirmation_url'] ) );
		}
		if ( isset( $_POST['swsales_custom_average_order_value'] ) ) {
			update_post_meta( $post_id, 'swsales_custom_average_order_value', floatval( $_POST['swsales_custom_average_order_value'] ) );
		}
	} // end swsales_save_metaboxes()

	/**
	 * Enqueues /modules/ecommerce/custom/swsales-module-custom-metaboxes.js
	 */
	public static function admin_enqueue_scripts() {
		global $wpdb, $typenow;
		if ( 'sitewide_sale' === $typenow ) {
			wp_register_script( 'swsales_module_custom_metaboxes', plugins_url( 'modules/ecommerce/custom/swsales-module-custom-metaboxes.js', SWSALES_BASENAME ), array( 'jquery' ), '1.0.4' );
			wp_enqueue_script( 'swsales_module_custom_metaboxes' );
		}
	} // end admin_enqueue_scripts()

	/**
	 * Get the coupon for a sitewide sale.
	 * Callback for the swsales_coupon filter.
	 */
	public static function swsales_coupon( $coupon, $sitewide_sale ) {
		global $wpdb;
		if ( $sitewide_sale->get_sale_type() === 'custom' ) {
			$coupon = $sitewide_sale->get_meta_value( 'swsales_custom_coupon', '' );
		}
		return $coupon;
	} // end swsales_coupon()

	public static function track_conversions() {
		$sitewide_sale = \Sitewide_Sales\classes\SWSales_Sitewide_Sale::get_active_sitewide_sale();
		if ( empty( $sitewide_sale ) || 'custom' !== $sitewide_sale->get_sale_type() ) {
			return;
		}

		$cookie_name = 'swsale_custom_conversions_' . $sitewide_sale->get_id();
		if ( get_permalink() === $sitewide_sale->get_meta_value( 'swsales_custom_confirmation_url', '' ) && empty( $_COOKIE[ $cookie_name ] ) ) {
			setcookie( $cookie_name, '1', time()+60*60*24*30, COOKIEPATH, COOKIE_DOMAIN, false);
			$prev_conversions = intval( $sitewide_sale->get_meta_value( 'swsales_custom_conversions', 0 ) );
			update_post_meta( $sitewide_sale->get_id(), 'swsales_custom_conversions', $prev_conversions + 1 );
		}
	}

	/**
	 * Set custom module checkout conversions for Sitewide Sale report.
	 *
	 * @param string               $cur_conversions set by filter.
	 * @param SWSales_Sitewide_Sale $sitewide_sale to generate report for.
	 * @return string
	 */
	public static function checkout_conversions( $cur_conversions, $sitewide_sale ) {
		if ( 'custom' !== $sitewide_sale->get_sale_type() ) {
			return $cur_conversions;
		}
		return $sitewide_sale->get_meta_value( 'swsales_custom_conversions', 'N/A' );
	}

	/**
	 * Set custom module total revenue for Sitewide Sale report.
	 *
	 * @param string               $cur_revenue set by filter.
	 * @param SWSales_Sitewide_Sale $sitewide_sale to generate report for.
	 * @param bool                 $format_price whether to run output through format_price().
	 * @return string
	 */
	public static function sale_revenue( $cur_revenue, $sitewide_sale, $format_price = true ) {
		if ( 'custom' !== $sitewide_sale->get_sale_type() ) {
			return $cur_revenue;
		}
		$checkout_conversions = intval( $sitewide_sale->get_meta_value( 'swsales_custom_conversions', 0 ) );
		$average_order_value = floatval( $sitewide_sale->get_meta_value( 'swsales_custom_average_order_value', 0 ) );
		$sale_rev = $checkout_conversions * $average_order_value;
		if ( ! empty( $sale_rev ) ) {
			return $format_price ? self::format_price( $sale_rev ) : $sale_rev;	
		} else {
			return __( 'N/A', 'sitewide-sales' );
		}
	}

	/**
	 * Get full list of currencies and price formats.
	 *
	 * @return array
	 */
	public static function get_currencies() {
		$swsales_currencies = apply_filters(
			'swsales_custom_currencies',
			array(
				'USD' => __('US Dollars (&#36;)', 'sitewide-sales' ),
				'EUR' => array(
					'name' => __('Euros (&euro;)', 'sitewide-sales' ),
					'symbol' => '&euro;',
					'position' => apply_filters( 'swsales_custom_euro_position', self::get_euro_position_from_locale() )
					),
				'GBP' => array(
					'name' => __('Pounds Sterling (&pound;)', 'sitewide-sales' ),
					'symbol' => '&pound;',
					'position' => 'left'
					),
				'ARS' => __('Argentine Peso (&#36;)', 'sitewide-sales' ),
				'AUD' => __('Australian Dollars (&#36;)', 'sitewide-sales' ),
				'BRL' => array(
					'name' => __('Brazilian Real (R&#36;)', 'sitewide-sales' ),
					'symbol' => 'R&#36;',
					'position' => 'left'
					),
				'CAD' => __('Canadian Dollars (&#36;)', 'sitewide-sales' ),
				'CNY' => __('Chinese Yuan', 'sitewide-sales' ),
				'CZK' => array(
					'name' => __('Czech Koruna', 'sitewide-sales' ),
					'decimals' => '2',
					'thousands_separator' => '&nbsp;',
					'decimal_separator' => ',',
					'symbol' => '&nbsp;Kč',
					'position' => 'right',
					),
				'DKK' => array(
					'name' =>__('Danish Krone', 'sitewide-sales' ),
					'decimals' => '2',
					'thousands_separator' => '&nbsp;',
					'decimal_separator' => ',',
					'symbol' => 'DKK&nbsp;',
					'position' => 'left',
					),
				'GHS' => array(
					'name' => __('Ghanaian Cedi (&#8373;)', 'sitewide-sales' ),
					'symbol' => '&#8373;',
					'position' => 'left',
					),
				'HKD' => __('Hong Kong Dollar (&#36;)', 'sitewide-sales' ),
				'HUF' => __('Hungarian Forint', 'sitewide-sales' ),
				'INR' => __('Indian Rupee', 'sitewide-sales' ),
				'IDR' => __('Indonesia Rupiah', 'sitewide-sales' ),
				'ILS' => __('Israeli Shekel', 'sitewide-sales' ),
				'JPY' => array(
					'name' => __('Japanese Yen (&yen;)', 'sitewide-sales' ),
					'symbol' => '&yen;',
					'position' => 'left',
					'decimals' => 0,
					),
				'KES' => __('Kenyan Shilling', 'sitewide-sales' ),
				'MYR' => __('Malaysian Ringgits', 'sitewide-sales' ),
				'MXN' => __('Mexican Peso (&#36;)', 'sitewide-sales' ),
				'NGN' => __('Nigerian Naira (&#8358;)', 'sitewide-sales' ),
				'NZD' => __('New Zealand Dollar (&#36;)', 'sitewide-sales' ),
				'NOK' => __('Norwegian Krone', 'sitewide-sales' ),
				'PHP' => __('Philippine Pesos', 'sitewide-sales' ),
				'PLN' => __('Polish Zloty', 'sitewide-sales' ),
				'RON' => array(
						'name' => __( 'Romanian Leu', 'sitewide-sales' ),
						'decimals' => '2',
						'thousands_separator' => '.',
						'decimal_separator' => ',',
						'symbol' => '&nbsp;Lei',
						'position' => 'right'
				),
				'RUB' => array(
					'name' => __('Russian Ruble (&#8381;)', 'sitewide-sales'),
					'decimals' => '2',
					'thousands_separator' => '&nbsp;',
					'decimal_separator' => ',',
					'symbol' => '&#8381;',
					'position' => 'right'
				),
				'SGD' => array(
					'name' => __('Singapore Dollar (&#36;)', 'sitewide-sales' ),
					'symbol' => '&#36;',
					'position' => 'right'
					),
				'ZAR' => array(
					'name' => __('South African Rand (R)', 'sitewide-sales' ),
					'symbol' => 'R ',
					'position' => 'left'
				),
				'KRW' => array(
					'name' => __('South Korean Won', 'sitewide-sales' ),
					'decimals' => 0,
					),
				'SEK' => __('Swedish Krona', 'sitewide-sales' ),
				'CHF' => __('Swiss Franc', 'sitewide-sales' ),
				'TWD' => __('Taiwan New Dollars', 'sitewide-sales' ),
				'THB' => __('Thai Baht', 'sitewide-sales' ),
				'TRY' => __('Turkish Lira', 'sitewide-sales' ),
				'UAH' => array(
					'name' => __('Ukrainian Hryvnia (&#8372;)', 'sitewide-sales' ),
					'decimals' => 0,
					'thousands_separator' => '',
					'decimal_separator' => ',',
					'symbol' => '&#8372;',
					'position' => 'right'
					),
				'VND' => array(
					'name' => __('Vietnamese Dong', 'sitewide-sales' ),
					'decimals' => 0,
					),
				)
			);
		return $swsales_currencies;
	}

	/**
	 * Get custom module currency for Sitewide Sale reports.
	 *
	 * @return array
	 */
	public static function get_currency( $currency = null ) {
		// Get the default currency for this site.
		$currency = apply_filters( 'swsales_custom_currency', 'USD' );

		// Get all currencies.
		$currencies = self::get_currencies();

		// Defaults.
		$currency_array = array(
			'name' =>__('US Dollars (&#36;)', 'paid-memberships-pro' ),
			'decimals' => '2',
			'thousands_separator' => ',',
			'decimal_separator' => '.',
			'symbol' => '&#36;',
			'position' => 'left',
		);

		if ( ! empty( $currency ) ) {
			if ( is_array( $currencies[$currency] ) ) {
				$currency_array = array_merge( $currency_array, $currencies[$currency] );
			} else {
				$currency_array['name'] = $currencies[$currency];
			}
		}

		return $currency_array;
	}

	/**
	 * Get the Euro position based on locale.
	 * English uses left, others use right.
	 */
	public static function get_euro_position_from_locale( $position = 'right' ) {
		$locale = get_locale();
		if ( strpos( $locale, 'en_' ) === 0 ) {
			$position = 'left';
		}
		return $position;
	}

	/**
	 * Format a price per the currency settings.
	 *
	 * @return string
	 */
	public static function format_price( $price ) {
		$currency_array = self::get_currency();

		$currency_symbol = isset( $currency_array['symbol'] ) ? $currency_array['symbol'] : '&#36;';
		$decimals = isset( $currency_array['decimals'] ) ? (int) $currency_array['decimals'] : '2';
		$decimal_separator = isset( $currency_array['decimal_separator'] ) ? $currency_array['decimal_separator'] : '.';
		$thousands_separator = isset( $currency_array['thousands_separator'] ) ? $currency_array['thousands_separator'] : ',';
		$symbol_position = isset( $currency_array['position'] ) ? $currency_array['position'] : 'left';

		// Settings stored in array?
		if ( ! empty( $currency_array ) && is_array( $currency_array ) ) {
			// Format number, do decimals, with decimal_separator and thousands_separator
			$formatted = number_format(
				$price,
				$decimals,
				$decimal_separator,
				$thousands_separator
			);

			// Which side is the symbol on?
			if ( ! empty( $symbol_position ) && $symbol_position == 'left' ) {
				$formatted = $currency_symbol . $formatted;
			} else {
				$formatted = $formatted . $currency_symbol;
			}
		} else {
			// Default to symbol on the left, 2 decimals using . and ,
			$formatted = $currency_symbol . number_format( $formatted, $decimals );
		}

		// Return the formatted price.
		return $formatted;
	}
}
SWSales_Module_Custom::init();