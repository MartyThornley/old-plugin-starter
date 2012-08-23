<?php

/**
 * Plugin Name: PluginStarter
 * Plugin URI: http://pluginstarter.com
 * Description: A base plugin to build more plugins with!
 * Version: 0.1
 * Author: Marty Thornley
 * Author URI: http://pluginstarter.com
 * Contributors: martythornley
 *
 * @copyright 2012 Marty Thornley
 * @link http://pluginstarter.com
 *
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @package Plugin_Starter
 *
 * Note about contributors: 
 * This plugin framework was built in part due to the work of austinpassy as part of development of another plugin, yet to be released.
 *
 */

	if ( !defined( 'PLUGINSTARTER_DEBUG' ) ) 			define( 'PLUGINSTARTER_DEBUG', 			false );
	if ( !defined( 'PLUGINSTARTER_PATH' ) )				define( 'PLUGINSTARTER_PATH',			plugin_dir_path( __FILE__ ) . '/plugin-starter' );
	if ( !defined( 'PLUGINSTARTER_URL' ) )				define( 'PLUGINSTARTER_URL',			plugin_dir_url( __FILE__ ) );

	require_once( trailingslashit( PLUGINSTARTER_PATH ) . 'classes/Plugin_Starter.php' );		

?>