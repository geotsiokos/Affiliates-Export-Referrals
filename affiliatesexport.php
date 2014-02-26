<?php
/**
 * affiliatesexport.php
 *
 * Copyright (c) 2011,2012 Antonio Blanco http://www.eggemplo.com
 *
 * This code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt.
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This header and all notices must be kept intact.
 *
 * @author Antonio Blanco	
 * @package affiliatesexport
 * @since affiliatesexport 1.0.0
 *
 * Plugin Name: Affiliates Export
 * Plugin URI: http://www.itthinx.com
 * Description: Affiliates data exporter
 * Version: 1.0
 * Author: itthinx
 * Author URI: http://www.itthinx.com
 * License: GPLv3
 */

define( 'AFFILIATESEXPORT_DOMAIN', 'affiliatesexport' );

define( 'AFFILIATESEXPORT_FILE', __FILE__ );

define( 'AFFILIATESEXPORT_PLUGIN_URL', plugin_dir_url( AFFILIATESEXPORT_FILE ) );

include_once 'class-affiliatesexport.php';


class AffiliatesExport_Plugin {
	
	private static $notices = array();
	
	public static function init() {
			
		load_plugin_textdomain( AFFILIATESEXPORT_DOMAIN, null, 'affiliatesexport/languages' );
		
		register_activation_hook( AFFILIATESEXPORT_FILE, array( __CLASS__, 'activate' ) );
		register_deactivation_hook( AFFILIATESEXPORT_FILE, array( __CLASS__, 'deactivate' ) );
		
		add_action( 'init', array( __CLASS__, 'wp_init' ) );
		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
	}
	
