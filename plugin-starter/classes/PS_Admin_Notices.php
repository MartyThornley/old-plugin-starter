<?php

// should be moved to network plugin

class PS_Admin_Notices {

	/*
	 * NOTICES:
	 *
	 *	Upgrades Available and number of
	 *	API Key is empty or invalid
	 *	Annoucements sent back in response - based on membership level and allow them to dismiss
	 *
	 */

	var $notices = array();

	__construct( $notice_group = '' , $notices ) {

		add_action( 'admin_init' , 							array( &$this, 'admin_init' ) );
		add_action( 'admin_footer' , 						array( &$this, 'admin_footer' ) );
		add_action( 'wp_ajax_'.$notice_group.'_dismiss' , 	array( &$this, 'ajax_dismiss' ) );

	}
	
	function admin_init() {
	
		/*
			$notice_group = 'my_group_name';
			$notices = array(
				
				'network' => array(
					'message' => array(
						'1' => array(
							'page'		=> 'string to check url against',
							'hook'		=> 'specific hook to show message on',
							'message'	=> 'Your message, html allowed',
						),
				
						'2' => array(
				
				
						),				
					
					
					),
					
					'error' => array(
					
					
					),
				
				),
				
				'all' => array(
				
				
				),
				
				
				'user' => array(
				
				
				
				),
			

			
			
			);
		*/
	
	}
	
	function ajax_dismiss() {
	
	
	}

	function wp_footer() {
	
	
	}

}

?>
