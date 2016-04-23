<?php
/**
 * class-affiliatesexport.php
 *
 * Copyright (c) Antonio Blanco http://www.blancoleon.com
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
 */

/**
 * Groups Mailchimp class
 */
class AffiliatesExport {

	public static function init() {
	
	}
	
	public static function generate($charset = null ) {
		global $affiliates_db;
		
		header( 'Content-Description: File Transfer' );
		if ( !empty( $charset ) ) {
			header( 'Content-Type: text/plain; charset=' . $charset );
		} else {
			header( 'Content-Type: text/plain' );
		}
		$hora = date( 'Y-m-d-H-i-s', time() );
		
		header( "Content-Disposition: attachment; filename=\"affiliates-export-$hora.txt\"" );

		$from_date = get_option("affexp_from_date", null);
		$thru_date = get_option("affexp_thru_date", null);
		
		$aff_table = $affiliates_db->get_tablename( 'affiliates' );
		$ref_table = $affiliates_db->get_tablename( 'referrals' );
		$hits_table = $affiliates_db->get_tablename( 'hits' );
		$status = "active";
		
		$affiliates = $affiliates_db->get_objects( "SELECT * FROM " . $aff_table . " WHERE status='" . $status . "'");
		
		if ( count( $affiliates ) ) {
			$output = "";
			$sep = "\t";
			foreach ( $affiliates as $affiliate ) {
				
				if ( $user_id = affiliates_get_affiliate_user( $affiliate->affiliate_id ) ) {
					// User Data 
					if ( get_userdata ( $user_id ) ) {
						$user_info = get_userdata( $user_id );
					}
					
					$firstname = get_user_meta( $user_id, "billing_first_name", true );
					if ( $firstname == "" ) {
						$firstname = $user_info->first_name;
					}
					
					$lastname = get_user_meta( $user_id, "billing_last_name", true );
					if ( $lastname == "" ) {
						$lastname = $user_info->last_name;
					}					
					
					// Referrals
					$referrals = self::get_affiliate_referrals( $affiliate->affiliate_id, $from_date, $thru_date);
					
					$amount = 0;
					if ( count( $referrals ) != 0 ) {
						foreach ( $referrals as $referral ) {
							if ( $referral->amount > 0.00000 ) {
								
								$amount = $referral->amount;
								$currency = $referral->currency_id;
								
								// order data
								if ( $referral->type == "sale" ) {
									$order_id = $referral->post_id;
									$order = new WC_Order( $order_id );
									$customer_firstname = $order->billing_first_name;
									$customer_lastname = $order->billing_last_name;
									if ( sizeof( $order->get_items() ) > 0 ) {
										foreach( $order->get_items() as $item ) {
											$product_list[] = $item['name'];
										}
										$product_names = implode( ',', $product_list );
										
									} 
								}
								$product_list = array();
								
								$output .= "Referrals for affiliate id " . $affiliate->affiliate_id . "\n" ;
								$output .= $firstname . $sep . $lastname . $sep . $amount;
								$output .= $sep . $currency;
								$output .= $sep . $customer_firstname . $sep . $customer_lastname;
								$output .= $sep . $product_names;
								$output .= "\n" . "\n";
							}
						}						
					} else {
						echo __( "There are no referrals recorded yet", AFFILIATESEXPORT_DOMAIN );
					}				
				}
			}
			echo $output;
		} else {
			echo __( "There are no affiliates available", AFFILIATESEXPORT_DOMAIN );
		}
		
	}	
	
	
	/**
	 * Returns referrals for a given affiliate.
	 *
	 * @param int $affiliate_id the affiliate's id
	 * @param string $from_date optional from date
	 * @param string $thru_date optional thru date
	 * @return int number of hits
	 */
	public static function get_affiliate_referrals( $affiliate_id, $from_date = null , $thru_date = null, $order_by = 'datetime', $order = 'DESC', $limit = null ) {
		global $wpdb;
		$referrals_table = _affiliates_get_tablename( 'referrals' );
		$where = " WHERE affiliate_id = %d";
		$values = array( $affiliate_id );
	
	
		switch( $order_by ) {
			case 'date' :
				$order_by = 'datetime';
				break;
			case 'amount' :
				break;
			default :
				$order_by = 'datetime';
		}
		$order = strtoupper( $order );
		switch( $order ) {
			case 'ASC' :
			case 'DESC' :
				break;
			default :
				$order = 'DESC';
		}
		$order_query = ' ORDER BY ' . $order_by . ' ' . $order;
	
		$limit_query = '';
		if ( $limit !== null ) {
			$limit = intval( $limit );
			if ( $limit > 0 ) {
				$limit_query = ' LIMIT ' . $limit;
			}
		}
	
		if ( $from_date ) {
			$from_date = date( 'Y-m-d', strtotime( $from_date ) );
		}
		if ( $thru_date ) {
			$thru_date = date( 'Y-m-d', strtotime( $thru_date ) + 24*3600 );
		}
		if ( $from_date && $thru_date ) {
			$where .= " AND datetime >= %s AND datetime < %s ";
			$values[] = $from_date;
			$values[] = $thru_date;
		} else if ( $from_date ) {
			$where .= " AND datetime >= %s ";
			$values[] = $from_date;
		} else if ( $thru_date ) {
			$where .= " AND datetime < %s ";
			$values[] = $thru_date;
		}
		
		$status = get_option("affexp_status", "-");
		
		if ( $status != "-" ) {
			$where .= " AND status = %s ";
			$values[] = $status;
		} //else {
		//	$where .= " AND status IN ( %s, %s ) ";
		//	$values[] = AFFILIATES_REFERRAL_STATUS_ACCEPTED;
		//	$values[] = AFFILIATES_REFERRAL_STATUS_CLOSED;
		//}
		
		return $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM $referrals_table $where $order_query $limit_query",
				$values
		) );
	}
	
	public static function get_affiliate_hits( $affiliate_id, $from_date = null , $thru_date = null, $status = null, $order_by = 'datetime', $order = 'DESC', $limit = null ) {
		global $wpdb;
		$hits_table = _affiliates_get_tablename( 'hits' );
		$where = " WHERE affiliate_id = " . $affiliate_id;
		$values = array();
		
		if ( $from_date ) {
			$from_date = date( 'Y-m-d', strtotime( $from_date ) );
		}
		if ( $thru_date ) {
			$thru_date = date( 'Y-m-d', strtotime( $thru_date ) + 24*3600 );
		}
		if ( $from_date && $thru_date ) {
			$where .= " AND datetime >= %s AND datetime < %s ";
			$values[] = $from_date;
			$values[] = $thru_date;
		} else if ( $from_date ) {
			$where .= " AND datetime >= %s ";
			$values[] = $from_date;
		} else if ( $thru_date ) {
			$where .= " AND datetime < %s ";
			$values[] = $thru_date;
		}
		
		//return $wpdb->get_results( "SELECT COUNT(*) as total FROM $hits_table $where");
		return $wpdb->get_results( $wpdb->prepare(
				"SELECT COUNT(*) as total FROM $hits_table $where",
				$values
		) );
	}
	
	/**
	 * Adjust from und until dates from UTZ to STZ and take into account the
	 * for option which will adjust the from date to that of the current
	 * day, the start of the week or the month, leaving the until date
	 * set to null.
	 *
	 * @param string $for "day", "week" or "month"
	 * @param string $from date/datetime
	 * @param string $until date/datetime
	 */
	private static function for_from_until( $for, &$from, &$until ) {
		include_once( AFFILIATES_CORE_LIB . '/class-affiliates-date-helper.php');
		if ( $for === null ) {
			if ( $from !== null ) {
				$from = date( 'Y-m-d H:i:s', strtotime( DateHelper::u2s( $from ) ) );
			}
			if ( $until !== null ) {
				$until = date( 'Y-m-d H:i:s', strtotime( DateHelper::u2s( $until ) ) );
			}
		} else {
			$user_now                      = strtotime( DateHelper::s2u( date( 'Y-m-d H:i:s', time() ) ) );
			$user_now_datetime             = date( 'Y-m-d H:i:s', $user_now );
			$user_daystart_datetime        = date( 'Y-m-d', $user_now ) . ' 00:00:00';
			$server_now_datetime           = DateHelper::u2s( $user_now_datetime );
			$server_user_daystart_datetime = DateHelper::u2s( $user_daystart_datetime );
			$until = null;
			switch ( strtolower( $for ) ) {
				case 'day' :
					$from = date( 'Y-m-d H:i:s', strtotime( $server_user_daystart_datetime ) );
					break;
				case 'week' :
					$fdow = intval( get_option( 'start_of_week' ) );
					$dow  = intval( date( 'w', strtotime( $server_user_daystart_datetime ) ) );
					$d    = $dow - $fdow;
					$from = date( 'Y-m-d H:i:s', mktime( 0, 0, 0, date( 'm', strtotime( $server_user_daystart_datetime ) )  , date( 'd', strtotime( $server_user_daystart_datetime ) )- $d, date( 'Y', strtotime( $server_user_daystart_datetime ) ) ) );
					break;
				case 'month' :
					$from = date( 'Y-m', strtotime( $server_user_daystart_datetime ) ) . '-01 00:00:00';
					break;
				default :
					$from = null;
			}
		}
	}
	
}
AffiliatesExport::init();
