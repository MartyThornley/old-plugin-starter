<?php
/**
 * Base class for plugin admin functions.
 *
 * @package PluginStarter
 *
 * Extends Plugin_Starter to use all definitions and functions
 */
	// My_Plugin_Admin extends Plugin_Starter_admin

class Plugin_Starter_Admin {

	var $debug = 			false;	
	
	var $admin_path = 		'';
	var $admin_url = 		'';	
	var $settings_page = 	'';	
	
	var $sections = 		array();	
	var $settings = 		array();	
	var $default_settings = array();	
	var $plugin_data = 		array();	
	
	/**
	 * The constructor is executed when the class is instatiated and the plugin gets loaded.
	 * @return void
	 */	
	function __construct() {	 
		
		$this->definitions();

		add_action( 'admin_init', 				array( $this, 'admin_init' ) );
		add_action( 'admin_menu', 				array( $this, 'admin_menu' ) );
		
		add_action( 'admin_enqueue_scripts',	array( $this, 'admin_enqueue_scripts' ) );

		add_filter( 'admin_body_class',			array( $this, 'admin_body_class' ) );

		add_action( 'save_post', 				array( $this, 'save_post' ) );
		
		/* Our actions and filters */
		add_action( 'plugin_starter_settings_section' , array( $this, 'add_to_settings_section' ) );
	}

	/**
	 * Load admin definitions
	 * @return void
	 */
	function definitions() {

	}

	/**
	 * admin init
	 * @return void
	 */
	function admin_init() {

		$this->register_settings();
	
	}

	/**
	 * register_settings
	 * @return void
	 */
	function register_settings() {
	
		$this->sections = $this->define_sections();
		$this->settings = $this->define_settings();
		
		if ( !get_option( $this->domain . '_settings' ) )
			$this->initialize_settings();		
		
		$sections = $this->sections;
		
		$sections['about'] = __( 'About & Help', $this->domain );
		
		$this->sections = $sections;
		
		register_setting( $this->domain . '_settings', $this->domain . '_settings', array( $this, 'validate_settings' ) );
		
		foreach ( $this->sections as $slug => $title ) {
			if ( $slug != 'about' ) 
				add_settings_section( $slug, $title, array( $this, 'display_section' ), $this->domain );
			else 	
				add_settings_section( $slug, $title, array( $this, 'display_about_section' ), $this->domain );
				add_settings_field( 'about', 'About' , array( $this, 'display_about_section_settings' ), $this->domain, 'about' );
		}
				
		foreach ( $this->settings as $id => $setting ) {
			$setting['id'] = $id;
			$this->create_setting( $setting );
		}		
	}

	function define_sections() {

		$sections = array( 
			'general'		=> __( 'General Settings', $this->domain ),
			'appearance'	=> __( 'Appearance', $this->domain ),
			'reset'			=> __( 'Reset to Defaults', $this->domain ),
		);
		
		return $sections;	
	}
	
	/**
	 * Description for About section
	 *
	 * @since 1.0
	 */
	function display_about_section() {
		return;
	}
	
	function display_about_section_settings() {
		
		echo 'This is some filler text';
	
	}
	
	
	/**
	 * Display options page
	 *
	 * @since 1.0
	 */
	public function display_page() {
		
		$plugin_data = $this->plugin_data;
		?>
		
		<div class="wrap">
		
			<?php screen_icon(); ?>
	
			<h2><?php printf( __( '%1$s Settings', $this->domain ), $plugin_data['Name'] ); ?></h2>
	
			<div id="poststuff">
	
				<form method="post" action="options.php">
	
					<?php settings_fields( $this->domain . '_settings' ); ?>
                    
                    <div class="ui-tabs">
                    <ul class="ui-tabs-nav">
                    <?php foreach ( $this->sections as $section_slug => $section ) echo '<li><a href="#' . $section_slug . '">' . $section . '</a></li>'; ?>
                    </ul>
					
                    <div class="<?php echo $this->domain; ?>-wrapper">
						<?php do_settings_sections( $_GET['page'] ); ?>
                    </div>

                    </div>
	
					<?php submit_button( esc_attr__( 'Update Settings', $this->domain ) ); ?>
	
				</form>
            
            <?php 
				if ( $this->debug ) {
					print '<pre>'; print_r( get_option( $this->domain . '_settings' ) ); print '</pre>';
					global $wp_rewrite; print '<pre>'; print_r( $wp_rewrite ); print '</pre>';
				} ?>
	
			</div><!-- #poststuff -->
	
		</div><!-- .wrap --><?php
		
	}
		
