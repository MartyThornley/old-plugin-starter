<?php

class WP_Dev_Network {

	var $plugin_dir;
	var $plugin_url;
	var $admin_url;
	var $network;
	var $network_full_name;
	
	var $server_url;
	var $api_key_option_name;
		
	var $subscribe_url;
	var $network_thumbs_url;
	var $version;
	var $user_agent;
	var $minimum_version;
	
	function WP_Dev_Network() {
		
		global $wp_version;
		
		$this->definitions();
		$this->api_key_option_name 	= $this->network .'_api_key';
		
		//localize the plugin
		add_action( 'plugins_loaded', 						array(&$this, 'localization') );
		
		add_action( 'admin_init', 							array( &$this, 'admin_init' ) );
		add_action( 'admin_init', 							array( &$this, 'filter_plugin_rows' ), 15 ); //make sure it runs after WP's

		add_action( 'admin_menu', 							array( &$this, 'admin_menu' ) );
		add_action( 'network_admin_menu', 					array( &$this, 'admin_menu' ) ); //for 3.1
		
		add_action( 'admin_print_scripts-plugins.php', 		array( &$this, 'admin_scripts' ) );
		add_action( 'admin_print_styles-plugins.php', 		array( &$this, 'admin_styles' ) );

		//refresh local projects
		add_action( 'update-core.php', 						array( &$this, 'refresh_local_projects' ) );
		add_action( 'load-plugins.php', 					array( &$this, 'refresh_local_projects' ) );
		add_action( 'load-update.php', 						array( &$this, 'refresh_local_projects' ) );
		add_action( 'load-update-core.php', 				array( &$this, 'refresh_local_projects' ) );
		add_action( 'load-themes.php', 						array( &$this, 'refresh_local_projects' ) );
		add_action( 'wp_update_plugins', 					array( &$this, 'refresh_local_projects' ) );
		add_action( 'wp_update_themes', 					array( &$this, 'refresh_local_projects' ) );

		add_action( 'site_transient_update_plugins', 		array( &$this, 'filter_plugin_count' ) );
		add_action( 'site_transient_update_themes', 		array( &$this, 'filter_theme_count' ) );
		add_action( 'core_upgrade_preamble', 				array( &$this, 'list_updates' ) );

		// need to work on this to show correct compatibility
		// currently just does a var_dump
		// add_filter( 'plugins_api_result', array( &$this, 'filter_plugin_info' ), 10, 3 );  

		$this->plugin_dir = PLUGINSTARTER_PATH;
		$this->plugin_url = PLUGINSTARTER_URL;

		//get admin page location
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			if ( version_compare( $wp_version , $this->minimum_version , '>' ) )
				$this->admin_url = admin_url('network/plugins.php?page='.$this->network);
			else
				$this->admin_url = admin_url('ms-admin.php?page='.$this->network);
		} else {
			$this->admin_url = admin_url('plugins.php?page='.$this->network);
		}


	}

	function definitions() {
		/*
		$this->network 				= 'demo_network';
		$this->network_full_name 	= 'My Awesome Network';
		$this->server_url 			= 'http://mydomain.com/wdp-un.php';
		$this->api_key_option_name 	= $this->network .'_api_key';
		
		// url to your network logo
		$this->network_logo_url		= 'http://photographyblogsites.com/favicon.ico';
		
		// where can they signup for an API key?
		$this->subscribe_url 		= 'http://mydomain.com/wp-admin/profile.php?page=subscription';
		
		// location of project thumbnails
		$this->network_thumbs_url	= "http://mydomain.com/wp-content/projects";
				
		// your version number for this version of network - or for this actual notification plugin?
		$this->version 				= '2.1.3';	
		
		// fake user agent to test against in your local file...
		$this->user_agent 			= $this->network . '/' . $this->version;
		
		// minimum WP Version that we need
		$this->minimum_version 		= '3.0.9';
		*/
	}

	function localization() {
		// Load up the localization file if we're using WordPress in a different language
		// Place it in this plugin's "languages" folder and name it "wpmudev-[value in wp-config].mo"
		load_plugin_textdomain( $this->network, false, '/dev-network/languages/' );
	}

	/*
	 * Run during admin_init
	 */
	function admin_init() {
		
		$refresh_time_local = 1; // time in seconds between checking local projects
		$refresh_time_remote = 43200; // time in seconds between checking local projects
		
		if ( defined('WP_INSTALLING') )
			return false;
			
		if ( current_user_can('edit_users') ) {
			
			// schedule refresh of local projects list
			$time_ago = time() - get_site_option( $this->network.'_local_projects_refreshed' );
			if ( $time_ago > $refresh_time_local ) {
				$this->refresh_local_projects();
			}
			
			// check for updates 
			$time_ago = time() - get_site_option( $this->network.'_last_run' );

			if ( $time_ago > $refresh_time_remote ) { //12 hour refreshing
				$this->process();
			}

		}
		
	}


	function admin_menu() {
		global $wpdb, $wp_roles, $current_user, $wp_version;

		$updates = get_site_option( $this->network.'_updates_available' );
		$count = ( is_array( $updates ) ) ? count( $updates ) : 0;
		if ( $count > 0 ) {
			$count_output = ' <span class="updates-menu"><span class="update-plugins"><span class="updates-count count-' . $count . '">' . $count . '</span></span></span>';
		} else {
			$count_output = ' <span class="updates-menu"></span>';
		}

		if ( is_multisite() ) {
			if ( is_super_admin() ) {
				if ( version_compare( $wp_version , $this->minimum_version , '>' ) )
					$page = add_submenu_page( 'plugins.php' ,$this->network_full_name . __( ' Updates' , $this->network ) , $this->network_full_name . $count_output, 10, $this->network, array( &$this , 'page_output' ) );
				else
					$page = add_submenu_page( 'plugins.php' , $this->network_full_name . __( ' Updates' , $this->network ) , $this->network_full_name . $count_output, 10, $this->network, array( &$this , 'page_output' ) );
			}
		} else {
			$page = add_submenu_page('plugins.php', __($this->network_full_name, $this->network), $this->network_full_name . $count_output, 'manage_options', $this->network, array( &$this, 'page_output') );
		}
		add_action( 'admin_print_scripts-' . $page, array(&$this, 'admin_scripts') );
		add_action( 'admin_print_styles-' . $page, array(&$this, 'admin_styles') );
	}

	function admin_scripts() {
		wp_enqueue_script( 'thickbox' );
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-tabs' );
	}

	function admin_styles() {
		wp_enqueue_style( 'thickbox' );
		wp_enqueue_style( 'jquery-ui-tabs' );
	}
	
	/*
	 * Run during admin_init at priority 15
	 */
	function filter_plugin_rows() {
		if ( !current_user_can( 'update_plugins' ) )
			return;

		$updates = get_site_option( $this->network.'_updates_available' );
		if ( is_array( $updates ) && count( $updates ) ) {
			foreach ( $updates as $id => $plugin ) {
				if ( $plugin['autoupdate'] != '2' ) {
					if ( $plugin['type'] == 'theme' ) {
						remove_all_actions( 'after_theme_row_' . $plugin['filename'] );
						add_action( 'after_theme_row_' . $plugin['filename'], array( &$this, 'plugin_row' ), 9, 2 );
					} else {
						remove_all_actions( 'after_plugin_row_' . $plugin['filename'] );
						add_action( 'after_plugin_row_' . $plugin['filename'], array( &$this, 'plugin_row' ), 9, 2 );
					}
				}
			}
		}
	}
	
	/* Wrapper for backwards compatibility with 3.0
	 *
	 */
	function self_admin_url($path) {
		if ( function_exists('self_admin_url') )
			return self_admin_url($path);
		else
			return admin_url($path);
	}

	function refresh_local_projects() {

		$data = get_site_option( $this->network.'_last_response' );
		$now = time();

		if ( is_array( $data ) ) {
			$local_projects = $this->dev_get_projects();
			$current_local_projects = get_site_option( $this->network.'_local_projects' );

			//check for changes on remote api
			$current_local_projects_md5 = md5( serialize( $current_local_projects ) );
			$local_projects_md5 = md5( serialize( $local_projects ) );
			
			if ( $current_local_projects_md5 != $local_projects_md5 ) {
				//refresh data as installed plugins have changed
				unset( $data );
				$data = $this->process( $local_projects );
			}

			//save to be able to check for changes later
			update_site_option( $this->network.'_local_projects' , $local_projects );

			//save timestamp
			update_site_option( $this->network.'_local_projects_refreshed' , time() );
			
			// make sure we got proper array of info from server...
			$remote_projects = isset( $data['latest_versions'] ) ? $data['latest_versions'] : array();
			
			// determine if we have available upgrades
			$this->calculate_upgrades( $remote_projects , $local_projects );
		}
	}
	
	/*
	 * Compares data recieved to local versions
	 *
	 */
	function calculate_upgrades( $remote_projects , $local_projects ) {

		$updates = array();

		//check for updates
		if ( is_array( $remote_projects ) ) {
		
			// check all ['latest_versions'] returned from server
			foreach ( $remote_projects as $id => $remote_project ) {
				
				// if a matching local project exists
				if ( is_array( $local_projects[$id] ) ) {
					//match
					$local_version = $local_projects[$id]['version'];

					//handle wp autoupgrades
					if ( $remote_project['autoupdate'] == '2' ) {
						if ( $local_projects[$id]['type'] == 'plugin' ) {
							$update_plugins = get_site_transient( 'update_plugins' );
							$remote_version = $update_plugins->response[$local_projects[$id]['filename']]->new_version;
						} elseif ( $local_projects[$id]['type'] == 'theme' ) {
							$update_themes = get_site_transient( 'update_themes' );
							$remote_version = $update_themes->response[$local_projects[$id]['filename']]->new_version;
						} else {
							$remote_version = $remote_project['version'];
						}
					} else {
						$remote_version = $remote_project['version'];
					}

					if ( version_compare( $remote_version , $local_version , '>' ) ) {
						
						$autoupdate = ( ( $local_projects[$id]['type'] == 'plugin' || $local_projects[$id]['type'] == 'theme' ) && get_site_option( $this->api_key_option_name ) ) ? $remote_project['autoupdate'] : 0;
						
						//add to array
						$updates[$id] = 					$local_projects[$id];
						$updates[$id]['version'] = 			$local_version;
						$updates[$id]['url'] = 				$remote_project['url'];
						$updates[$id]['instructions_url'] = $remote_project['instructions_url'];
						$updates[$id]['support_url'] = 		$remote_project['support_url'];
						$updates[$id]['name'] = 			$remote_project['name'];
						$updates[$id]['new_version'] = 		$remote_version;
						$updates[$id]['changelog'] = 		$remote_project['changelog'];
						$updates[$id]['autoupdate'] = 		$autoupdate; //only allow autoupdates if installed in plugins
					}
				}
			}

			//record results
			update_site_option($this->network.'_updates_available', $updates);
		} else {
			return false;
		}
		
		return $updates;
	}
	
	/*
	 * Checks local projects against server to see if there are new versions
	 */
	function process( $local_projects = false ) {
		global $wpdb, $current_site;

		if ( defined( 'WP_INSTALLING' ) )
			return false;
			
		if ( !is_array( $local_projects ) )
			$local_projects = $this->dev_get_projects();
		
		//dev_debug($local_projects);
		
		update_site_option( $this->network.'_local_projects' , $local_projects );

		$api_key = get_site_option( $this->api_key_option_name );
		
		// sends variables:
		// domain = urlencode(network_site_url())
		// key = urlencode($api_key)
		// p = implode('.', array_keys($local_projects))  - will be list of ids 23.2.34.45.65
		
		//delete_site_option($this->network.'_last_response');
		
		//$url = $this->server_url . '?action=check&un-version=' . $this->version . '&domain=' . urlencode(network_site_url()) . '&key=' . urlencode($api_key) . '&p=' . implode('.', array_keys($local_projects));
	
		// our new api method
		$url = $this->server_url . '/api/stuff/34/check';

		$options = array(
			'timeout' => 15,
			'user-agent' => $this->user_agent
		);

		$response = wp_remote_get( $url , $options );
		
		// save response so we don't hae to check everytime
		update_site_option( $this->network.'_last_response', $response );
		
		if ( defined( 'PLUGINSTARTER_LOCAL' ) || wp_remote_retrieve_response_code( $response ) == 200 ) {
			$data = $response['body'];
			if ( $data != 'error' ) {
				$data = unserialize( $data );

				if ( is_array( $data ) ) {
					update_site_option( $this->network.'_last_response' , $data );
					update_site_option( $this->network.'_last_run' , time() );

					if ( !$data['membership'] || $data['membership'] == 'free' ) { //free member
						// Do stuff for free members
					} else if ( is_numeric( $data['membership'] ) ) { //single
						// do stuff for paid members
					}

					$remote_projects = $data['latest_versions'];

					$this->calculate_upgrades( $remote_projects , $local_projects );

					return $data;
					
				} else {
				
					return false;
				
				}
			
			} else {
			
				return false;
			
			}
		
		} else {
		
			return false;
		
		}
	}


	function filter_plugin_info( $res , $action , $args ) {
		if ( $action == 'plugin_information' )
			var_dump( $args );
		return $res;
	}

	function filter_plugin_count( $value ) {

		$updates = get_site_option( $this->network.'_updates_available' );
		if ( is_array( $updates ) && count( $updates ) ) {
			$api_key = get_site_option( $this->api_key_option_name );
			foreach ( $updates as $id => $plugin ) {
				if ( $plugin['type'] != 'theme' && $plugin['autoupdate'] != '2' ) {

					//build plugin class
					$object = new stdClass;
					$object->url = $plugin['url'];
					$object->upgrade_notice = $plugin['changelog'];
					$object->new_version = $plugin['new_version'];
					if ( $plugin['autoupdate'] == '1' )
						$object->package = $this->server_url . "?action=download&key=$api_key&pid=$id";

					//add to class
					$value->response[$plugin['filename']] = $object;
				}
			}
		}

		return $value;
	}

	function filter_theme_count( $value ) {

		$updates = get_site_option( $this->network.'_updates_available' );
		if ( is_array( $updates ) && count( $updates ) ) {
			$api_key = get_site_option( $this->api_key_option_name );
			foreach ( $updates as $id => $plugin ) {
				if ( $plugin['type'] == 'theme' && $plugin['autoupdate'] != '2' ) {
					//build theme listing
					$value->response[$plugin['filename']]['url'] = $plugin['url'];
					$value->response[$plugin['filename']]['new_version'] = $plugin['new_version'];

					if ( $plugin['autoupdate'] == '1' )
						$value->response[$plugin['filename']]['package'] = $this->server_url . "?action=download&key=$api_key&pid=$id";
				}
			}
		}

		return $value;
	}

	/* 
	 * add our own links after plugin row
	 */
	function plugin_row( $file , $plugin_data ) {

		//get new version and update url
		$updates = get_site_option( $this->network.'_updates_available' );
		
		if ( is_array( $updates ) && count( $updates ) ) {
			foreach ( $updates as $id => $plugin ) {
				if ( $plugin['filename'] == $file ) {
					$project_id = 	$id;
					$version = 		$plugin['new_version'];
					$plugin_url = 	$plugin['url'];
					$autoupdate = 	$plugin['autoupdate'];
					$filename = 	$plugin['filename'];
					$type = 		$plugin['type'];
					break;
				}
			}
		} else {
			return false;
		}

		$plugins_allowedtags = array(
			'a' => array(
				'href' => array(),
				'title' => array()
			),
			'abbr' => array(
				'title' => array()
			),
			'acronym' => array(
				'title' => array()
			),
			'code' => array(),
			'em' => array(),
			'strong' => array()
		);
		
		$plugin_name = wp_kses( $plugin_data['Name'], $plugins_allowedtags );

		$details_url = $this->server_url . '?action=details&id=' . $project_id . '&TB_iframe=true&width=640&height=700';

		if ( $type == 'plugin' )
			$autoupdate_url = wp_nonce_url( $this->self_admin_url('update.php?action=upgrade-plugin&plugin=') . $filename, 'upgrade-plugin_' . $filename);
		else if ( $type == 'theme' )
			$autoupdate_url = wp_nonce_url( $this->self_admin_url('update.php?action=upgrade-theme&theme=') . $filename, 'upgrade-theme_' . $filename);

		if ( current_user_can( 'update_plugins' ) ) {
			echo '<tr class="plugin-update-tr"><td colspan="3" class="plugin-update colspanchange"><div class="update-message">';
			if ($autoupdate)
				printf( __('There is a new version of %1$s available from %2$s. <a href="%3$s" class="thickbox" title="%4$s">View version %5$s details</a> or <a href="%6$s">automatically update</a>.', $this->network ), $plugin_name, $this->network_full_name, esc_url($details_url), esc_attr($plugin_name), $version, esc_url($autoupdate_url) );
			else
				printf( __('There is a new version of %1$s available from %2$s. <a href="%3$s" class="thickbox" title="%4$s">View version %5$s details</a> or <a href="%6$s" target="_blank" title="Download update from %7$s">download update</a>.', $this->network ), $plugin_name, $this->network_full_name, esc_url($details_url), esc_attr($plugin_name), $version, esc_url($plugin_url) , $this->network_full_name );
		}
		echo '</div></td></tr>';
	}

	function list_updates() {

		$updates = get_site_option( $this->network.'_updates_available' );
		if ( !is_array( $updates ) || ( is_array( $updates ) && !count( $updates ) ) ) {
			echo '<h3>' . $this->network_full_name . __( ' Plugins/Themes', $this->network ) . '</h3>';
			echo '<p>' . __( 'Your plugins/themes from ' , $this->network ) . $this->network_full_name . __( ' are all up to date.', $this->network ) . '</p>';
			return;
		}
?>
    <h3><?php echo $this->network_full_name; _e( ' Plugins/Themes', $this->network ); ?></h3>
    <p><?php _e( 'The following plugins/themes from ' ); echo $this->network_full_name; _e( ' have new versions available.', $this->network ); ?></p>
    <table class="widefat" cellspacing="0" id="update-plugins-table">
	<thead>
	<tr>
		<th scope="col" class="manage-column"><label><?php _e( 'Name' , $this->network ); ?></label></th>
		<th scope="col" class="manage-column"><label><?php _e( 'Links' , $this->network ); ?></label></th>
		<th scope="col" class="manage-column"><label><?php _e( 'Installed Version' , $this->network ); ?></label></th>
		<th scope="col" class="manage-column"><label><?php _e( 'Latest Version' , $this->network ); ?></label></th>
		<th scope="col" class="manage-column"><label><?php _e( 'Actions' , $this->network ); ?></label></th>
	</tr>
	</thead>

	<tfoot>
	<tr>
		<th scope="col" class="manage-column"><label><?php _e( 'Name' , $this->network ); ?></label></th>
		<th scope="col" class="manage-column"><label><?php _e( 'Links' , $this->network ); ?></label></th>
		<th scope="col" class="manage-column"><label><?php _e( 'Installed Version' , $this->network ); ?></label></th>
		<th scope="col" class="manage-column"><label><?php _e( 'Latest Version' , $this->network ); ?></label></th>
		<th scope="col" class="manage-column"><label><?php _e( 'Actions' , $this->network ); ?></label></th>
	</tr>
	</tfoot>
	<tbody class="plugins">
<?php
		foreach ( (array)$updates as $id => $plugin ) {
			$screenshot = $this->network_thumbs_url . "/$id/listing-image-thumb.png";

			if ( $plugin['autoupdate'] && $plugin['type'] == 'plugin' )
				$upgrade_button_code = "<a href='" . wp_nonce_url( $this->self_admin_url( 'update.php?action=upgrade-plugin&plugin=' ) . $plugin['filename'], 'upgrade-plugin_' . $plugin['filename'] ) . "' class='button-secondary'>".__( 'Auto Update' , $this->network )."&raquo;</a>";
			else if ( $plugin['autoupdate'] && $plugin['type'] == 'theme' )
				$upgrade_button_code = "<a href='" . wp_nonce_url( $this->self_admin_url( 'update.php?action=upgrade-theme&theme=' ) . $plugin['filename'], 'upgrade-theme_' . $plugin['filename'] ) . "' class='button-secondary'>".__( 'Auto Update' , $this->network )."&raquo;</a>";
			else
				$upgrade_button_code = "<a href='" . $plugin['url'] . "' class='button-secondary' target='_blank'>".__( 'Download Update' , $this->network )."&raquo;</a>";

			echo "
				<tr class='active'>
				<td class='plugin-title'><a target='_blank' href='{$plugin['url']}' title='" . __( 'More Information &raquo;' , $this->network ) . "'><img src='$screenshot' width='80' height='60' style='float:left; padding: 5px' /><strong>{$plugin['name']}</strong></a>" .  sprintf( __( 'You have version %1$s installed. Update to %2$s.' ), $plugin['version'], $plugin['new_version'] ) . "</td>
				<td style='vertical-align:middle;width:200px;'><a target='_blank' href='{$plugin['instructions_url']}'>" . __( 'Installation & Use Instructions &raquo;' , $this->network ) . "</a><br /><a target='_blank' href='{$plugin['support_url']}'>" . __( 'Get Support &raquo;', $this->network ) . "</a></td>
				<td style='vertical-align:middle'><strong>{$plugin['version']}</strong></td>
				<td style='vertical-align:middle'><strong><a href='{$this->server_url}?action=details&id={$id}&TB_iframe=true&width=640&height=700' class='thickbox' title='" . sprintf( __( 'View version %s details' , $this->network ) , $plugin['new_version'] ) . "'>{$plugin['new_version']}</a></strong></td>
				<td style='vertical-align:middle'>$upgrade_button_code</td>
				</tr>";
		}
?>
	</tbody>
    </table>
    <br />
<?php
	}

	function page_output() {
		global $wpdb, $current_site;

		if( !current_user_can( 'edit_users' ) ) {
			echo "<p>Nice Try...</p>";  //If accessed properly, this message doesn't appear.
			return;
		}

		//handle forced update
		if ( $_GET['action'] == 'update' ) {

			$result = $this->process();
			if ( is_array($result) ) {
				?><div class="updated fade"><p><?php _e('Update data successfully refreshed from ', $this->network); echo $this->network_full_name .'.'; ?></p></div><?php
			} else {
				?><div class="error fade"><p><?php _e('There was a problem refreshing data from ', $this->network); echo $this->network_full_name .'.'; ?></p></div><?php
			}

		} else {
			$this->refresh_local_projects();
		}
		
		// update user's API info and save	
		if ( isset( $_POST[$this->api_key_option_name] ) ) {
			
			update_site_option( $this->api_key_option_name , strip_tags( $_POST[$this->api_key_option_name] ) );
			
			$result = $this->process();
			
			dev_debug($_POST);
			
			// empty API info if non-member is returned from server
			if ( is_array( $result ) && !$result['membership'] ) {
				update_site_option( $this->api_key_option_name , '' );
				$message = '<div class="error fade"><p>' . __('Your API Key was invalid. Please try again.', $this->network) . '</p></div>';
				echo $message;
			}

			?>
			
			<div class="updated fade"><p><?php _e('Settings Saved!', $this->network); ?></p></div>
			
			<?php
		}
		$options = array(
			'timeout' => 15,
			'user-agent' => 'UN Client/' . $this->version
		);		
		$response = wp_remote_get($this->server_url, $options);
		
		
	//	update_site_option($this->network.'_last_response', $response);
		if ( wp_remote_retrieve_response_code($response) == 200 ) {
			$data = $response['body'];
			if ( $data != 'error' ) {
				$data = maybe_unserialize($data);
			}
		}
		//dev_debug($data); 	
		
?>
    <div class="wrap">
    <div class="icon32"><img src="<?php echo $this->network_logo_url ?>" /><br /></div>
    <h2><?php _e( $this->network_full_name . ' Updates', $this->network) ?></h2>
	<?php 
		//$data = get_site_option($this->network.'_last_response');
		$last_run = get_site_option($this->network.'_last_run');
	
	//	dev_debug($data); 	
	?>
      <p><?php echo $data['text_page_head']; ?></p>
			
			<style type="text/css">	
				a.metatab-file { padding: 2px 4px; border: 1px solid #e5e5e5; margin: 4px; }
				
				/* Tabs */
				.ui-tabs-nav {
					border-bottom: 1px solid #ccc !important;
					height: 27px;
					margin: 20px 0;
					padding: 0;
				}
				.ui-tabs-nav li {
					display: block;
					float: left;
					margin: 0;
				}
				.ui-tabs-nav li:first-child {
					margin-left: 5px;
				}
				.ui-tabs-nav li a {
					padding: 4px 20px 6px;
					font-weight: bold;
				}
				.ui-tabs-nav li a {
					border-style: solid;
					border-color: #CCC #CCC #F9F9F9;
					border-width: 1px 1px 0;
					color: #C1C1C1;
					text-shadow: rgba(255, 255, 255, 1) 0 1px 0;
					display: inline-block;
					padding: 4px 14px 6px;
					text-decoration: none;
					margin: 0 6px -1px 0;
					-moz-border-radius: 5px 5px 0 0;
					-webkit-border-top-left-radius: 5px;
					-webkit-border-top-right-radius: 5px;
					-khtml-border-top-left-radius: 5px;
					-khtml-border-top-right-radius: 5px;
					border-top-left-radius: 5px;
					border-top-right-radius: 5px;
				}
				.ui-tabs-nav li.ui-tabs-selected a,
				.ui-tabs-nav li.ui-state-active a {
				    border-bottom: 1px solid #fff;
				    border-width: 1px;
				    bottom: 1px;
				    color: #464646;
				    position: relative;
				}
				.ui-tabs-nav li.ui-tabs-selected a,
				.ui-tabs-nav li.ui-state-active a {
				    border-bottom: 1px solid #F5F5F5;
				    bottom: 0px;
				}
				.ui-tabs-panel {
					clear: both;
				}
				.ui-widget-content .ui-widget-content {
					padding: 0 14px 16px;
				}
				.ui-tabs-panel h3 {
					font: italic normal normal 24px/29px Georgia,"Times New Roman","Bitstream Charter",Times,serif;
					margin: 0;
					padding: 0 0 5px;
					line-height: 35px;
					text-shadow: 0 1px 0 #fff;
				}
				.ui-tabs-panel h4 {
					font-size: 15px;
					font-weight: bold;
					margin: 1em 0;
				}
				.tabbed_option input[type="checkbox"], .tabbed_option input[type="radio"] {
					margin-left: 10px;
					margin-top: 5px;
					vertical-align: bottom;
				}
			</style>
			<script type="text/javascript">
				//<![CDATA[
				jQuery(document).ready(function($) {
					$( "#tabs" ).tabs({
						fx: { opacity: 'toggle', duration: 'fast' }
					});
				});
				//]]>
			</script>

<div id="tabs">
	<ul>
		<li><a href="#tabs-latest">Latest Projects</a></li>
		<li><a href="#tabs-current">Manage Current Projects</a></li>
		<li><a href="#tabs-profile">Profile / Account</a></li>
	</ul>

	<!-- Latest Tab -->

	<div id="tabs-latest">
			      <h3><?php _e('Recently Released Plugins', $this->network) ?></h3>
			
			<?php
					// echo returned info:
					//site_admin_debug($data);
					
					echo "
						<table cellpadding='3' cellspacing='3' width='100%' class='widefat'>
						<thead><tr>
						<th scope='col'>".__('Name', $this->network)."</th>
						<th scope='col'>".__('Description', $this->network)."</th>
						</tr></thead>
						<tbody id='the-list'>
						";
					$latest_plugins = array();
					if ( isset($data['latest_plugins'])) {
						$latest_plugins = $data['latest_plugins'];
					}
					if (count($latest_plugins) > 0){
						$class = ('alternate' == $class) ? '' : 'alternate';
						foreach ($latest_plugins as $latest_plugin){
							//=========================================================//
							echo "<tr class='" . $class . "'>";
							echo "<td valign='top'><strong><a target='_blank' href='" . $latest_plugin['url'] . "'>" . stripslashes($latest_plugin['title']) . "</a></strong></td>";
							echo "<td valign='top'>" . stripslashes($latest_plugin['short_description']) . "</td>";
							echo "</tr>";
							$class = ('alternate' == $class) ? '' : 'alternate';
							//=========================================================//
						}
					}
			?>
			      </tbody></table>
			
			      <h3><?php _e('Recently Released Themes', $this->network) ?></h3>
			<?php
					echo "
						<table cellpadding='3' cellspacing='3' width='100%' class='widefat'>
						<thead><tr>
						<th scope='col'>".__('Name', $this->network)."</th>
						<th scope='col'>".__('Description', $this->network)."</th>
						</tr></thead>
						<tbody id='the-list'>
						";
					$latest_themes = array();
					if ( is_array( $data ) ) {
						$latest_themes = $data['latest_themes'];
					}
					if (count($latest_themes) > 0){
						$class = ('alternate' == $class) ? '' : 'alternate';
						foreach ($latest_themes as $latest_theme){
							//=========================================================//
							echo "<tr class='" . $class . "'>";
							echo "<td valign='top'><strong><a target='_blank' href='" . $latest_theme['url'] . "'>" . stripslashes($latest_theme['title']) . "</a></strong></td>";
							echo "<td valign='top'>" . stripslashes($latest_theme['short_description']) . "</td>";
							echo "</tr>";
							$class = ('alternate' == $class) ? '' : 'alternate';
							//=========================================================//
						}
					}
			?>
			      </tbody></table>

	</div>

	<!-- End Latest Tab -->

	<!-- Current Tab -->

	<div id="tabs-current">
	
      <h3><?php printf('Installed %1$s Projects', $this->network_full_name ) ?></h3>
<?php
		echo "
			<table cellpadding='3' cellspacing='3' width='100%' class='widefat'>
			<thead><tr>
			<th scope='col'>".__('Name', $this->network)."</th>
			<th scope='col'>".__('Links', $this->network)."</th>
			<th scope='col'>".__('Installed Version', $this->network)."</th>
			<th scope='col'>".__('Latest Version', $this->network)."</th>
			<th scope='col'>".__('Actions', $this->network)."</th>
			</tr></thead>
			<tbody id='the-list'>
			";
		$projects = array();
		if ( is_array( $data ) ) {
			$remote_projects = isset($data['latest_versions']) ? $data['latest_versions'] : array();
			$local_projects = get_site_option($this->network.'_local_projects');
			if ( is_array( $local_projects ) ) {
				foreach ( $remote_projects as $remote_id => $remote_project ) {
					$projects[$remote_id]['name'] = $remote_project['name'];
					$projects[$remote_id]['description'] = $remote_project['short_description'];
					$projects[$remote_id]['url'] = $remote_project['url'];
					$projects[$remote_id]['instructions_url'] = $remote_project['instructions_url'];
					$projects[$remote_id]['support_url'] = $remote_project['support_url'];
					$projects[$remote_id]['autoupdate'] = (($local_projects[$remote_id]['type'] == 'plugin' || $local_projects[$remote_id]['type'] == 'theme') && get_site_option($this->api_key_option_name)) ? $remote_project['autoupdate'] : 0;

					//handle wp autoupgrades
					if ($projects[$remote_id]['autoupdate'] == '2') {
						if ($local_projects[$remote_id]['type'] == 'plugin') {
							$update_plugins = get_site_transient('update_plugins');
							if ($update_plugins->response[$local_projects[$remote_id]['filename']]->new_version)
								$projects[$remote_id]['remote_version'] = $update_plugins->response[$local_projects[$remote_id]['filename']]->new_version;
							else
								$projects[$remote_id]['remote_version'] = $local_projects[$remote_id]['version'];
						} else if ($local_projects[$remote_id]['type'] == 'theme') {
							$update_themes = get_site_transient('update_themes');
							if ($update_themes->response[$local_projects[$remote_id]['filename']]['new_version'])
								$projects[$remote_id]['remote_version'] = $update_themes->response[$local_projects[$remote_id]['filename']]['new_version'];
							else
								$projects[$remote_id]['remote_version'] = $local_projects[$remote_id]['version'];
						} else {
							$projects[$remote_id]['remote_version'] = $remote_project['version'];
						}
					} else {
						$projects[$remote_id]['remote_version'] = $remote_project['version'];
					}

					$projects[$remote_id]['local_version'] = $local_projects[$remote_id]['version'];
					$projects[$remote_id]['filename'] = $local_projects[$remote_id]['filename'];
					$projects[$remote_id]['type'] = $local_projects[$remote_id]['type'];
				}
			}
		}
		if (count($projects) > 0) {
			$class = ('alternate' == $class) ? '' : 'alternate';
			foreach ($projects as $project_id => $project) {
				$local_version = $project['local_version'];
				$remote_version = $project['remote_version'];

				$check = (version_compare($remote_version, $local_version, '>')) ? "style='background-color:#FFEBE8;'" : '';

				if ( $project['autoupdate'] && $project['type'] == 'plugin' )
					$upgrade_button_code = "<a href='" . wp_nonce_url( $this->self_admin_url('update.php?action=upgrade-plugin&plugin=') . $project['filename'], 'upgrade-plugin_' . $project['filename']) . "' class='button-secondary'>".__('Auto Update', $this->network)."&raquo;</a>";
				else if ( $project['autoupdate'] && $project['type'] == 'theme' )
					$upgrade_button_code = "<a href='" . wp_nonce_url( $this->self_admin_url('update.php?action=upgrade-theme&theme=') . $project['filename'], 'upgrade-theme_' . $project['filename']) . "' class='button-secondary'>".__('Auto Update', $this->network)."&raquo;</a>";
				else
					$upgrade_button_code = "<a href='" . $project['url'] . "' class='button-secondary' target='_blank'>".__('Download Update', $this->network)."&raquo;</a>";

				$upgrade_button = (version_compare($remote_version, $local_version, '>')) ? $upgrade_button_code : '';

				$screenshot = $project['thumbnail'];

				//=========================================================//
				echo "<tr class='" . $class . "' " . $check . " >";
				echo "<td style='vertical-align:middle'><img src='$screenshot' width='40' height='30' style='float:left; padding: 5px' /></a><strong><a target='_blank' href='{$project['url']}' title='" . __('More Information &raquo;', $this->network) . "'>{$project['name']}</a></strong><br />{$project['description']}</td>";
				echo "<td style='vertical-align:middle;width:200px;'><a target='_blank' href='{$project['instructions_url']}'>" . __('Installation & Use Instructions &raquo;', $this->network) . "</a><br /><a target='_blank' href='{$project['support_url']}'>" . __('Get Support &raquo;', $this->network) . "</a></td>";
				echo "<td style='vertical-align:middle'><strong>" . $local_version . "</strong></td>";
				echo "<td style='vertical-align:middle'><strong><a href='{$this->server_url}?action=details&id={$project_id}&TB_iframe=true&width=640&height=700' class='thickbox' title='" . sprintf( __('View version %s details', $this->network), $remote_version ) . "'>{$remote_version}</a></strong></td>";
				echo "<td style='vertical-align:middle'>" . $upgrade_button . "</td>";
				echo "</tr>";
				$class = ('alternate' == $class) ? '' : 'alternate';
				//=========================================================//
			}
		}
?>
      </tbody></table>
      <p><?php _e('Please note that all data is updated every 12 hours.', $this->network) ?> <?php _e('Last updated:', $this->network); ?> <?php echo get_date_from_gmt(date('Y-m-d H:i:s', $last_run), get_option('date_format') . ' ' . get_option('time_format')); ?> - <a href="<?php echo $this->admin_url; ?>&action=update"><?php _e('Update Now', $this->network); ?></a></p>
      <p><small>* <?php _e('Latest plugins, themes and installed plugins and themes above only refer to those provided to', $this->network) ?> <a href="http://mydomain.com/join/"><?php _e('My Network members'); ?></a> <?php _e('by Incsub - other plugins and themes are not included here.', $this->network); ?></small></p>


	</div>	
	
	<!-- End Current Tab -->

	
	<!-- Profile / Account Tab -->
	
	<div id="tabs-profile">


			      <h3><?php _e('API Key', $this->network) ?></h3>
			      <form method="post" action="<?php echo $this->admin_url .'#tabs-profile' ; ?>">
			      <table class="form-table">
			<?php
					$api_key = get_site_option($this->api_key_option_name);
					if ( $api_key && $data['membership'] ) {
						$style = ' style="background-color:#ADFFAA;"';
					} else {
						$style = ' style="background-color:#FF7C7C;"';
					}
			?>
				<tr valign="top">
				<th scope="row"><?php echo $this->network_full_name; _e(' API Key', $this->network ) ?>*</th>
				<td><input type="text" id="<?php echo $this->api_key_option_name;?>" name="<?php echo $this->api_key_option_name;?>"<?php echo $style; ?> value="<?php echo $api_key; ?>" size="50" /><input type="submit" name="check_key" value="<?php _e('Check Key &raquo;', $this->network) ?>" />
				<br /><?php printf( __( 'Enter your API Key to enable auto-updates and special members-only offers from %1$s. You can <a href="'.$this->subscribe_url.'" target="_blank">get your free API Key here&raquo;</a>', $this->network ) , $this->network_full_name ); ?></td>
				</tr>
			      </table>

			
			      <p class="submit">
			      <input type="submit" name="Submit" value="<?php _e('Save Changes', $this->network) ?>" />
			      </p>
			      </form>
	</div>

	<!-- End Profile / Account Tab -->


</div>

</div>
<?php
	}

	
	function dev_get_project_data( $plugin_file ) {
		
		$data = array( 
			'theme_name' 		=> 'Theme Name', 
			'plugin_name' 		=> 'Plugin Name', 
			'id' 				=> $this->network_full_name.' ID', 
			'version' 			=> 'Version'
		);
		
		return get_file_data( $plugin_file , $data );
	}
	
	function dev_get_projects() {
		$projects = array();
		
		// get plugins
		
		$plugins_root = WP_PLUGIN_DIR;
		if( empty($plugins_root) ) {
			$plugins_root = ABSPATH . 'wp-content/plugins';
		}

		$plugins_dir = @opendir($plugins_root);
		$plugin_files = array();
		if ( $plugins_dir ) {
			while (($file = readdir( $plugins_dir ) ) !== false ) {
				if ( substr($file, 0, 1) == '.' )
					continue;
				if ( is_dir( $plugins_root.'/'.$file ) ) {
					$plugins_subdir = @ opendir( $plugins_root.'/'.$file );
					if ( $plugins_subdir ) {
						while (($subfile = readdir( $plugins_subdir ) ) !== false ) {
							if ( substr($subfile, 0, 1) == '.' )
								continue;
							if ( substr($subfile, -4) == '.php' )
								$plugin_files[] = "$file/$subfile";
						}
					}
				} else {
					if ( substr($file, -4) == '.php' )
						$plugin_files[] = $file;
				}
			}
		}
		@closedir( $plugins_dir );
		@closedir( $plugins_subdir );

		if ( $plugins_dir && !empty($plugin_files) ) {
			foreach ( $plugin_files as $plugin_file ) {
				if ( is_readable( "$plugins_root/$plugin_file" ) ) {

					unset($data);
					$data = $this->dev_get_project_data( "$plugins_root/$plugin_file" );

					if ( $data['id'] ) {
						$projects[$data['id']]['name'] = $data['plugin_name'];
						$projects[$data['id']]['type'] = 'plugin';
						$projects[$data['id']]['version'] = $data['version'];
						$projects[$data['id']]['filename'] = $plugin_file;
					}
				}
			}
		}
		
		// get mu-plugins
		
		$mu_plugins_root = WPMU_PLUGIN_DIR;
		if( empty($mu_plugins_root) ) {
			$mu_plugins_root = ABSPATH . 'wp-content/mu-plugins';
		}

		if ( $mu_plugins_dir = @opendir($mu_plugins_root) ) {
			while (($file = readdir( $mu_plugins_dir ) ) !== false ) {
				if ( substr($file, -4) == '.php' ) {
					if ( is_readable( "$mu_plugins_root/$file" ) ) {

						unset($data);
						$data = $this->dev_get_project_data( "$mu_plugins_root/$file" );

						if ( $data['id'] ) {
							$projects[$data['id']]['name'] = $data['plugin_name'];						
							$projects[$data['id']]['type'] = 'mu-plugin';
							$projects[$data['id']]['version'] = $data['version'];
							$projects[$data['id']]['filename'] = $file;
						}
					}
				}
			}
		}
		@closedir( $mu_plugins_dir );

		// get inserts
		
		$content_plugins_root = WP_CONTENT_DIR;
		if( empty($content_plugins_root) ) {
			$content_plugins_root = ABSPATH . 'wp-content';
		}

		$content_plugins_dir = @opendir($content_plugins_root);
		$content_plugin_files = array();
		if ( $content_plugins_dir ) {
			while (($file = readdir( $content_plugins_dir ) ) !== false ) {
				if ( substr($file, 0, 1) == '.' )
					continue;
				if ( !is_dir( $content_plugins_root.'/'.$file ) ) {
					if ( substr($file, -4) == '.php' )
						$content_plugin_files[] = $file;
				}
			}
		}
		@closedir( $content_plugins_dir );

		if ( $content_plugins_dir && !empty($content_plugin_files) ) {
			foreach ( $content_plugin_files as $content_plugin_file ) {
				if ( is_readable( "$content_plugins_root/$content_plugin_file" ) ) {
					unset($data);
					$data = $this->dev_get_project_data( "$content_plugins_root/$content_plugin_file" );

					if ( $data['id'] ) {
						$projects[$data['id']]['name'] = $data['plugin_name'];					
						$projects[$data['id']]['type'] = 'drop-in';
						$projects[$data['id']]['version'] = $data['version'];
						$projects[$data['id']]['filename'] = $mu_plugin_file;
					}
				}
			}
		}

		// get themes
		
		$themes_root = WP_CONTENT_DIR . '/themes';
		if( empty($themes_root) ) {
			$themes_root = ABSPATH . 'wp-content/themes';
		}

		$themes_dir = @opendir($themes_root);
		$themes_files = array();
		if ( $themes_dir ) {
			while (($file = readdir( $themes_dir ) ) !== false ) {
				if ( substr($file, 0, 1) == '.' )
					continue;
				if ( is_dir( $themes_root.'/'.$file ) ) {
					$themes_subdir = @ opendir( $themes_root.'/'.$file );
					if ( $themes_subdir ) {
						while (($subfile = readdir( $themes_subdir ) ) !== false ) {
							if ( substr($subfile, 0, 1) == '.' )
								continue;
							if ( substr($subfile, -4) == '.css' )
								$themes_files[] = "$file/$subfile";
						}
					}
				} else {
					if ( substr($file, -4) == '.css' )
						$themes_files[] = $file;
				}
			}
		}
		@closedir( $themes_dir );
		@closedir( $themes_subdir );

		if ( $themes_dir && !empty($themes_files) ) {
			foreach ( $themes_files as $themes_file ) {

				//skip child themes
				if ( strpos( $themes_file, '-child' ) !== false )
					continue;

				if ( is_readable( "$themes_root/$themes_file" ) ) {

					unset($data);
					$data = $this->dev_get_project_data( "$themes_root/$themes_file" );

					if ( $data['id'] ) {
						$projects[$data['id']]['name'] = $data['theme_name'];
						$projects[$data['id']]['type'] = 'theme';
						$projects[$data['id']]['version'] = $data['version'];
						$projects[$data['id']]['filename'] = substr( $themes_file, 0, strpos( $themes_file, '/' ) );
					}
				}
			}
		}
		dev_debug($projects);
		return $projects;
	}

}
?>