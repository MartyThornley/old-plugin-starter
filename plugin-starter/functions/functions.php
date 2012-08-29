<?php

if ( !function_exists( 'dev_debug' ) ) {
	function dev_debug( $data ) {
		print '<pre>'; print_r( $data ); print '</pre>';
	}
}

if ( ! function_exists( 'register_dev_network' ) ) {	
	function register_dev_network( $args ) {
		
		$dev_networks = get_option( 'dev_networks' );
		
		if ( ! isset( $dev_networks[$args['name']] ) ) {
			$dev_networks[] = $args['name'];
			update_option( 'dev_networks' , $dev_networks );
		}
		
		add_option( 'dev_network_'.$args['name'] , $args );

	}
}

?>