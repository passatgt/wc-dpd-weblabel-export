<?php 
/*
Plugin Name: WooCommerce DPD Weblabel Export
Plugin URI: http://visztpeter.me
Description: Rendelésinfó exportálása DPD Weblabel importáláshoz
Author: Viszt Péter
Version: 1.0.1
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
		self::$version = '1.0.1'; 

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
		?>
		<a href="<?php echo admin_url( "?download_dpd_csv=1&order_id=$order->id" ); ?>" class="button tips dpd-small-button" target="_blank" alt="" data-tip="<?php _e('DPD Weblabel Export','wc-szamlazz'); ?>">
			<img src="<?php echo WC_DPD_Weblabel::$plugin_url . 'dpd.png'; ?>" alt="" width="16" height="16">
		</a>
		<?php
	}
	
	//Single order button
	public function single_order_button($order) {
		?>
		<li class="wide dpd-big-single-button">
			<label><?php _e('DPD Weblabel','wc-szamlazz'); ?></label> <a href="<?php echo admin_url( "?download_dpd_csv=1&order_id=$order->id" ); ?>" class="button" target="_blank" alt="" data-tip="<?php _e('DPD Weblabel Export','wc-szamlazz'); ?>"><?php _e('Letöltés','wc-szamlazz'); ?></a>
		</li>
		<?php		
	}
	
	//Global button
	public function restrict_manage_posts() {
		global $typenow, $wp_query;

		if ( $typenow == 'shop_order' ) {
			?>
			<a href="<?php echo admin_url( "?download_dpd_csv=1&order_id=$order->id" ); ?>" class="button dpd-big-button" target="_blank" alt="" data-tip="<?php _e('DPD Weblabel Export','wc-szamlazz'); ?>"><?php _e('DPD Weblabel Export','wc-szamlazz'); ?></a>
			<?php
		}
	}
	
	//Generate CSV file for DPD
	public function generate_csv() {

		if (!empty($_GET['download_dpd_csv'])) {
			if ( !current_user_can( 'edit_shop_order' ) )  {
				wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
			}
			
			$filename = 'dpd'.date('-Y-m-d-H:i').'.csv';
			
			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Cache-Control: private", false);
			header("Content-Type: application/octet-stream");
			header("Content-Disposition: attachment; filename=\"$filename\";" );
			header("Content-Transfer-Encoding: binary");
			
			$args = array(
				'post_type' => 'shop_order',
				'post_status' => 'publish',
				'posts_per_page' => '-1'
			);
			
			if(isset($_GET['order_id'])) {
				if($_GET['order_id'] != '') {
					$args['post__in'] = array($_GET['order_id']);
				}
			}
			
			//2.2 előtt taxonomy volt a rendelés státusz
			global $woocommerce;
			if($woocommerce->version<2.2) {
				$args['tax_query'] = array(
					array(
						'taxonomy' => 'shop_order_status',
						'field' => 'slug',
						'terms' => array('processing')
					)
				);
			} else {
				$args['post_status'] =  array( 'wc-processing');
			}
			
			$orders = new WP_Query($args);
			
			$output = fopen("php://output", "w");
			while ( $orders->have_posts() ) : $orders->the_post();
				global $post;
				$order_id = get_the_ID();
				$order = new WC_Order($order_id);
				
				$order_type = 'D';
				
				$cash_payment_method = get_option( 'wc_dpd_cash_payment_gateway', 'none' );
				
				//Ha utánvétes payu fizetés
				if($order->payment_method == $cash_payment_method) {
					$order_type = 'D-COD';
				}
	
				//Súly
				$weight = 0;
				foreach( $order->get_items() as $item ) {
					$_product = new WC_Product($item['product_id']);
					$weight += $item['qty']*$_product->get_weight();
				}
							
				$csv_row = array();
				$csv_row[0] = $order_type; //Csomag típusa, D normál, D-COD utánvétes
				$csv_row[1] = $weight; //Súly
				$csv_row[2] = ''; //Utánvét összeg
					if($order->payment_method == $cash_payment_method) { $csv_row[2] = $order->order_total; }
				$csv_row[3] = ''; //Utánvét ref.
					if($order->payment_method == $cash_payment_method) { $csv_row[3] = '#'.$order_id; }
				$csv_row[4] = '#'.$order_id; //Referencia szám(rendelés szám)
				$csv_row[5] = ''; //Címtörzs ID(???)
				
				//Ha cégnév van, az az elsődleges, különben csak a nevet küldjük
				if($order->shipping_company) {
					$csv_row[6] = $order->shipping_company;
					$csv_row[7] = $order->shipping_first_name.' '.$order->shipping_last_name; //Név
				} else {
					$csv_row[6] = $order->shipping_first_name.' '.$order->shipping_last_name;
					$csv_row[7] = ''; //Név
				}		
				$csv_row[8] = $order->shipping_address_1; //Cím 1
				$csv_row[9] = $order->shipping_address_2; //Cím 2
				$csv_row[10] = 'H'; //Országkód
				$csv_row[11] = $order->shipping_postcode; //Irányítószám
				$csv_row[12] = $order->shipping_city; //Város
				$csv_row[13] = $order->billing_phone; //Telefon
				$csv_row[14] = ''; //Fax
				$csv_row[15] = $order->billing_email; //Email
				$csv_row[16] = $post->post_excerpt; //Megjegyzés
				
				fputcsv($output, $csv_row, ';');
				
			endwhile;
			fclose($output);
			
			exit();
		}
	

	}		
}

$GLOBALS['wc_dpd_weblabel'] = new WC_DPD_Weblabel();

?>