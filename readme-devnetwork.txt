
INFO FOR THICKBOX POPUP

Will send your update url the following:

	action=details				// 'check', 'details' , 'download'
	id=' . $project_id . '		// the project id

AUTO UPDATE PROCESS

adds update urls to the auto update functions for both plugins and themes

Will send your update url :

	action=download				// let us know we are downloading
	key=$api_key				// user's API key	
	pid=$id						// project id

	return 400 if API key is no good

CHECKING FOR AVAILABLE UPDATES

Will send info to your server at defined url

		// sends variables:
		// domain = urlencode(network_site_url())
		// key = urlencode($api_key)
		// p = implode('.', array_keys($local_projects))  - will be list of ids 23.2.34.45.65

You can check API Key and domain against your saved user info to see if they are registered or not

You should return serialized version (maybe using json_encode?)

		$data = array(
			
			'membership'		=> array(
			
				'level'		=> 'pro',
			
			),
			
			'latest_versions'	=> array(
				'5' => array (
                    'id' 					=> '5',
                    'name' 					=> 'Admin Message',
                    'short_description' 	=> 'This is a simple plugin that makes it easier to place a message in the admin panels that all users will see.',
                    'version'	 			=> '1.1.1',
                    'autoupdate' 			=> '1',
                    'changelog' 			=> '3.1+ compatibility update',
                    'url' 					=> 'http://mydomain.com/project/admin-message/',
                    'instructions_url' 		=> 'http://mydomain.com/project/admin-message/installation/',
                    'support_url' 			=> 'http://mydomain.com/forums/tags/admin-message/',			
				),
				'6' => array (
                    'id' 					=> '6',
                    'name' 					=> 'Admin Message',
                    'short_description' 	=> 'This is a simple plugin that makes it easier to place a message in the admin panels that all users will see.',
                    'version'	 			=> '1.1.1',
                    'autoupdate' 			=> '1',
                    'changelog' 			=> '3.1+ compatibility update',
                    'url' 					=> 'http://mydomain.com/project/admin-message/',
                    'instructions_url' 		=> 'http://mydomain.com/project/admin-message/installation/',
                    'support_url' 			=> 'http://mydomain.com/forums/tags/admin-message/',			
				),
			),
			
			// annoucements of new plugins...
			'latest_plugins' 	=> array(
	            '100' => array (
					'id' 					=> '100',
	                'title' 				=> 'PluginName',
	                'version' 				=> '1.1.1',
	                'autoupdate' 			=> '1',
	                'short_description' 	=> 'SimpleMarket is all about simplicity. Get your website up and running in seconds with the simplest theme you'll ever setup!',
	                'url' 					=> 'http://mydomain.com/project/simplemarket/',			
				),			
			),
			
			// annoucements of new themes...
			'latest_themes' 	=> array(
	            '237' => array (
					'id' 					=> '237',
	                'title' 				=> 'SimpleMarket',
	                'version' 				=> '1.1.1',
	                'autoupdate' 			=> '1',
	                'short_description' 	=> 'SimpleMarket is all about simplicity. Get your website up and running in seconds with the simplest theme you'll ever setup!',
	                'url' 					=> 'http://mydomain.com/project/simplemarket/',			
				),
			),
					
		);