	/**
	 * Initialize settings to their default values
	 * 
	 */
	public function initialize_settings() {
		
		$default_settings = array();
		
		if ( !empty( $this->settings ) ) {
			foreach ( $this->settings as $id => $setting ) {
				if ( $setting['type'] != 'heading' )
					$default_settings[$id] = $setting['std'];
			}
		}
		
		$this->default_settings = $default_settings;
		
		update_option( $this->domain . '_settings', $default_settings );		
	}
		
	/**
	 * Create settings field
	 *
	 * @since 1.0
	 */
	function create_setting( $args = array() ) {
		
		$defaults = array(
			'id'      => 'default_field',
			'title'   => __( 'Default Field', $this->domain ),
			'desc'    => __( 'This is a default description.', $this->domain ),
			'std'     => '',
			'type'    => 'text',
			'section' => 'general',
			'choices' => array(),
			'class'   => ''
		);
			
		extract( wp_parse_args( $args, $defaults ) );
		
		$field_args = array(
			'type'      => $type,
			'id'        => $id,
			'desc'      => $desc,
			'std'       => $std,
			'choices'   => $choices,
			'label_for' => $id,
			'class'     => $class
		);
		
		add_settings_field( $id, $title, array( $this, 'display_setting' ), $this->domain, $section, $field_args );
	}	
		
	/**
	 * Description for section
	 *
	 * @since 1.0
	 */
	public function display_section( $section ) {
		
		do_action( 'plugin_starter_settings_section' , $section );
	
		return;
	}	

	/**
	 * Additional info for section
	 *
	 * @since 1.0
	 */
	public function add_to_settings_section( $section ) {
		return;
	}	
		
	/**
	 * HTML output for text field
	 *
	 * @since 1.0
	 */
	public function display_setting( $args = array() ) {
		
		extract( $args );
		
		$options = get_option( $this->domain . '_settings' );
		
		if ( ! isset( $options[$id] ) )
			$options[$id] = $std;
		
		$field_class = '';
		if ( $class != '' )
			$field_class = ' ' . $class;
		
		switch ( $type ) {
			
			case 'heading':
				echo '<h4>' . $desc . '</h4>';
				break;
			
			case 'checkbox':
				
				echo '<input class="checkbox' . $field_class . '" type="checkbox" id="' . $id . '" name="' . $this->domain . '_settings[' . $id . ']" value="1" ' . checked( $options[$id], 1, false ) . ' /> <label for="' . $id . '">' . $desc . '</label>';
				
				break;
			
			case 'select':
				echo '<select class="select' . $field_class . '" name="' . $this->domain . '_settings[' . $id . ']">';
				
				if ( $desc != '' )
					echo '<span class="description">' . $desc . '</span><br />';				
				foreach ( $choices as $value => $label )
					echo '<option value="' . esc_attr( $value ) . '"' . selected( $options[$id], $value, false ) . '>' . $label . '</option>';
				
				echo '</select>';

				
				break;
			
			case 'radio':
				$i = 0;
				
				if ( $desc != '' )
					echo '<span class="description">' . $desc . '</span><br />';
					
				foreach ( $choices as $value => $label ) {
					echo '<input class="radio' . $field_class . '" type="radio" name="' . $this->domain . '_settings[' . $id . ']" id="' . $id . $i . '" value="' . esc_attr( $value ) . '" ' . checked( $options[$id], $value, false ) . '> <label for="' . $id . $i . '">' . $label . '</label>';
					if ( $i < count( $options ) - 1 )
						echo '<br />';
					$i++;
				}

				
				break;
			
			case 'textarea':
				if ( $desc != '' )
					echo '<span class="description">' . $desc . '</span><br />';
								
				echo '<textarea class="' . $field_class . '" id="' . $id . '" name="' . $this->domain . '_settings[' . $id . ']" placeholder="' . $std . '" rows="5" cols="30">' . wp_htmledit_pre( $options[$id] ) . '</textarea>';				
				break;
			
			case 'password':
				echo '<input class="regular-text' . $field_class . '" type="password" id="' . $id . '" name="' . $this->domain . '_settings[' . $id . ']" value="' . esc_attr( $options[$id] ) . '" />';
				
				if ( $desc != '' )
					echo '<br /><span class="description">' . $desc . '</span>';
				
				break;
				
			case 'file':
			
				echo '<input class="upload_file" type="file" size="45" id="' . $id . '" name="' . $this->domain . '_settings[' . $id . ']" value="' . esc_url( $options[$id] ) . '" />';
				echo '<input class="upload_file_id" type="hidden" id="' . $id . '_id" name="' . $this->domain . '_settings[' . $id . '_id]" value="' . $id . "_id" . '" />';					
				
				if ( $desc != '' )
					echo '<br /><span class="description">' . $desc . '</span>';
					
				echo '<div id="' . $id . '_status" class="clp_upload_status">';	
					if ( $options[$id] != '' ) { 
						$check_image = preg_match( '/(^.*\.jpg|jpeg|png|gif|ico*)/i', $id );
						if ( $check_image ) {
							echo '<div class="img_status">';
							echo '<img src="' . $id . '" alt="" />';
							echo '<a href="#" class="remove_file_button" rel="' . $id . '">Remove Image</a>';
							echo '</div>';
						} else {
							$parts = explode( "/", $id );
							for( $i = 0; $i < sizeof( $parts ); ++$i ) {
								$title = $parts[$i];
							} 
							echo 'File: <strong>' . $title . '</strong>&nbsp;&nbsp;&nbsp; (<a href="' . $id . '" target="_blank" rel="external">Download</a> / <a href="#" class="remove_file_button" rel="' . $id . '">Remove</a>)';
						}	
					}
				echo '</div>';
				
				break;
			
			case 'hidden':
		 		echo '<input class="regular-text' . $field_class . '" type="hidden" id="' . $id . '" name="' . $this->domain . '_settings[' . $id . ']" placeholder="' . $std . '" value="' . esc_attr( $options[$id] ) . '" />';
				
				break;
				
			case 'text':
		 		if ( $desc != '' )
		 			echo '<span class="description">' . $desc . '</span><br />';
		
		 		echo '<input class="regular-text' . $field_class . '" type="text" id="' . $id . '" name="' . $this->domain . '_settings[' . $id . ']" placeholder="' . $std . '" value="' . esc_attr( $options[$id] ) . '" />';
		 		
		 		break;
		
			case 'datepicker':
		 		if ( $desc != '' )
		 			echo '<span class="description">' . $desc . '</span><br />';
		
		 		echo '<input class="plugin-datepicker' . $field_class . '" type="text" id="' . $id . '" name="' . $this->domain . '_settings[' . $id . ']" placeholder="' . $std . '" value="' . esc_attr( $options[$id] ) . '" />';
		 		
		 		break;
		
		 	
			default: 		
		 		if ( $desc != '' )
		 			echo '<span class="description">' . $desc . '</span><br />';
		
		 		echo '<input class="regular-text' . $field_class . '" type="text" id="' . $id . '" name="' . $this->domain . '_settings[' . $id . ']" placeholder="' . $std . '" value="' . esc_attr( $options[$id] ) . '" />';
		 		
		 		break;
		}
		
	}
		