	public static function wp_init() {
		if ( !defined ( 'AFFILIATES_PLUGIN_DOMAIN' ) )  {
			self::$notices[] = "<div class='error'>" . __( '<strong>AffiliatesExport</strong> plugin requires <a href="http://www.itthinx.com/plugins/affiliates" target="_blank">Affiliates</a> or <a href="http://www.itthinx.com/plugins/affiliates-pro" target="_blank">Affiliates Pro</a> or <a href="http://www.itthinx.com/plugins/affiliates-enterprise" target="_blank">Affiliates Enterprise</a>.', AFFILIATESEXPORT_DOMAIN ) . "</div>";
		} else {
			
				
			add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 40 );
					
			//call register settings function
			add_action( 'admin_init', array( __CLASS__, 'register_affiliatesexport_settings' ) );
		}
		
	}
	
	/**
	 * Register settings as groups-mailchimp-settings
	 */
	public static function register_affiliatesexport_settings() {
		// load datepicker scripts for all
		wp_enqueue_script( 'datepicker', AFFILIATESEXPORT_PLUGIN_URL . 'js/jquery.ui.datepicker.min.js', array( 'jquery', 'jquery-ui-core' ));
		wp_enqueue_script( 'datepickers', AFFILIATESEXPORT_PLUGIN_URL . 'js/datepickers.js', array( 'jquery', 'jquery-ui-core', 'datepicker' ));
		
		wp_register_style( 'smoothness', AFFILIATESEXPORT_PLUGIN_URL . 'css/smoothness/jquery-ui-1.8.16.custom.css', array());
		
		//register our settings
		register_setting( 'affiliatesexport-settings', 'affexp_status' );
		
	}
	
	public static function admin_notices() { 
		if ( !empty( self::$notices ) ) {
			foreach ( self::$notices as $notice ) {
				echo $notice;
			}
		}
	}
	
	/**
	 * Adds the admin section.
	 */
	public static function admin_menu() {
		$admin_page = add_submenu_page(
				'affiliates-admin',				
				__( 'Affiliates export' , AFFILIATESEXPORT_DOMAIN),
				__( 'Affiliates export' , AFFILIATESEXPORT_DOMAIN),
				'manage_options',
				'affiliatesexport',
				array( __CLASS__, 'affiliatesexport' )
		);
		add_action( 'admin_print_styles-' . $admin_page, array( __CLASS__,'admin_print_styles') );
		
		
	}
	
	function admin_print_styles() {
		wp_enqueue_style( 'smoothness' );
	}
	
	/**
	 * Show Groups MailChimp setting page.
	 */
	public static function affiliatesexport () {
	?>
	<div class="wrap">
	<h2><?php echo __( 'Affiliates Export', AFFILIATESEXPORT_DOMAIN ); ?></h2>
	<?php 
	if ( isset( $_POST['submit'] ) ) {
	
		add_option( 'affexp_status', $_POST['affexp_status'] );
		update_option( 'affexp_status', $_POST['affexp_status'] );
		
		add_option( 'affexp_from_date', $_POST['from_date'] );
		update_option( 'affexp_from_date', $_POST['from_date'] );
		
		add_option( 'affexp_thru_date', $_POST['thru_date'] );
		update_option( 'affexp_thru_date', $_POST['thru_date'] );

		
				
	} elseif ( isset( $_POST['generate'] ) ) {
	
	
		AffiliatesExport::generate(
			get_option( 'blog_charset' ) 
		);
	
	} 
	
	?>
	<form method="post" action="">
	    <table class="form-table">
	        <tr valign="top">
	        <th scope="row">
	        	<label class="referral-status-filter" for="referral_status"><?php echo __('Referral Status', AFFILIATES_PLUGIN_DOMAIN );?></label> 
			</th>
	        <td>
	        <?php 
	        	$status_old = get_option('affexp_status', null);
	        	$status = array(
	        			AFFILIATES_REFERRAL_STATUS_ACCEPTED => __( 'Accepted', AFFILIATES_PLUGIN_DOMAIN ),
	        			AFFILIATES_REFERRAL_STATUS_CLOSED => __( 'Closed', AFFILIATES_PLUGIN_DOMAIN ),
	        			AFFILIATES_REFERRAL_STATUS_PENDING => __( 'Pending', AFFILIATES_PLUGIN_DOMAIN ),
	        			AFFILIATES_REFERRAL_STATUS_REJECTED => __( 'Rejected', AFFILIATES_PLUGIN_DOMAIN ), );
	        	
	        	$output = '<select class="referral-status-filter" name="affexp_status">'; 
				$output .= '<option value="-" ' . ( empty( $status_old ) ? ' selected="selected" ' : '' ) . '>All</option>'; 
				foreach ( $status as $key => $value ) { 
					$selected = $key == $status_old ? ' selected="selected" ' : ''; 
					$output .= '<option ' . $selected . ' value="' . esc_attr( $key ) . '">' . $value . '</option>'; 
				} 
				$output .= '</select>';
				echo $output; 
				?>
	        </td>
	        </tr>
	    </table>
	    <p>
	    <?php 
		    $from_date = get_option('affexp_from_date', "");
		    $thru_date = get_option('affexp_thru_date', "");
		     
	    	$output = '<label class="from-date-filter" for="from_date">' . __( 'From', AFFILIATESEXPORT_DOMAIN ) . '</label>' .
				'<input class="datefield from-date-filter" name="from_date" type="text" value="' . esc_attr( $from_date ) . '"/>'.
				'<label class="thru-date-filter" for="thru_date">' . __( 'Until', AFFILIATESEXPORT_DOMAIN ) . '</label>' .
				'<input class="datefield thru-date-filter" name="thru_date" type="text" value="' . esc_attr( $thru_date ) . '"/>';
	    	echo $output;
		?>	
	    </p>
	    
	    <?php 
	    submit_button(); 
	    
	    settings_fields( 'affiliatesexport-settings' ); ?>
	    
	</form>
	<span class="description">File structure: billing_firstname | billing_lastname | amount | country_code | billing_address_1 | billing_address_2</span>
	</br>
	<span class="description">Fields separated by tabulator. Affiliates separated by enter.</span>
	</br>
    <?php 
    $url = esc_url( AFFILIATESEXPORT_PLUGIN_URL . 'core/generate-file.php' );
    ?>
    <a href="<?php echo $url;?>?action=generate_file" target="_blank" class="button"><?php echo __("Generate file", AFFILIATESEXPORT_DOMAIN);?></a>
    	
	</div>
	<?php 
	}
	
	
	/**
	 * Plugin activation work.
	 * 
	 */
	public static function activate() {
			
	}
	
	/**
	 * Plugin deactivation work. Delete database table.
	 * 
	 */
	public static function deactivate() {
		
	}
	
	
}
AffiliatesExport_Plugin::init();

