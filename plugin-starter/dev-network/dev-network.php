<?php
/*
Plugin Name: WP Developer Network
Plugin URI: http://pluginstarter.com
Description: Create your own updatable plugin or theme network.
Author: Marty Thornley
Version: .1
Author URI: http://pluginstarter.com/
Network: true
DevProject ID: 76

Based on: WPMU DEV Update Notifications Version: 2.1.3 - http://premium.wpmudev.org/project/update-notifications/
Copyright 2007-2011 Incsub (http://incsub.com)
by Aaron Edwards (Incsub)
http://premium.wpmudev.org/

*/

/*
Copyright Marty Thornley 2012

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
	
	// define this for testing locally
	define( 'DEV_TESTING_LOCAL' , true );
	
	//setup proper directories
	if ( !defined ( 'WP_DEV_NETWORK_PATH' ) ) { define ( 'WP_DEV_NETWORK_PATH', dirname(__FILE__) ); };
	
	// need change this to mu?
	$network_url = WP_PLUGIN_URL . '/' . str_replace( basename( __FILE__ ) , "" , plugin_basename(__FILE__) );
	if ( !defined ( 'WP_DEV_NETWORK_URL' ) ) { define ( 'WP_DEV_NETWORK_URL' , $network_url ); };
	
	if ( !class_exists( 'WP_Dev_Network' ) )
		require_once( trailingslashit( WP_DEV_NETWORK_PATH ) . 'library/classes/WP_Dev_Network.php' );	

	require_once( trailingslashit( WP_DEV_NETWORK_PATH ) . 'library/functions/functions.php' );	
	
	class BlogSite_Plugins_Network extends WP_Dev_Network {
		
		function definitions() {
	
			$this->network 				= 'blogsite_plugins_network';
			$this->network_full_name 	= 'BlogSite Plugins';
			$this->server_url 			= 'http://localhost/dev_host/api/info';
			
			// url to your network logo
			$this->network_logo_url		= 'http://photographyblogsites.com/favicon.ico';
			
			// where can they signup for an API key?
			$this->subscribe_url 		= 'http://photographyblogsites.com';
			
			// location of project thumbnails
			$this->network_thumbs_url	= "http://premium.wpmudev.org/wp-content/projects";
					
			// your version number for this version of network - or for this actual notification plugin?
			$this->version 				= '2.1.3';	
			
			// fake user agent to test against in your local file...
			$this->user_agent 			= $this->network . '/' . $this->version;
			
			// minimum WP Version that we need
			$this->minimum_version 		= '3.0.9';
		}
		
	}

	$BlogSite_Plugins_Network = new BlogSite_Plugins_Network();
	