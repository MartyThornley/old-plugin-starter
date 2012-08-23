<?php
/**
 * Base class for plugin creation.
 *
 * @package PluginStarter
 *
 */

	// My_Plugin extends Plugin_Starter

class Plugin_Starter {

	var $debug =		false;	
	
	var $domain 		= '';
	var $longname	 	= '';
	var $shortname 		= '';	
	var $plugin_path 	= '';
	var $plugin_url 	= '';

	var $blog_url 		= '';
		
	/**
	 * The constructor is executed when the class is instatiated and the plugin gets loaded.
	 * @return void
	 */	
	function __construct() {	 
		
		// add all basic action calls
		// add call to admin class (if is_admin)
		// call extra init fuunction that extensions can use for initial action hooks

		// call these first - we need to make sure everything can access definitions - init is too late
		$this->definitions();

		register_activation_hook( __FILE__, 	array( $this, 'activate' ) );
		register_uninstall_hook( __FILE__, 		array( $this, 'deactivate' ) );
		
		/* Initiate the plugin */
		add_action( 'init',						array( $this, 'init' ) );

		/* Rewrites */
		add_action( 'wp_loaded' ,				array( $this, 'flush_rules' ) );
		add_filter( 'rewrite_rules_array' ,		array( $this, 'insert_rewrite_rules' ) );
		add_filter( 'query_vars' ,				array( $this, 'insert_query_vars' ) );
		add_action( 'template_redirect' , 		array( $this, 'template_redirect' ) );

		/* Standard WP Hooks */
		add_action( 'wp_head',					array( $this, 'wp_head' ) );
		add_action( 'wp_footer',				array( $this, 'wp_footer' ) );
		
		if ( is_admin() ) {
			// add our classes first...
			require_once( trailingslashit( PLUGINSTARTER_PATH ) . 'classes/Tabbed_Meta_Box.php' );		
			require_once( trailingslashit( PLUGINSTARTER_PATH ) . 'classes/Plugin_Starter_Admin.php' );			
		}
	}
	
	/**
	 * Load plugin definitions
	 * @return void
	 */
	function plugin_starterdefinitions() {

	}
	
	/**
	 * Load plugin definitions
	 * @return void
	 */
	function definitions() {

		if ( !defined( 'PLUGINSTARTER' ) )					define( 'PLUGINSTARTER', 				'plugin-starter' );
		if ( !defined( 'PLUGINSTARTER_BASENAME' ) )			define( 'PLUGINSTARTER_BASENAME',		plugin_basename( __FILE__ ) );
		
		if ( !defined( 'PLUGINSTARTER_UPDATE_API' ) )		define( 'PLUGINSTARTER_UPDATE_API',		'http://pluginstarter.com' );
		if ( !defined( 'PLUGINSTARTER_MEMBER_LOGIN' ) )		define( 'PLUGINSTARTER_MEMBER_LOGIN',	PLUGINSTARTER_UPDATE_API . '/wp-login.php?redirect_to=' . PLUGINSTARTER_UPDATE_API . '/my-account/' );
		
		$this->blog_url 			= get_option('home');
		$this->stylesheet_directory = get_stylesheet_directory();
		$this->template_directory 	= get_template_directory();

		define( 'PLUGINSTARTER_BLOG_URL' , $this->blog_url );
		define( 'PLUGINSTARTER_STYLESHEETPATH' , $this->stylesheet_directory );
		define( 'PLUGINSTARTER_TEMPLATEPATH' , $this->template_directory );
		
	}
	
	/**
	 * Loading the gettext textdomain first from the WP languages directory,
	 * and if that fails try the subfolder /languages/ in the plugin directory.
	 * @return void
	 */
	function localize() {
		load_plugin_textdomain( $this->domain, false, dirname( $this->basename ) . '/languages/' );
	}
	
	/**
	 * Run on plugin activation
	 */
	function activate() {
		do_action( 'plugin_starter_activate' );	
	}

	/**
	 * Run on plugin deactivation
	 */
	function deactivate() {
		do_action( 'plugin_starter_deactivate' );	
	}
	
	/**
	 * The constructor is executed when the class is instatiated and the plugin gets loaded.
	 * @return void
	 */	
	function admin_init_actions() {	

	}			
	
	/**
	 * Function to run on 'init'
	 */
	function init() {
	
		$this->localize();	
		
		/* Scripts and Styles */
		$this->wp_register_scripts();
		add_action( 'wp_enqueue_scripts',		array( $this, 'wp_enqueue_scripts' ) );
				
		$this->init_actions();
		
		if ( is_admin() )
			$this->admin_init_actions();				
	}
	
	/**
	 * The constructor is executed when the class is instatiated and the plugin gets loaded.
	 * @return void
	 */	
	function init_actions() {	

	}

	/**
	 * wp_head
	 */
	function wp_head() {
			
	}
	
	/**
	 * wp_footer
	 */
	function wp_footer() {
			
	}
		
	/**
	 * wp_enqueue_scripts
	 */
	function wp_register_scripts() {
			
	}
	
	/**
	 * wp_enqueue_scripts
	 */
	function wp_enqueue_scripts() {
			
	}
	
	/**
	 * Function to run on 'init'
	 */
	function flush_rules() {
	
	}

	/**
	 * Function to run on 'init'
	 */
	function insert_rewrite_rules( $rules ) {
		return $rules;
	}
	
	/**
	 * Function to run on 'init'
	 */
	function insert_query_vars( $vars ) {
		return $vars;
	}	
	
	public function get_plugin_template( $path , $domain , $template_name = '' ) {
		
		$located = '';	

		$located = Plugin_Starter::find_plugin_template( $path , $domain , $template_name );
		
		if ( '' != $located )
			load_template( $located, true );	
	
	}
	