	/**
	 * Settings and defaults
	 * 
	 * @since 1.0
	 */
	public function define_settings() {
		
		// keep it from breaking
		$settings = array();
		
		/* example of how to define settings
		
		$settings['slug'] = array(
			'title'   => __( 'Prefix slug', $this->domain ),
			'desc'    => sprintf( __( 'You can change the slug prefix. ie. %s', $this->domain ), home_url( '/YOUR_CUSTOM_SLUG/your-custom-post-title' ) ),
			'std'     => '',
			'type'    => 'text', 'checkbox', 'heading', 'textarea', 'radio', 'file'
			'section' => 'general', 'appearance', 'etc',
			'class'   => 'code', 		// Custom class for CSS
			'choices' => array(			// for radio, or select
				'choice1' => 'Choice 1',
				'choice2' => 'Choice 2',
				'choice3' => 'Choice 3'
			)
		);
		*/
		
		return $settings;
	}
							
	/**
	 * admin_register_scripts
	 * @return void
	 */
	function admin_enqueue_scripts() {

		$SyntaxHighlighter = '3.0.83';
		
		/* register scripts */
		wp_register_script( 'shCore', 'http://alexgorbatchev.com/pub/sh/current/scripts/shCore.js', null, $SyntaxHighlighter, false );
		wp_register_script( 'shAutoloader', 'http://alexgorbatchev.com/pub/sh/current/scripts/shAutoloader.js', null, $SyntaxHighlighter, false );

		/* enqueue scripts */
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-tabs' );		
		wp_enqueue_script( 'thickbox' );

		/* register scripts */
		wp_register_style( 'shThemeDefault', 'http://alexgorbatchev.com/pub/sh/current/styles/shThemeDefault.css', false, $SyntaxHighlighter, 'screen' );

		/* enqueue scripts */		
		wp_enqueue_style( 'thickbox' );		
	
		if ( $this->settings_page != '' ) {
			add_action( 'admin_head-' . $this->settings_page,			array( $this, 'settings_page_scripts' ) );	
		}	
	}

