<?php

/**
 * Meta box class
 */

class Tabbed_Meta_Box {
	
	var $domain 		= '';
	var $plugin_path 	= '';
	var $plugin_url 	= '';
	var $post_type 		= '';
	var $sections 		= array();
		
	/**
	 * The constructor is executed when the class is instatiated and the plugin gets loaded.
	 * @return void
	 */	
	function __construct() {	 
		
		$this->define_sections();

		/* Add the post meta box creation function to the 'admin_menu' hook. */
		add_action( 'add_meta_boxes',		array( $this, 'add_meta_box' ) );
		add_action( 'admin_menu',			array( $this, 'add_meta_box' ) );
		add_action( 'admin_enqueue_scripts',array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_head',			array( $this, 'metabox_tabs_script' ) );
			
		/* Saves the post meta box data. */
		add_action( 'save_post', 			array( $this, 'save_meta_box' ) , 10, 2 );

		add_filter( 'media_upload_tabs' ,					array( $this , 'media_upload_tabs' ) , 11 );		
		add_filter( 'ps_uploads_send_to_editor_filter',		array( $this , 'no_send_to_editor_filter' ) , 0, 1 );
	}
	
	/*
	 * Tell media uploads not to go to editor when on our post type
	 */	
	function no_send_to_editor_filter( $editor ) {

		if ( get_transient( 'metabox_to_editor' ) != 'true' )
			$editor = false;

		return $editor;
	}
	
	function media_upload_tabs( $tabs ) {
		
		if ( isset( $_GET['editor'] ) && $_GET['editor'] == 'false' );
			set_transient( 'metabox_to_editor' , 'false' , 20 );
		
		return $tabs;
	}

	/**
	 * Defines the sections for metabox tabs
	 */
	function define_sections(){
	
		$sections = array (
			'general'		=> __( 'General Settings', $this->domain ),
		);
		
		$this->sections = $sections;
	}
	
	/**
	 * Creates a meta box on the experiments editing screen for allowing the easy input of 
	 * commonly-used post metadata.
	 *
	 * @uses add_meta_box() Adds a meta box to the post editing screen.
	 */
	function add_meta_box() {
		
		//add_meta_box( $this->domain . '-meta-box', __( 'Title', $this->domain ), array( $this, 'meta_box' ), $this->post_type, 'normal', 'high' );
				
	}
	
	/**
	 * Loads the required stylesheets for displaying the edit and post-new page in the WordPress admin.
	 *
	 */
	function enqueue_scripts() {
	}
	
	/**
	 * Creates the settings for the post meta box.  
	 *
	 * @param string $type The post type of the current post in the post editor.
	 */
	function meta_box_args( $type = '' ) {
		$meta = array();
	
		/* If no post type is given, default to '$this->post_type'. */
		if ( empty( $type ) )
			$type = $this->post_type;
			
		$prefix = $this->domain . '_';
		
		/* How to add new fields...
		
		$meta['YOUR_SETTING_NAME'] = array( 
				'name' => 'Text name',
				'title' => __( 'A Good Title', $this->domain ), 			
				'type' => can be [ 'paragraph' ], [ 'text' ], [ 'text', 'maxlength' => '2' ], [ 'textarea' ], [ 'checkbox', 'options' => array( 'true' => true ) ] ,
				'description' => __( 'String description', $this->domain ),
				'tab' => 'general' ( which tab defined in $this-sections should it go in? ) 
		);
		
		*/
		
		return $meta;
	}
	
	/**
	 * Displays the post meta box on the edit post page. The function gets the various metadata elements
	 * from the meta_box_args() function. It then loops through each item in the array and
	 * displays a form element based on the type of setting it should be.
	 *
	 * @parameter object $object Post object that holds all the post information.
	 * @parameter array $box The particular meta box being shown and its information.
	 */
	function meta_box( $object, $box ) {
	
		$meta_box_options = $this->meta_box_args( $object->post_type ); ?>
		
		<div class="ui-tabs">

            <ul class="ui-tabs-nav">
            <?php /* Tabs */
            foreach ( $this->sections as $section_slug => $section ) {
                 print '<li><a href="#' . $section_slug . '">' . $section . '</a></li>';
            } ?>
            </ul>

        <?php /* Settings */
		foreach ( $this->sections as $section_slug => $section ) {
			echo '<div id="' . $section_slug . '">';			
			foreach ( $meta_box_options as $option ) {
				if ( method_exists( 'Tabbed_Meta_Box', "meta_box_{$option['type']}" ) ) {
					if ( $section_slug == $option['tab'] ) {
						echo '<div class="tabbed_option" style="display:block; clear: both; margin: 0 0 15px 0;">';
						call_user_func( array( $this, "meta_box_{$option['type']}" ), $option, get_post_meta( $object->ID, $option['name'], true ) );
						if ($option['repeatable']) echo '<a class="repeat" rel="1">Add Another</a>';
						echo '</div>';
					}
				}
			}
			echo '</div>';
		} ?>
		<input type="hidden" name="<?php echo "{$object->post_type}_meta_box_nonce"; ?>" value="<?php echo wp_create_nonce( basename( __FILE__ ) ); ?>" />
		</div><?php
	}
	
	/**
	 * Outputs a text input box with the given arguments for use with the post meta box.
	 *
	 * @param array $args 
	 * @param string|bool $value Custom field value.
	 */
	public function meta_box_text( $args = array(), $value = false ) {
		$name = preg_replace( "/[^A-Za-z_-]/", '-', $args['name'] ); ?>
		<p>
			<label for="<?php echo $name; ?>"><?php echo $args['title']; ?></label>
			<br />
			<input type="text" name="<?php echo $name; ?>" id="<?php echo $name; ?>" value="<?php echo esc_attr( $value ); ?>" size="30" tabindex="30" style="width: <?php echo ( !empty( $args['width'] ) ? $args['width'] : '99%' ); ?>;"<?php echo ( !empty( $args['maxlength'] ) ? 'maxlength="' . $args['maxlength'] . '"' : '' ); ?> />
			<?php if ( !empty( $args['description'] ) ) echo '<br /><span class="howto">' . $args['description'] . '</span>'; ?>
		</p>
		<?php
	}

	public function meta_box_file( $args = array(), $value = false ) {
		global $post;
		$name = preg_replace( "/[^A-Za-z_-]/", '-', $args['name'] );
		
		if ( $args['repeatable'] )
			$name = $name.'[1]';
		?>
		
		<p class="option-wrapper" rel="<?php echo $name; ?>">
			<label for="<?php echo $name; ?>"><?php echo $args['title']; ?></label>
			<br />
			<input type="file" name="<?php echo $name; ?>" id="upload_<?php echo $name; ?>" title="Add an Image" />
			<?php if ( !empty( $args['description'] ) ) echo '<br /><span class="howto">' . $args['description'] . '</span>'; ?>
		</p>
		<?php
	}

	public function meta_box_gallery_images( $args = array(), $value = false ) {
		global $post;
		$name = preg_replace( "/[^A-Za-z_-]/", '-', $args['name'] ); ?>
		<p>
			<label for="<?php echo $name; ?>"><?php echo $args['title']; ?></label>
			<br />
			<a href="media-upload.php?post_id=<?php echo $post->ID; ?>&amp;type=image&amp;TB_iframe=1&amp;width=640&amp;height=585&editor=<?php echo $args['editor']; ?>" id="upload_gallery" class="thickbox button-secondary metatab-file" title="Add an Image">Select Files</a>
			<?php if ( !empty( $args['description'] ) ) echo '<br /><span class="howto">' . $args['description'] . '</span>'; ?>
		</p>
		<?php
	}
	
	/**
	 * Outputs a text input box with the given arguments for use with the post meta box.
	 *
	 * @param array $args 
	 * @param string|bool $value Custom field value.
	 */
	public function meta_box_datepicker( $args = array(), $value = false ) {
		$name = preg_replace( "/[^A-Za-z_-]/", '-', $args['name'] ); ?>
		<p>
			<label for="<?php echo $name; ?>"><?php echo $args['title']; ?></label>
			<br />
			<input class="plugin-datepicker" type="text" name="<?php echo $name; ?>" id="<?php echo $name; ?>" value="<?php echo esc_attr( $value ); ?>" size="30" tabindex="30" style="width: <?php echo ( !empty( $args['width'] ) ? $args['width'] : '99%' ); ?>;"<?php echo ( !empty( $args['maxlength'] ) ? 'maxlength="' . $args['maxlength'] . '"' : '' ); ?> />
			<?php if ( !empty( $args['description'] ) ) echo '<br /><span class="howto">' . $args['description'] . '</span>'; ?>
		</p>
		<?php
	}
		
	/**
	 * Outputs a select box with the given arguments for use with the post meta box.
	 *
	 * @param array $args
	 * @param string|bool $value Custom field value.
	 */
	public function meta_box_select( $args = array(), $value = false ) {
		$name = preg_replace( "/[^A-Za-z_-]/", '-', $args['name'] ); ?>
		<p>
			<label for="<?php echo $name; ?>"><?php echo $args['title']; ?></label>
			<?php if ( !empty( $args['description'] ) ) echo '<br /><span class="howto">' . $args['description'] . '</span>'; ?>
			
			<?php if ( !empty( $args['sep'] ) ) echo '<br />'; ?>
			<select name="<?php echo $name; ?>" id="<?php echo $name; ?>" style="width:60px">
				<?php // echo '<option value=""></option>'; ?>
				<?php $i = 0; foreach ( $args['options'] as $option => $val ) { $i++; ?>
					<option value="<?php echo esc_attr( $val ); ?>" <?php selected( esc_attr( $value ), esc_attr( $val ) ); //if ( $i == 1 ) echo 'selected="selected"'; ?>><?php echo ( !empty( $args['use_key_and_value'] ) ? $option : $val ); ?></option>
				<?php } ?>
			</select>
		</p>
		<?php
	}

	/**
	 * Outputs a textarea with the given arguments for use with the post meta box.
	 *
	 * @param array $args
	 * @param string|bool $value Custom field value.
	 */
	public function meta_box_textarea( $args = array(), $value = false ) {
		$name = preg_replace( "/[^A-Za-z_-]/", '-', $args['name'] ); ?>
		<p>
			<label for="<?php echo $name; ?>"><?php echo $args['title']; ?></label>
			<br />
			<textarea name="<?php echo $name; ?>" id="<?php echo $name; ?>" cols="60" rows="2" tabindex="30" style="width: 99%;"><?php echo esc_html( $value ); ?></textarea>
			<?php if ( !empty( $args['description'] ) ) echo '<br /><span class="howto">' . $args['description'] . '</span>'; ?>
		</p>
		<?php
	}

	/**
	 * Outputs a textarea with the given arguments for use with the post meta box.
	 *
	 * @param array $args
	 * @param string|bool $value Custom field value.
	 */
	public function meta_box_html( $args = array(), $value = false ) {
		$name = preg_replace( "/[^A-Za-z_-]/", '-', $args['name'] ); ?>
		<p>
			<label for="<?php echo $name; ?>"><?php echo $args['title']; ?></label>
			<br />
			<textarea class="htmlarea" name="<?php echo $name; ?>" id="<?php echo $name; ?>" cols="60" rows="2" tabindex="30" style="width: 99%;"><?php echo stripslashes( $value ); ?></textarea>
			<?php if ( !empty( $args['description'] ) ) echo '<br /><span class="howto">' . $args['description'] . '</span>'; ?>
		</p>
		<?php
	}
		
	/**
	 * Outputs checkbox inputs with the given arguments for use with the meta box.
	 *
	 * @param array $args
	 * @param string|bool $value Custom field value.
	 */
	public function meta_box_checkbox( $args = array(), $value = false ) {
		$name  = preg_replace( "/[^A-Za-z_-]/", '-', $args['name'] ); ?>
		<p>
			<?php echo $args['title']; ?>
			<?php if ( !empty( $args['description'] ) ) echo '<br /><span class="howto">' . $args['description'] . '</span>'; ?>

			<?php foreach ( $args['options'] as $option => $val ) { ?>
				<span style="margin: 5px 14px 5px 0; float: left; display: inline;">
				<input type="checkbox" name="<?php echo $name; ?>" value="<?php echo esc_attr( $val ); ?>" <?php checked( esc_attr( $value ), esc_attr( $val ) ); ?> />
				<label for="<?php echo $name; ?>"><?php echo $option; ?></label>
				</span>
			<?php } ?>
		</p>
		<?php
	}
	
	/**
	 * Outputs radio inputs with the given arguments for use with the meta box.
	 *
	 * @param array $args
	 * @param string|bool $value Custom field value.
	 */
	public function meta_box_radio( $args = array(), $value = false ) {
		$name  = preg_replace( "/[^A-Za-z_-]/", '-', $args['name'] ); ?>
		<p>
			<?php echo $args['title']; ?>
			<?php if ( !empty( $args['description'] ) ) echo '<br /><span class="howto">' . $args['description'] . '</span>'; ?>

			<?php foreach ( $args['options'] as $option => $val ) { ?>
				<br />
				<label for="<?php echo $name; ?>"><?php echo $val; ?></label>
				<input type="radio" name="<?php echo $name; ?>" value="<?php echo esc_attr( $val ); ?>" <?php checked( esc_attr( $value ), esc_attr( $val ) ); ?> />
			<?php } ?>
		</p>
		<?php
	}
	
	/**
	 * Outputs text with the given arguments for use with the meta box.
	 *
	 * @param array $args
	 */
	public function meta_box_paragraph( $args = array(), $value = false ) { ?>
		<h4 class="title"><?php echo $args['title']; ?></h4>
		<?php if ( !empty( $args['description'] ) ) echo '<p>' . $args['description'] . '</p>'; ?>
		<?php
	}
	
	/**
	 * The function for saving the theme's post meta box settings. It loops through each of the meta box 
	 * arguments for that particular post type and adds, updates, or deletes the metadata.
	 *
	 * @param int $post_id
	 */
	function save_meta_box( $post_id, $post ) {
	
		/* Verify that the post type supports the meta box and the nonce before preceding. */
		if ( !isset( $_POST["{$post->post_type}_meta_box_nonce"] ) || !wp_verify_nonce( $_POST["{$post->post_type}_meta_box_nonce"], basename( __FILE__ ) ) )
			return $post_id;
	
		/* Get the post type object. */
		$post_type = get_post_type_object( $post->post_type );
	
		/* Check if the current user has permission to edit the post. */
		if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
			return $post_id;
	
		/* Get the post meta box arguments. */
		$metadata = $this->meta_box_args( $_POST['post_type'] );
		
		// need to edit this to take arrays for repeatable...

		foreach ( $metadata as $meta ) {
			
			$name = $meta['name'];
			
			// if name is an array, need to loop through?
			
			$posted[$name]['name'] = $name;
			$posted[$name]['data'] = $_POST[ preg_replace( "/[^A-Za-z_-]/", '-', $name ) ];
			$posted[$name]['type'] = $meta['type'];
					
		}
		
		$metadata = Plugin_Starter_Admin::validate_posted( $posted );
		
		// for testing...
		//update_option( $this->domain . '_testing_meta' , $metadata );
	
		
		/* Loop through all of post meta box arguments. */
		foreach ( $metadata as $name => $meta ) {
	
			$meta_value = get_post_meta( $post_id, $name, true );
			$new_meta_value = stripslashes( $_POST[ preg_replace( "/[^A-Za-z_-]/", '-', $meta['name'] ) ] );
	
			if ( $new_meta_value && '' == $meta_value )
				add_post_meta( $post_id, $meta['name'], $new_meta_value, true );
			elseif ( $new_meta_value && $new_meta_value != $meta_value )
				update_post_meta( $post_id, $meta['name'], $new_meta_value );
			elseif ( '' == $new_meta_value && $meta_value )
				delete_post_meta( $post_id, $meta['name'], $meta_value );
		}
	}
	

		
	/**
	 * Loads the JavaScript required for toggling the meta boxes on the plugin settings page.
	 *
	 */
	function metabox_tabs_script() {
		global $pagenow;
		
		if ( $pagenow == ( 'post.php' || 'post-new.php' ) && $this->post_type == get_post_type() ) { ?>

			<script type="text/javascript">
				//<![CDATA[
				jQuery(document).ready(function($) {
					/* Tabs **/
					var sections = [];
					
					<?php foreach ( $this->sections as $section_slug => $section )
						echo "sections['$section'] = '$section_slug';"; ?>
					
					var wrapped = $('#<?php echo $this->domain; ?>-meta-box .inside').wrap('<div class="ui-tabs-panel">');
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
					/* End Tabs **/
					$('.tabbed_option a.repeat').click( function() {
						current_id = $(this).prop('rel');
						next_id = parseInt(current_id)+1;
						$prev_div = $(this).parent().children('.option-wrapper').contents();
						$(this).before( 'stuff' );
						$(this).prop('rel', next_id);
					});
				});
				//]]>
			</script>
			
			<style type="text/css">
			
				a.metatab-file { padding: 2px 4px; border: 1px solid #e5e5e5; margin: 4px; }
			</style>		
		
		<?php
		}
	}

}
