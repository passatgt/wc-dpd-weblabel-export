<?php
/*
Plugin Name: WooCommerce DPD Weblabel Export
Plugin URI: http://visztpeter.me
Description: Rendelésinfó exportálása DPD Weblabel importáláshoz
Author: Viszt Péter
Version: 2.0.1
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_DPD_Weblabel {

	public static $plugin_prefix;
	public static $plugin_url;
	public static $plugin_path;
	public static $plugin_basename;
	public static $version;

	//Construct
	public function __construct() {

		//Default variables
		self::$plugin_prefix = 'wc_dpd_weblabel_';
		self::$plugin_basename = plugin_basename(__FILE__);
		self::$plugin_url = plugin_dir_url(self::$plugin_basename);
		self::$plugin_path = trailingslashit(dirname(__FILE__));
		self::$version = '2.0.1';

		add_action( 'admin_init', array( $this, 'wc_dpd_weblabel_admin_init' ) );
		add_filter( 'woocommerce_shipping_settings', array( $this, 'settings' ) );
		add_action( 'woocommerce_admin_order_actions_end', array( $this, 'order_row_button' ),10,1);
		add_action( 'admin_init',array( $this, 'generate_csv' ));
 		add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts' ) );
 		add_action( 'woocommerce_order_actions_start', array( $this, 'single_order_button' ) );

  }

	//Add CSS & JS
	public function wc_dpd_weblabel_admin_init() {
		wp_enqueue_style( 'wc_dpd_css', plugins_url( '/global.css',__FILE__ ) );
		wp_enqueue_script( 'wc_dpd_js', plugins_url( '/global.js',__FILE__ ) );
	}

	//Settings
	public function settings( $settings ) {
		$updated_settings = array();
		foreach ( $settings as $section ) {
			if ( isset( $section['id'] ) && 'shipping_options' == $section['id'] && isset( $section['type'] ) && 'sectionend' == $section['type'] ) {
				$shipping_methods = array();
				global $woocommerce;

				$payment_methods[''] = __( 'Válassz egy fizetési módot', 'wc_dpd_weblabel' );
				foreach ( WC()->payment_gateways->payment_gateways() as $gateway ) {
					if($gateway->enabled == 'yes') $payment_methods[$gateway->id] = $gateway->get_title();
				}

				$updated_settings[] = array(
					'name'     => __( 'DPD - Utánvételes Fizetési Mód', 'wc_dpd_weblabel' ),
					'id'       => 'wc_dpd_cash_payment_gateway',
					'type' 	   => 'select',
					'css' 	   => 'min-width:150px;',
					'options'  => $payment_methods,
					'class'	   => 'chosen_select',
					'desc'     => __( 'Válaszd ki az utánvételes fizetési módot, hogy a DPD export fájlban tudjuk, melyik rendelés utánvételes.', 'wc_dpd_weblabel' ),
				);
			}
			$updated_settings[] = $section;
		}
		return $updated_settings;
	}

	//Order list button
	public function order_row_button($order) {
		$order_id = $order->get_id();
		?>
		<a href="<?php echo admin_url( "?download_dpd_csv=1&order_id=$order_id" ); ?>" class="button tips dpd-small-button" target="_blank" alt="" data-tip="<?php _e('DPD Weblabel Export','wc-szamlazz'); ?>">
			<img src="<?php echo WC_DPD_Weblabel::$plugin_url . 'dpd-logo.svg'; ?>" alt="" width="16" height="20">
		</a>
		<?php
	}

	//Single order button
	public function single_order_button($order_id) {
		?>
		<li class="wide dpd-big-single-button">
			<label><?php _e('DPD Weblabel','wc-szamlazz'); ?></label> <a href="<?php echo admin_url( "?download_dpd_csv=1&order_id=$order_id" ); ?>" class="button" target="_blank" alt="" data-tip="<?php _e('DPD Weblabel Export','wc-szamlazz'); ?>"><?php _e('Letöltés','wc-szamlazz'); ?></a>
		</li>
		<?php
	}

	//Global button
	public function restrict_manage_posts() {
		global $typenow, $wp_query;

		if ( $typenow == 'shop_order' ) {
			?>
			<a href="<?php echo admin_url( "?download_dpd_csv=1&order_id=" ); ?>" data-baseurl="<?php echo admin_url( "?download_dpd_csv=1&order_id=" ); ?>" class="button dpd-big-button" id="dpd-export-button" target="_blank" alt="" data-tip="<?php _e('DPD Weblabel Export','wc-szamlazz'); ?>"><?php _e('DPD Weblabel Export','wc-szamlazz'); ?><small></small></a>
			<?php
		}
	}

	//Generate CSV file for DPD
	public function generate_csv() {

		if (!empty($_GET['download_dpd_csv'])) {
			if ( !current_user_can( 'administrator' ) )  {
				wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
			}

			//Set file name
			if(isset($_GET['order_id']) && $_GET['order_id'] != '') {
				$order_id = sanitize_text_field($_GET['order_id']);
				$filename = 'dpd'.date('-Y-m-d-H:i').'-'.$order_id.'.csv';
			} else {
				$filename = 'dpd'.date('-Y-m-d-H:i').'.csv';
			}

			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Cache-Control: private", false);
			header("Content-Type: application/octet-stream");
			header("Content-Disposition: attachment; filename=\"$filename\";" );
			header("Content-Transfer-Encoding: binary");

			$args = array(
				'post_type' => 'shop_order',
				'post_status' => 'any',
				'posts_per_page' => '-1'
			);

			if(isset($_GET['order_id']) && $_GET['order_id'] != '') {
				$orderids = array_map('intval', explode('|', $_GET['order_id']));
			} else {

				//Get all processing orders
				$orderids = wc_get_orders( array(
					'status' => 'processing',
					'return' => 'ids',
				));

			}

			//Convert WooCommerce country codes to DPD versions
			$dpd_country_codes = array('AT' => 'A', 'BA' => 'BIH', 'DE' => 'D', 'ES' => 'E', 'FI' => 'FIN', 'FR' => 'F', 'HU' => 'H', 'IE' => 'IRL', 'IT' => 'I', 'LU' => 'L', 'NO' => 'N', 'PT' => 'P', 'RU' => 'GUS', 'SE' => 'S', 'SI' => 'SLO');

			//Check the cod payment method
			$cash_payment_method = get_option( 'wc_dpd_cash_payment_gateway', 'none' );

			//Generate output
			$output = fopen("php://output", "w");

			foreach ($orderids as $order_id) {
				$order = wc_get_order($order_id);

				//If COD
				$is_cod = ($order->get_payment_method() == $cash_payment_method);

				//Set default type
				$order_type = ($is_cod) ? 'D-COD' : 'D';

				//Súly
				$weight = 0;
				foreach( $order->get_items() as $order_item ) {
					$product = $order_item->get_product();
					if($product && $product->get_weight()) {
						$weight += $order_item->get_quantity()*$product->get_weight();
					}
				}

				/*
				A oszlop: Példák: „D” / „D-DOCRET” / „D-COD” / „D-CODEX” / „D-COD-DOCRET” / - Normál / Normál Szállítólevél viszaforgatással /Utánvétes csomag / Utánvételes csomag express / Utánvétes csomag szállítólevél visszaforgatással , minden esetben ennek kell lennie, ezekkel kell kezdődnie - kötelező / kötelező
				B oszlop: 0.6 - Csomag súlya: elhagyható parcel weight: optional
				C oszlop: "" / 111 - Normál csomag esetén nincs értelmezve / COD érték - elhagyható / kötelező COD value, used only for COD parcels
				D oszlop: ”” / szla:111 - Normál csomag esetén nincs értelmezve / Beszedés indítéka (pl.: számlaszám) - elhagyható / kötelező COD reference optional
				E oszlop: Referencia - Önök referencia (azonosító) száma, például számlaszám - elhagyható Parcel reference, optional
				F oszlop: Címtörzs id - Megrendelő címnyilvántartási száma - elhagyható Address reference: optional G oszlop: Minta cegnév – címzett neve (vagy magánszemélynél a magánszemély neve) – Kötelező Address name Mandatory
				H oszlop: Minta nev 2 - Céges környezetben a címzett neve . elhagyható Address name2 mandatory
				I oszlop: Minta utca 1 - A cél címe - kötelező Address1: mandatory
				J oszlop: Minta utca 2 - A cél címe - elhagyható Address2: mandatory
				K oszlop: H - Ország betűkód – Kötelező Country code: mandatory
				L oszlop: 1158 - Irányítószám – Kötelező Postal code: mandatory
				M oszlop: Budapest - Város – Kötelező City: mandatory
				N oszlop: 1234567 - Telefonszám (futár tudja értesíteni a címzettet) - elhagyható Phone number: optional
				O oszlop: 7654321 - Fax szám – elhagyható Fax number: optional
				P oszlop: valaki@valahol.hu - E-mail cím, ennek megléte esetén a csomag címzettje e-mailt kap a csomag felvétele (a mi telephelyünkön!) után – elhagyható Email address: optional
				Q oszlop: Megjegyzés a céghez tárolható megjegyzés – elhagyható Remark for delivery:optional
				R oszlop: IDM SMS telefonszám - elhagyható IDM SMS Number: optional
				*/

				$csv_row = array();
				$csv_row[0] = $order_type; //Csomag típusa, D normál, D-COD utánvétes
				$csv_row[1] = $weight; //Súly
				$csv_row[2] = ''; //Utánvét összeg
					if($is_cod) { $csv_row[2] = $order->get_total(); }
				$csv_row[3] = ''; //Utánvét ref.
					if($is_cod) { $csv_row[3] = $order->get_order_number(); }
				$csv_row[4] = $order->get_order_number(); //Referencia szám(rendelés szám)
				$csv_row[5] = ''; //Címtörzs ID(???)

				//Ha cégnév van, az az elsődleges, különben csak a nevet küldjük
				if($order->get_shipping_company()) {
					$csv_row[6] = $order->get_shipping_company();
					$csv_row[7] = $order->get_formatted_shipping_full_name(); //Név
				} else {
					$csv_row[6] = $order->get_formatted_shipping_full_name();
					$csv_row[7] = ''; //Név
				}
				$csv_row[8] = $order->get_shipping_address_1(); //Cím 1
				$csv_row[9] = $order->get_shipping_address_2(); //Cím 2
				if(array_key_exists($order->get_shipping_country(), $dpd_country_codes)) {
					$csv_row[10] = $dpd_country_codes[$order->get_shipping_country()]; //Országkód
				} else {
					$csv_row[10] = $order->get_shipping_country(); //Országkód
				}
				$csv_row[11] = $order->get_shipping_postcode(); //Irányítószám
				$csv_row[12] = $order->get_shipping_city(); //Város
				$csv_row[13] = $order->get_billing_phone(); //Telefon
				$csv_row[14] = ''; //Fax
				$csv_row[15] = $order->get_billing_email(); //Email
				$csv_row[16] = ''; //Céggel kapcsolatos megjegyzés

				$csv_row = apply_filters('wc_dpd_weblabel_item', $csv_row, $order_id);

				fputcsv($output, $csv_row, ';');

			};
			fclose($output);

			exit();
		}

	}
}

$wc_dpd_weblabel = new WC_DPD_Weblabel();

?>
