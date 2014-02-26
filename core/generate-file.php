<?php
if ( !defined( 'ABSPATH' ) ) {
	$wpfile = 'wp-load.php'; 
	$cntlevel = 100; 
	while ( !file_exists( $wpfile ) && ( $cntlevel > 0 ) ) {
		$wpfile = '../' . $wpfile; 
		$cntlevel--;
	} if ( file_exists( $wpfile ) ) {
		require_once $wpfile;
	}
} if ( defined( 'ABSPATH' ) ) {
	if ( !current_user_can( AFFILIATES_ACCESS_AFFILIATES ) ) {
		wp_die( __( 'Access denied.', AFFILIATES_PRO_PLUGIN_DOMAIN ) );
	} else { 
		if ( isset ( $_GET['action'] ) ) {
			global $wpdb, $affiliates_db; 
			switch( $_GET['action'] ) {
				case 'generate_file' : 
					AffiliatesExport::generate( 
						get_option( 'blog_charset' ) 
					); 
					die;
					break;
			}
		}
	}
}