	/**
	 * Add options pages and menu items
	 *
	 * @since 1.0
	 */
	public function admin_menu() {

	}
		
	/**
	 * add body class in admin
	 * if we find a post_type, we add that. Always adds a 'plugin_starter' class
	 *
	 * @return string
	 */
	function admin_body_class( $class ) {
		$screen = get_current_screen(); 
		$post_type = ( isset( $screen->post_type ) ) ? $screen->post_type : null;
		if ( isset( $post_type ) ) {
			$class .= $post_type;
			$class .= ' post_type-' . $post_type;
		}
		$class .= ' plugin_starter';

		return $class;
	}	

	/**
	 * jQuery Tabs
	 *
	 */
	function scripts() {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-tabs' );
		wp_enqueue_script( $this->domain . '-admin-js' );		
	}
	
	/**
	 * Styling for the theme options page
	 *
	 */
	function styles() {		
		wp_enqueue_style( $this->domain . '-admin-style' );	
	}
	
	/**
	 * Loads the JavaScript required for toggling the meta boxes on the plugin settings page.
	 *
	 */
	function settings_page_scripts() { 
		?>
		<script type="text/javascript">
			//<![CDATA[
			jQuery(document).ready(function($) {
				/* Tabs **/
				var sections = [];
				
				<?php foreach ( $this->sections as $section_slug => $section )
					echo "sections['$section'] = '$section_slug';"; ?>
				
				var wrapped = $('.wrap h3').wrap('<div class="ui-tabs-panel">');
				wrapped.each(function() {
					$(this).parent().append($(this).parent().nextUntil('div.ui-tabs-panel'));
				});
				$('.ui-tabs-panel').each(function(index) {
					$(this).prop('id', sections[$(this).children('h3').text()]);
					if (index > 0)
						$(this).addClass('ui-tabs-hide');
				});
				$('.ui-tabs').tabs({
					fx: { opacity: 'toggle', duration: 'fast' }
				});
				
				$('input[type="text"], textarea').each(function() {
					if ($(this).val() == $(this).prop('placeholder') || $(this).val() == '')
						$(this).css('color', '#999');
				});
				
				$('input[type="text"], textarea').focus(function() {
					if ($(this).val() == $(this).prop('placeholder') || $(this).val() == '') {
						$(this).val('');
						$(this).css('color', '#000');
					}
				}).blur(function() {
					if ($(this).val() == '' || $(this).val() == $(this).prop('placeholder')) {
						$(this).val($(this).prop('placeholder'));
						$(this).css('color', '#999');
					}
				});
				/* End Tabs **/
				
				$('#post').prop('enctype', 'multipart/form-data');
				$('#post').prop('encoding', 'multipart/form-data');
				
				$('.wrap h3, .wrap table').show();
				
				/**
				 * This will make the 'warning' checkbox class really stand out when checked.
				 * I use it here for the Reset checkbox.
				 */
				$('.warning').change(function() {
					if ($(this).is(':checked'))
						$(this).parent().css('background', '#c00').css('color', '#fff').css('fontWeight', 'bold');
					else
						$(this).parent().css('background', 'none').css('color', 'inherit').css('fontWeight', 'normal');
				});
				
				/* Browser compatibility //*/
				if ($.browser.mozilla) $('form').prop('autocomplete', 'off');
			});
			//]]>
		</script><?php
	}
	
	/**
	 * Allow us to save 
	 *
	 * @since 1.0
	 */
	function save_post() {

	}
	
	function validate_posted( $data_array ) {
	
		if ( is_array( $data_array ) ) {
		
			foreach( $data_array as $name => $data ) {
				
				// need to check if this is an array for repeatable options...
				
				$clean_data_array[$name]['name'] = $name;
				
				if ( isset( $data['type'] ) && isset( $data['data'] )) {
				
					switch( $data['type'] ) {
						
						case 'text' :
							$clean_data_array[$name]['data'] = esc_html( $data['data'] );
							break;
							
						case 'textarea' :
							$clean_data_array[$name]['data'] = esc_textarea( $data['data'] );
							break;					

						case 'html' :
							$clean_data_array[$name]['data'] = addslashes( $data['data'] );
							break;	

						case 'url' :
							$clean_data_array[$name]['data'] = esc_url( $data['data'] );
							break;	

						case 'checkbox' :
							$clean_data_array[$name]['data'] = esc_html( $data['data'] );
							break;		

						case 'select' :
							$clean_data_array[$name]['data'] = esc_html( $data['data'] );
							break;	
					
					}
				
				}
			
			}
			
		} else {
		
			$clean_data_array = array();
		
		}
		
		return $clean_data_array;
	
	}
		
}

?>