	public function find_plugin_template( $path , $domain , $template_name = '' ) {
		
		if ( file_exists( STYLESHEETPATH . '/'.$domain.'/' . $template_name ) ) {
			$located = STYLESHEETPATH . '/'.$domain.'/' . $template_name;
		} else if ( file_exists( TEMPLATEPATH . '/'.$domain.'/' . $template_name ) ) {
			$located = TEMPLATEPATH . '/'.$domain.'/' . $template_name;
		} else if ( file_exists( trailingslashit( $path ) . $template_name ) ) {
			$located = trailingslashit( $path ) . $template_name;
		}	
		
		return $located;	
	}
				
	/**
	 * Function for getting an array of available custom templates with a specific header. Ideally, this function 
	 * would be used to grab custom singular post templates.  It is a recreation of the WordPress
	 * page templates function because it doesn't allow for other types of templates.
	 *
	 * @param array $args Arguments to check the templates against.
	 * @return array $post_templates The array of templates.
	 */
	function get_plugin_templates( $args = array() ) {

		$defaults = array( 
			'label' => array( 'Plugin Template' ), 
			'extension' => 'php', 
			'folder' => '' 
		);

        /* Parse the arguments with the defaults. */
        $args = wp_parse_args( $args, $defaults );

        /* Get theme and templates variables. */
        $themes = get_themes();
        $theme = get_current_theme();
        $templates = $themes[$theme]['Template Files'];
        $theme_templates = array();
        $plugin_templates = array();
		$plugin_templates_final = array();
		
		if ( $args['folder'] != '' )
			$folder = '/' . $args['folder'];
		
        $plugin_root = trailingslashit( $this->plugin_path ) . 'templates' . $folder;
		
        // look through plugin directory
        $plugin_dir = @ dir("$plugin_root");
        if ( $plugin_dir ) {
            while ( ($file = $plugin_dir->read()) !== false ) {
                if ( !preg_match('|^\.+$|', $file) ) {
					if ( preg_match('|\.'.$args['extension'].'$|', $file) )
                        $plugin_templates[] = "$plugin_root/$file";
                }
            }
            @ $plugin_dir->close();
        }

        /* If there's an array of templates, loop through each template. */
        if ( is_array( $templates ) ) {

            /* Set up a $base path that we'll use to remove from the file name. */
            $base = array(
                trailingslashit( get_stylesheet_directory()	),
                trailingslashit( get_template_directory() ),
            );

            /* Loop through the post templates. */
            foreach ( $templates as $template ) {

                /* Remove the base (parent/child theme path) from the template file name. */
                $basename = str_replace( $base, '', $template );

                /* Get the template data. */
                $template_data = implode( '', file( $template ) );

                /* Make sure the name is set to an empty string. */
                $name = '';

                /* Loop through each of the potential labels and see if a match is found. */
                foreach ( $args['label'] as $label ) {
                    if ( preg_match( "|{$label}:(.*)$|mi", $template_data, $name ) ) {
                        $name = _cleanup_header_comment( $name[1] );
                        break;
                    }
                }

                /* If a post template was found, add its name and file name to the $post_templates array. */
                if ( !empty( $name ) )
                    $theme_templates[trim( $name )] = $basename;
            }
        }

        /* If there's an array of templates, loop through each template. */
        if ( is_array( $plugin_templates ) ) {

            /* Set up a $base path that we'll use to remove from the file name. */
            $base = array(
				trailingslashit( $this->plugin_path ) . 'templates',
				trailingslashit( $this->plugin_path ) . trailingslashit( 'templates' ) . 'header.php',
				trailingslashit( $this->plugin_path ) . trailingslashit( 'templates' ) . 'footer.php',
            );

            /* Loop through the post templates. */
            foreach ( $plugin_templates as $template ) {

                /* Remove the base (parent/child theme path) from the template file name. */
                $basename = str_replace( $base, '', $template );

                /* Get the template data. */
                $template_data = implode( '', file( $template ) );

                /* Make sure the name is set to an empty string. */
                $name = '';

                /* Loop through each of the potential labels and see if a match is found. */
                foreach ( $args['label'] as $label ) {
                    if ( preg_match( "|{$label}:(.*)$|mi", $template_data, $name ) ) {
                        $name = _cleanup_header_comment( $name[1] );
                        break;
                    }
                }

                /* If a post template was found, add its name and file name to the $post_templates array. */
                if ( !empty( $name ) )
                    $plugin_templates_final[trim( $name )] = $basename;
            }
        }
		
        $post_templates = array_merge( $plugin_templates_final, $theme_templates );

        /* Return array of post templates. */
		return apply_filters( 'get_plugin_templates', $post_templates );
    }

	/*
	 * Safely include file
	 */
	function include_file( $file = '' ) {
		if ( file_exists( $file) )
			include ( $file );
	}

	function get_hooked_functions($tag=false){
		global $wp_filter;
	 	if ( $tag ) {
		  	$hook[$tag]=$wp_filter[$tag];
		  	if (!is_array($hook[$tag])) {
		 		trigger_error("Nothing found for '$tag' hook", E_USER_WARNING);
		  		return;
		  	}
		} else {
		  	$hook=$wp_filter;
		  	ksort($hook);
		}
		
		foreach( $hook as $tag => $priority ){
		  	ksort( $priority );
		  	foreach( $priority as $priority => $function ){
		  		//echo $priority;
		  		foreach( $function as $name => $properties ) 
					$functions[$tag][$priority][$name]['priority'] = $priority;
					$functions[$tag][$priority][$name]['function'] = $properties['function'];
					$functions[$tag][$priority][$name]['args'] = $properties['accepted_args'];
		
		  	}
		}
		return $functions;
	}
}
?>