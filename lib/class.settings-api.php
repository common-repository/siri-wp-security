<?php
/*
 * 
 * @package     Secure Login
 * Copyright (C) 2013  Siri Iinnovations
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 *
 */
class PP_Settings_API {

    /**
     * settings sections array
     *
     * @var array
     */
    private $settings_sections = array();

    /**
     * Settings fields array
     *
     * @var array
     */
    private $settings_fields = array();

    /**
     * Settings fields array
     *
     * @var array
     */
    private $settings_menu = array();
    
    /**
     * Singleton instance
     *
     * @var object
     */
    private $domain;
    private static $_instance;
    

    public function __construct($fields, $sections, $menu='') {
        
        //set sections and fields
        //if (!is_admin())
        //    return;
            
        $this->set_sections( $sections );
        $this->set_fields( $fields );
        
        if ($menu)  {
            $this->set_menu($menu);
            add_action( 'admin_menu',  array(&$this, 'register_menu') );  
        }
        
        
        add_action( 'init',  array(&$this, 'filter_settings') );  
        
        //$this->admin_init();
        //$this->register_menu();
        
        if ($this->settings_menu['action_link'])
            add_filter( 'plugin_action_links_'.$this->settings_menu['plugin_file'], array(&$this, 'plugin_actions_links'), -10);
                                                                    
        add_action( 'admin_init', array(&$this, 'admin_init') );
        
    
	
    }
     

    public function register_menu() {
        $role= ($this->settings_menu['role']) ? $this->settings_menu['role'] :  'manage_options';
        add_options_page( $this->settings_menu['title'],  $this->settings_menu['title'], $role, $this->settings_menu['name'], array(&$this, 'render_option_page') );
    }
    
    public function filter_settings(){
        
        if (is_admin()  && isset($_POST['action']) && $_POST['action']=='update' && isset($_POST['option_page']) && $_POST['option_page']==$this->settings_menu['name']) {
           
            do_action('pp_settings_api_filter', $_POST);
            
            if (isset($_POST['import_field']) && $_POST['import_field'] && check_admin_referer( $this->settings_menu['name'] . '-options' ) ) {
                //delete_option( $this->settings_menu['name'] );
                $new_settings = stripslashes($_POST['import_field']);
                update_option($this->settings_menu['name'], json_decode($new_settings, true));
                
                // 	if ( !count( get_settings_errors() ) )
                //	add_settings_error('general', 'settings_imported', __('Settings Imported.'), 'updated');
                //	set_transient('settings_errors', get_settings_errors(), 30);
            
            	/**
            	 * Redirect back to the settings page that was submitted
            	 */
                 $goback = add_query_arg( array('settings-imported'=>'true'),  wp_get_referer() );
                 wp_redirect( $goback );
       	         exit;
            	
                
            }
            //check to see if the options were reset
            if ( isset ( $_POST['reset-defaults'] ) && check_admin_referer( $this->settings_menu['name'] . '-options' )) {
                
               // foreach ($this->settings_sections as $section)
               delete_option( $this->settings_menu['name'] );
               $this->save_defaults();
                    
               $goback = add_query_arg( array('settings-reseted'=>'true'),  wp_get_referer() );
               wp_redirect( $goback );
       	       exit;
            }
        }
    }
    
    /**
     * Display the plugin settings options page
     */
    public function render_option_page() {
        
        if ($this->settings_menu['template_file']) {
            include_once($this->settings_menu['template_file']);
            
        }else {  
           
            echo '<div class="wrap settings_api_class_page" id="'.$this->settings_menu['name'].'_settings" >';
            $icon='';
            if (isset($this->settings_menu['icon_path']) && $this->settings_menu['icon_path'])
                $icon= ' style="background: url('.$this->settings_menu['icon_path'].') no-repeat ;" ';
                
            //echo '<div id="icon-options"><br /></div>';
            
            //echo '<div><h2>'. $this->settings_menu['title'] .'</h2></div>';
            
            //echo '<br />';
            //settings_errors();
            if (isset($_GET['settings-reseted']) && $_GET['settings-reseted'])
                echo '<div class="updated fade" style="float: left; margin: -90px 0px 0px 473px;"><p><strong>'.__('Settings was reseted successfully!').'</p></strong></div>' ;
                
            if (isset($_GET['settings-imported']) && $_GET['settings-imported'])
                echo '<div class="updated fade" style="float: left; margin: -90px 0px 0px 473px;"><p><strong>'.__('Settings was imported successfully!').'</p></strong></div>';
            
            do_action('pp_settings_api_header', $this->settings_menu);
            

            $this->show_navigation();
            $this->show_forms();  
        
            do_action('pp_settings_api_footer', $this->settings_menu);
        
            echo '</div>';
        }
    }
    public function plugin_actions_links($links) {
        if ($this->settings_menu['action_link'])
            $links[] = '<a href="'.admin_url("options-general.php?page=".$this->settings_menu['name']).'" >'.
            $this->settings_menu['action_link'].'</a>';
          return $links;
    }
    
    public static function getInstance() {
        if ( !self::$_instance ) {
            self::$_instance = new WeDevs_Settings_API();
        }

        return self::$_instance;
    }

    /**
     * Set settings sections
     *
     * @param array $sections setting sections array
     */
    function set_sections( $sections ) {
        $this->settings_sections = $sections;
    }

    /**
     * Set settings fields
     *
     * @param array $fields settings fields array
     */
    function set_fields( $fields ) {
        $this->settings_fields = $fields;
    }

    /**
     * Set settings fields
     *
     * @param array $fields settings fields array
     */
    function set_menu( $menu ) {
        $this->settings_menu = $menu;
    }

    /**
     * Initialize and registers the settings sections and fileds to WordPress
     *
     * Usually this should be called at `admin_init` hook.
     *
     * This function gets the initiated settings sections and fields. Then
     * registers them to WordPress and ready for use.
     */
    public function admin_init() {
        //Disable Drag
        if (isset($_GET['page']) && $_GET['page']==$this->settings_menu['name'])
            wp_deregister_script('postbox');
        
        if ( ! get_option( $this->settings_menu['name'] ) )
            $this->save_defaults(); 
            //add_option( $this->settings_menu['name'] );
        
            
        //register settings sections
        foreach ($this->settings_sections as $section) {
            

            add_settings_section( $section['id'], $section['title'], '__return_false', $this->settings_menu['name'] );
        }

        //register settings fields
        foreach ($this->settings_fields as $section => $field) {
            foreach ($field as $option) {
                $args = array(
                    'id' => $option['name'],
                    'desc' => $option['desc'],
                    'name' => $option['label'],
                    'section' => $section,
                    'class' => isset( $option['class'] ) ? $option['class'] : null,
                    'options' => isset( $option['options'] ) ? $option['options'] : '',
                    'std' => isset( $option['default'] ) ? $option['default'] : ''
                );
                add_settings_field( $option['name'] , $option['label'], array($this, 'callback_' . $option['type']), $this->settings_menu['name'], $section, $args );  
            }
        }

        // creates our settings in the options table
        //foreach ($this->settings_sections as $section) {
            register_setting( $this->settings_menu['name'], $this->settings_menu['name'], array (&$this, 'admin_settings_validate') );
        //}
        
        
       
    }
    // validate our settings
    
    function admin_settings_validate($input) {
        
         do_action('pp_settings_api_validate', $input);
         
        
        
//      if (empty($input['sample_text'])) {
//
//                add_settings_error(
//                    'sample_text',           // setting title
//                    'sample_text_error',            // error ID
//                    'Please enter some sample text',   // error message
//                    'error'                        // type of message
//                );
//
//       }
         return $input;
    }
    
    function callback_colorpicker( $args ) {
		
        $value	 = esc_attr( $this->get_option( $args['id'], $this->settings_menu['name'], $args['std'] ) );
		$check	 = esc_attr( $this->get_option( $args['id'] . '_checkbox', $this->settings_menu['name'], $args['std'] ) );
        $opacity = esc_attr( $this->get_option( $args['id'] . '_opacity', $this->settings_menu['name'], $args['std'] ) );
        $size	 = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'small';
		$opaque_options = array( '1', '0.9', '0.8', '0.7', '0.6', '0.5', '0.4', '0.3', '0.2', '0.1', '0', );
		
		/* Color */
        $html  = sprintf( '<input type="text" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s" style="float:left"/>', $size, $this->settings_menu['name'], $args['id'], $value );
		
		/* Allow Opacity */
		$html .= '<div class="checkbox-wrap">';
        $html .= sprintf( '<input type="hidden" name="%1$s[%2$s]" value="off" />', $this->settings_menu['name'], $args['id'] . '_checkbox' );
        $html .= sprintf( '<input type="checkbox" class="checkbox" id="%1$s[%2$s]" name="%1$s[%2$s]" value="on"%4$s />', $this->settings_menu['name'], $args['id'] . '_checkbox', $check, checked( $check, 'on', false ) );
        $html .= sprintf( __( '<label for="%1$s[%2$s]">Opacity</label>', $this->domain ), $this->settings_menu['name'], $args['id'] . '_checkbox' );
        $html .= '</div>';
		
		/* Opacity */
       // $html .= sprintf( '<input type="text" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s" style="margin-left:70px;%5$s" />', $size, $args['section'], $args['id'] . '_opacity', $opacity, ( 'on' !== $check ? 'display:none;' : '' ) );
	   $html .= sprintf( '<select class="%1$s%4$s" name="%2$s[%3$s]" id="%2$s[%3$s]" style="margin-left:70px;">', $size, $this->settings_menu['name'], $args['id'] . '_opacity', ( 'on' !== $check ? ' hidden' : '' ) );
        foreach ( $opaque_options as $key ) {
            $html .= sprintf( '<option value="%s"%s>%s</option>', $key, selected( $opacity, $key, false ), $key );
        }
        $html .= sprintf( '</select>' );
		
		ob_start(); ?>
        <script>
		jQuery(document).ready(function($) {
			var myOptions = {
				// you can declare a default color here,
				// or in the data-default-color attribute on the input
				defaultColor: false,
				// a callback to fire whenever the color changes to a valid color
				change: function(event, ui){},
				// a callback to fire when the input is emptied or an invalid color
				clear: function() {},
				// hide the color picker controls on load
				hide: true,
				// show a group of common colors beneath the square
				// or, supply an array of colors to customize further
				palettes: true
			};
			$('input[name="<?php echo $this->settings_menu['name'] . '[' . $args['id'] . ']'; ?>"]').wpColorPicker();
		   
		    $('select[name="<?php echo $this->settings_menu['name'] . '[' . $args['id'] . '_opacity]'; ?>"]').chosen();
			if ( $('select[name="<?php echo $this->settings_menu['name'] . '[' . $args['id'] . '_opacity]'; ?>"]').hasClass('hidden') ) {
		    	$('#<?php echo str_replace( '[', '_', $this->settings_menu['name'] . '[' . $args['id'] . '_opacity' ); ?>__chzn').hide();
			}
			
		    $('input[name="<?php echo $this->settings_menu['name'] . '[' . $args['id'] . '_checkbox]'; ?>"]').on('change', function() {
		    	//$('select[name="<?php echo $this->settings_menu['name'] . '[' . $args['id'] . '_opacity]'; ?>"]').toggle();
		    	$('#<?php echo str_replace( '[', '_', $this->settings_menu['name'] . '[' . $args['id'] . '_opacity' ); ?>__chzn').toggle();
			});
		});
		</script><?php
		$html .= ob_get_clean();
		
		/* Description */
        $html .= sprintf( '<span class="description"> %s</span>', $args['desc'] );

        echo $html;
    }
    function callback_file( $args ) {
		static $counter = 0;
		
        $value = esc_attr( $this->get_option( $args['id'], $this->settings_menu['name'], $args['std'] ) );
        $size = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';
        $id = $this->settings_menu['name']  . '[' . $args['id'] . ']';
        $html = sprintf( '<input type="text" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>', $size, $this->settings_menu['name'], $args['id'], $value );
        $html .= '<input type="button" class="button extendd-browse" id="'. $id .'_button" value="Browse" style="margin-left:5px" />';
        $html .= '<input type="button" class="button extendd-clear" id="'. $id .'_clear" value="Clear" style="margin-left:5px" />';
		if ( 0 == $counter ) {
			ob_start(); ?>
			<script>
			jQuery(document).ready(function($) {			
				// WP 3.5+ uploader
				var file_frame;
				var wp_media_post_id = wp.media.model.settings.post.id; // Store the old id
				var set_to_post_id = <?php echo isset( $args['page_id'] ) ? $args['page_id'] : '0'; ?>; // Set this
				window.formfield = '';
				
				$(document.body).on('click', 'input[type="button"].button.extendd-browse', function(e) {
		
					e.preventDefault();
		
					var button = $(this);
					
					window.formfield = $(this).closest('td');
		
					// If the media frame already exists, reopen it.
					if ( file_frame ) {
						file_frame.uploader.uploader.param( 'post_id', set_to_post_id );
						file_frame.open();
						return;
					} else {
						// Set the wp.media post id so the uploader grabs the ID we want when initialised
						wp.media.model.settings.post.id = set_to_post_id;
					}
		
					// Create the media frame.
					file_frame = wp.media.frames.file_frame = wp.media({
						frame: 'post',
						state: 'insert',
						title: button.data( 'uploader_title' ),
						button: {
							text: button.data( 'uploader_button_text' ),
						},
						library: {
							type: 'image',
						},
						multiple: false  // Set to true to allow multiple files to be selected
					});
		
					file_frame.on( 'menu:render:default', function(view) {
						// Store our views in an object.
						var views = {};
		
						// Unset default menu items
						view.unset('library-separator');
						view.unset('gallery');
						view.unset('featured-image');
						view.unset('embed');
		
						// Initialize the views in our view object.
						view.set(views);
					});
		
					// When an image is selected, run a callback.
					file_frame.on( 'insert', function() {
		
						var attachment = file_frame.state().get('selection').first().toJSON();
					//	console.log(attachment);
						window.formfield.find('input[type="text"]').val(attachment.url);
					//	window.formfield.find('').val(attachment.title);
					});
		
					// Finally, open the modal
					file_frame.open();
				});
				
				// WP 3.5+ uploader
				var file_frame;
				window.formfield = ''; 
				
				$('input[type="button"].button.extendd-clear').on('click', function(e) {  
					e.preventDefault();		
					var $this = $(this);
					$this.closest('td').find('input[type="text"]').val('');
				});
			});
			</script><?php
			$html .= ob_get_clean();
		}
        $html .= sprintf( '<br><span class="description"> %s</span>', $args['desc'] );

        echo $html;
		$counter++;
    }
    /**
     * Displays a text field for a settings field
     *
     * @param array $args settings field args
     */
    function callback_text( $args ) {

        $value = esc_attr( $this->get_option( $args['id'], $this->settings_menu['name'] ) );
       // $class = isset( $args['class'] ) && !is_null( $args['class'] ) ? $args['class'] : 'regular';
        if($args['id']=='post_base')
        {
        if($value=='/%postname%/' || $value=='%postname%' || $value=='/%postname%' || $value=='%postname%/')
        {
            $value='/%year%/%postname%/';
        }
        }
        if($args['id']=='page_base')
        {
         if($value=='')
         {
             $value='/page';
         }
        }
        $html = sprintf( '<input type="text" class="regular-text %1$s" id="%4$s" name="%2$s[%4$s]" value="%5$s"/>', $args['class'], $this->settings_menu['name'], $args['section'],$args['id'], $value );
        if($args['id']=='admin_key'){$html .= sprintf('<span style="font-size: 14px;" class="">'.$args['desc'].'</span>');}
        else{
        $html .= sprintf( '<div class="hovertootip"><img class="helpicon" src="'.plugins_url().'/siri-wp-security/img/Help-icon.png">
            <div id="tool1"></div> <div class="tooltip"><span id="adminkey" class="description">'.$args['desc'].'</span></div></div>' );
        }
        echo $html;
    }


    function callback_html( $args ) {
              echo '</td></tr><tr valign="top"><td colspan="2"><div class="'.$args['class'].'">' . $args['desc'].'</span>';
    }
  
    function callback_wp_editor($args) {
       // $value = esc_attr( $this->get_option( $args['id'], $this->settings_menu['name'], $args['std'] ) );
        
        $value =  $this->get_option( $args['id'], $this->settings_menu['name'] ) ;
         
        echo wp_editor( $value, $this->settings_menu['name'].'_'.$args['id'] , array( 'textarea_name' => $this->settings_menu['name'] . '[' . $args['id'] . ']', 'textarea_rows' => '5','wpautop'=>false, 'dfw' => false, 'media_buttons' => true, 'quicktags' => true, 'tinymce' => true,'editor_class'=> $args['class'], 'teeny' => false  ) );
        echo sprintf( '<span class="description"> %s</span>', $args['desc'] );
    }
    
    function callback_file1($args){
        $value = esc_attr( $this->get_option( $args['id'], $this->settings_menu['name'] ) );
        
        $html = sprintf( '<input type="text" class="regular-text image-upload-url %1$s" id="%3$s" name="%2$s[%3$s]" value="%4$s" />', $args['class'], $this->settings_menu['name'], $args['id'], $value );
        $html .= sprintf( '<input id="st_upload_button" class="image-upload-button" type="button" name="upload_button" value="%s" />', __('Select', $this->settings_menu['name']) );
              
        $html .= sprintf( '<span class="description"> %s</span>', $args['desc'] );
        
        echo $html;
    }
    
    /**
     * Displays a checkbox for a settings field
     *
     * @param array $args settings field args
     */
    function callback_checkbox( $args ) {

        $value = esc_attr( $this->get_option( $args['id'], $this->settings_menu['name'] ) );
        $html  = '<div class="checkbox-wrap">';
        $html .= sprintf( '<input type="hidden" name="%1$s[%2$s]" value="off" />', $this->settings_menu['name'], $args['id'] );
        $html .= sprintf( '<input type="checkbox" class="checkbox %1$s" id="%3$s" name="%2$s[%3$s]" value="on"%4$s/>', $args['class'],  $this->settings_menu['name'], $args['id'], checked( 'on', $value, false ) );
        $html .= sprintf( '<label for="%2$s"> %3$s</label>', $this->settings_menu['name'], $args['id'],'');
        $html .= '</div>';
         if($args['desc']!=''){
        $html .= sprintf( '<div style="left: 10px !important; top: -3px !important;" class="hovertootip"><img class="helpicon" src="'.plugins_url().'/siri-wp-security/img/Help-icon.png">
            <div id="tool1" style="left: 50px !important;"></div> <div style=" left: 59px !important;" class="tooltip"><span class="description">'.$args['desc'].'</span></div></div>');
                }
        echo $html;
    }
 
    /**
     * Displays a multicheckbox a settings field
     *
     * @param array $args settings field args
     */
    function callback_multicheck( $args ) {

        $value = $this->get_option( $args['id'], $this->settings_menu['name'] );

        if (!$args['options'])
            return;
        //option name should not be 0 to work correctly with empty option    
        $html = '';
        foreach ($args['options'] as $key => $label) {
            $checked = isset( $value[$key] ) ? $value[$key] : '0';
            $html .= sprintf( '<input type="checkbox" class="checkbox %1$s" id="%3$s_%4$s" name="%2$s[%3$s][%4$s]" value="%4$s"%5$s />',$args['class'], $this->settings_menu['name'], $args['id'], $key, checked( $checked, $key, false ) );
            $html .= sprintf( '<label for="%2$s_%4$s"> %3$s</label><br>',$this->settings_menu['name'], $args['id'], $label, $key );
        }
        $html .= sprintf( '<span class="description"> %s</label>', $args['desc'] );
        
        echo $html;
    }

    /**
     * Displays a multicheckbox a settings field
     *
     * @param array $args settings field args
     */
    function callback_radio( $args ) {

        $value = $this->get_option( $args['id'], $this->settings_menu['name'] );

        $html = '';
        foreach ($args['options'] as $key => $label) {
            $html .= sprintf( '<input type="radio" class="radio %1$s" id="%3$s_%4$s" name="%2$s[%3$s]" value="%4$s"%5$s />', $args['class'], $this->settings_menu['name'], $args['id'], $key, checked( $value, $key, false ) );
            $html .= sprintf( '<label for="%2$s_%4$s" style="padding: 0px 26px 0px 0px;"> %3$s</label>', $this->settings_menu['name'], $args['id'], $label, $key );
        }
        $html .= sprintf( '<span class="description"> %s</label>', $args['desc'] );

        echo $html;
    }

    /**
     * Displays a selectbox for a settings field
     *
     * @param array $args settings field args
     */
    function callback_select( $args ) {


        $value = esc_attr( $this->get_option( $args['id'], $this->settings_menu['name'] ) );
        //$class = isset( $args['class'] ) && !is_null( $args['class'] ) ? $args['class'] : 'regular';
        
        $html = sprintf( '<select class="regular pages_list selectbox %1$s" name="%2$s[%3$s]" id="%3$s">', $args['class'], $this->settings_menu['name'], $args['id'] );
        foreach ($args['options'] as $key => $label) {
            $html .= sprintf( '<option value="%s"%s>%s</option>', $key, selected( $value, $key, false ), $label );
        }
        $html .= sprintf( '</select>' );
        ob_start(); ?>
        <script>
		jQuery(document).ready(function($) {
		    $('select[name="<?php echo  $this->settings_menu['name'] . '[' . $args['id'] . ']'; ?>"]').chosen();
		});
		</script><?php
		$html .= ob_get_clean();
        $html .= sprintf( '<span class="description"> %s</span>', $args['desc'] );

        echo $html;
    }
   
        /**
     * Displays a selectbox for a settings field
     *
     * @param array $args settings field args
     */
    function callback_rolelist( $args ) {
        global $wp_roles;
       
        if ($wp_roles->roles)
            foreach ($wp_roles->roles as $key=>$val)
                if ($key!='administrator')
                    $args['options'][$key]=$wp_roles->roles[$key]['name'];
              
        $value = $this->get_option( $args['id'], $this->settings_menu['name'] );
        $html = '';
        foreach ($args['options'] as $key => $label) {
            $checked = isset( $value[$key] ) ? $value[$key] : '0';
            $html  .= '<div class="checkbox-wrap" style="width:90px !important;">';
            $html .= sprintf( '<input type="hidden" name="%2$s[%3$s][%4$s]" value="off" />', $args['class'], $this->settings_menu['name'], $args['id'], $key );
            $html .= sprintf( '<input type="checkbox" class="checkbox user_roles_checkbox %1$s" id="%3$s_%4$s" name="%2$s[%3$s][%4$s]" value="%4$s"%5$s />',$args['class'], $this->settings_menu['name'], $args['id'], $key, checked( $checked, $key, false ) );
            $html .= sprintf( '<label for="%2$s_%4$s" style="padding: 0px;"> %3$s</label>',$this->settings_menu['name'], $args['id'], $label, $key );
            $html .= '</div>';
        }
         
        $html .= sprintf( '<div class="hovertootip" style="left: 10px ! important; top: 0px ! important;"><img class="helpicon" src="'.plugins_url().'/siri-wp-security/img/Help-icon.png">
            <div id="tool1" style="left: 420px ! important; top: 8px ! important;"></div> <div class="tooltip" style="left: 431px ! important;"><span class="description">'.$args['desc'].'</span></div></div>');

        echo $html;      
    }
    
    function callback_pagelist( $args ) {
        $value = esc_attr( $this->get_option( $args['id'], $this->settings_menu['name'] ) );
        $name=sprintf('%1$s[%2$s]', $this->settings_menu['name'], $args['id']);
        
    	$q = array(
    		'depth' => 0, 'child_of' => 0,
    		'selected' => $value, 'echo' => 0,
    		'name' => $name, 'id' => $args['id'],
    		'show_option_none' => '', 'show_option_no_change' => '',
    		'option_none_value' => ''
    	);
        
        $html = wp_dropdown_pages($q );     
        $html = str_replace('<select','<select class="'.$args['class'].'" ', $html ) ; 
        ob_start(); ?>
        <script>
		jQuery(document).ready(function($) {
		    $('select[name="<?php echo  $this->settings_menu['name'] . '[' . $args['id'] . ']'; ?>"]').chosen();
		});
		</script><?php
		$html .= ob_get_clean();
        if($args['desc']!=''){
        $html .= sprintf( '<div class="hovertootip" style="left: 160px !important; top: -34px !important;"><img class="helpicon" src="'.plugins_url().'/siri-wp-security/img/Help-icon.png">
            <div id="tool1"></div> <div class="tooltip"><span class="description">'.$args['desc'].'</span></div></div>');
                }
        echo $html;
    }
    
    
    /**
     * Displays a textarea for a settings field
     *
     * @param array $args settings field args
     */
    function callback_textarea( $args ) {

        $value = esc_textarea( $this->get_option( $args['id'], $this->settings_menu['name'] ) );
        //$class = isset( $args['class'] ) && !is_null( $args['class'] ) ? $args['class'] : 'regular';
             
        $html = sprintf( '<textarea style="float:left;" rows="5" cols="55" class="regular-text %1$s" id="%3$s" name="%2$s[%3$s]">%4$s</textarea>', $args['class'], $this->settings_menu['name'], $args['id'], $value );
        $html .= sprintf( '<br><br><br><div class="hovertootip" style="left: 18px;"><img class="helpicon" src="'.plugins_url().'/siri-wp-security/img/Help-icon.png">
		
            <div id="tool1" style="left: 344px;"></div> <div class="tooltip" style="left: 354px;"><span class="description">'.$args['desc'].'</span></div></div>');
                                                  
        echo $html;         
    }
    
    
    function callback_export( $args ) {
        
        if (isset($_GET['export_settings']) && $_GET['export_settings'])  {
            
            $empty_keys = array_keys(array_diff_key( $this->get_defaults(), get_option($this->settings_menu['name'])));
            $empty_keys = array_fill_keys($empty_keys, '');

            $value = esc_textarea(stripslashes(json_encode( array_merge(get_option($this->settings_menu['name']), $empty_keys)    ) ));
            //$class = isset( $args['class'] ) && !is_null( $args['class'] ) ? $args['class'] : 'regular';
            $html = sprintf( '<strong> %s </strong><br/>', $args['desc'] );
            $html .= sprintf( '<textarea readonly="readonly" onclick="this.focus();this.select()" rows="5" cols="55" class="regular-text %1$s" id="%2$s" name="%2$s" style="%4$s">%3$s</textarea>', $args['class'],'export_field', $value, 'width:95% !important;height:400px !important' );
            
                                                      
            echo $html;
        }else{
            echo '<a href="'.add_query_arg(array('export_settings'=>true)).'" class="button">'.__('Export Current Settings',$this->settings_menu['name']).'</a>' ;
            echo sprintf('<div class="hovertootip"><img class="helpicon" src="'.plugins_url().'/siri-wp-security/img/Help-icon.png">
           <div id="tool1"></div> <div class="tooltip"><span class="description">'.$args['desc'].'</span></div></div>'); 
           //echo sprintf( '<br><span class="description"> %s</span>', $args['desc'] );
        }
    }
    
    function callback_import( $args ) {
        $html='';
        
        if ($args['options']) {
            $html .= sprintf( '<select class="regular selectbox %1$s" name="import_options" id="%3$s">', $args['class'], $this->settings_menu['name'], $args['id'] );
        
            $html .= sprintf( '<option value="" selected="selected">- Select Scheme -</option>' );
            foreach ($args['options'] as $key => $settings_value) 
                $html .= sprintf( '<option value="%s">%s</option>', esc_textarea(stripslashes($settings_value)), ucfirst($key) );
        
        
            $html .= sprintf( '</select>' );
                      
        }
    $html.=sprintf('<div class="hovertootip"><img class="helpicon" src="'.plugins_url().'/siri-wp-security/img/Help-icon.png">
            <div id="tool1"></div>
			<div class="tooltip"><span class="description">'.$args['desc'].'</span></div></div>');
        //$html .= sprintf( '<span class="description">%s</span>', $args['desc'] );
        $html .='<br>';
            
        $value = '';
        //$class = isset( $args['class'] ) && !is_null( $args['class'] ) ? $args['class'] : 'regular';

        $html .= sprintf( '<textarea rows="5" cols="55" class="regular-text %1$s" id="%2$s" name="%2$s">%3$s</textarea>', $args['class'],'import_field', $value );
       // $html .= sprintf( '<br><span class="description"> %s</span>', $args['desc'] );
                                                  
        echo $html;
    }
    
    function callback_debug_report( $args ) {
        global $wp_version;
        
        if (!isset($_GET['debug_report']) )  {                                          
            echo '<a href="'. add_query_arg(array('debug_report'=>true)) . '" class="button">'.__('Generate Debug Report', $this->settings_menu['name']).'</a>' ;
          echo sprintf('<div class="hovertootip"><img class="helpicon" src="'.plugins_url().'/siri-wp-security/img/Help-icon.png">
            <div id="tool1"></div> <div class="tooltip"><span class="description">'.$args['desc'].'</span></div></div>');
            // echo sprintf( '<br><span class="description"> %s</span>', $args['desc'] );
        }else{
            
            /* Get from WooCommerce by WooThemes http://woothemes.com  */
            $active_plugins = (array) get_option( 'active_plugins', array() );
            if ( is_multisite() )
            	$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
            
            $active_plugins = array_map( 'strtolower', $active_plugins );
            $pp_plugins = array();
            
            foreach ( $active_plugins as $plugin ) {
            		$plugin_data = @get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
            		if ( ! empty( $plugin_data['Name'] ) ) {
            			$pp_plugins[] = $plugin_data['Name'] .' '. $plugin_data['Version']. ' [' . $plugin_data['PluginURI'] . "]";
            		}
            }
            
            if ( $pp_plugins ) 
                $plugin_list=implode( "\n", $pp_plugins );
                            
             
            $wp_info= ( is_multisite() ) ? 'WPMU ' . $wp_version :  'WP ' . $wp_version; 
            $wp_debug = ( defined('WP_DEBUG') && WP_DEBUG ) ? 'true' : 'false';
            $is_ssl = ( is_ssl() ) ? 'true' : 'false';
            $is_rtl = ( is_rtl() ) ? 'true' : 'false';
            $fsockopen = ( function_exists( 'fsockopen' ) ) ? 'true' : 'false';
            $curl = ( function_exists( 'curl_init' ) ) ? 'true' : 'false';
                               
            if ( function_exists( 'phpversion' ) ) { 
                $php_info=  phpversion();
                $max_server_upload= ini_get('upload_max_filesize');
                $post_max_size= ini_get('post_max_size') ;
            }
    
            $empty_keys = array_keys(array_diff_key( $this->get_defaults(), get_option($this->settings_menu['name'])));
            $empty_keys = array_fill_keys($empty_keys, '');
                        
            $value = '
===========================================================
 WP Settings
===========================================================
WordPress version: 	'.$wp_info.'
Home URL: 	'. home_url(). '
Site URL: 	'. site_url(). '
Is SSL: 	'. $is_ssl.'
Is RTL: 	'. $is_rtl.'                                          
Permalink: 	'. get_option('permalink_structure'). '

============================================================
 Server Environment
============================================================
PHP Version:     	'. $php_info .'
Server Software: 	'. $_SERVER['SERVER_SOFTWARE'].'
WP Max Upload Size: '. wp_convert_bytes_to_hr( wp_max_upload_size() ).'
Server upload_max_filesize:     '.$max_server_upload.'
Server post_max_size: 	'.$post_max_size.'
WP Memory Limit: 	'. WP_MEMORY_LIMIT .'
WP Debug Mode: 	    '. $wp_debug.'
CURL:               '. $curl.'
fsockopen:          '. $fsockopen.'

============================================================
 Active plugins   
============================================================
'.$plugin_list.'

============================================================
 Plugin Option
============================================================
'. esc_textarea(stripslashes(json_encode(   array_merge( get_option($this->settings_menu['name']), $empty_keys )  ) )) .'
    ';
            
    
            $html = sprintf( '<textarea readonly="readonly" rows="5" cols="55" style="%4$s" class="%1$s" id="%2$s" name="%2$s">%3$s</textarea>', $args['class'],'debug_report', $value , 'width:95% !important;height:400px !important');
            $html .= sprintf( '<br><span class="description"> %s</span>', $args['desc'] );
            
            echo $html;
        }   
           
    }
    


    /**
     * Get the value of a settings field
     *
     * @param string $option settings field name
     * @param string $option_page the $option_page name this field belongs to
     * @param string $default default text if it's not found
     * @return string
     */
    function get_option( $option, $option_page, $_disabled_default = '' ) {

        $options = get_option( $option_page );
        
        if ( isset( $options[$option] ) ) 
            return $options[$option];
        
        return false;
    }
    
    public function get_defaults(){
        $defaults_val='';
        foreach ($this->settings_fields  as $tabs => $field) {
            foreach ($field as $opt){
                if (isset($opt['name']))  {
                    if (isset($opt['default']))
                        $defaults_val[$opt['name']]=$opt['default'];
                    else
                        $defaults_val[$opt['name']]='';
                }
            }
        }
        return $defaults_val;
    }
    
    public function save_defaults(){
        $defaults_val=$this->get_defaults();
        
        $main_key=$this->settings_menu['name'];
        
        //make white screen problem!
        update_option($main_key, $defaults_val);
            
    }
    /**
     * Show navigations as tab
     *
     * Shows all the settings section labels as tab
     */
    function show_navigation() {
        $html = '<h2 class="nav-tab-wrapper">';
        $html .='<a id="custom_login-tab" class="nav-tab" href="#custom_login">Login Form Design</a>';
        foreach ($this->settings_sections as $tab) {
            $html .= sprintf( '<a href="#%1$s" class="nav-tab" style="font-size:18px;" id="%1$s-tab">%2$s</a>', $tab['id'], $tab['title'] );
        }
        $html .= '</h2>';

        echo $html;          
    }
    function do_settings_sections_for_tab($page, $sections) {
        global $wp_settings_sections, $wp_settings_fields;

        if ( !isset($wp_settings_sections) || !isset($wp_settings_sections[$page]) )
            return;
         
        foreach ( (array) $wp_settings_sections[$page] as $section ) {
            if (in_array($section['id'], $sections)) {
                echo "<h3>{$section['title']}</h3>\n";
                call_user_func($section['callback'], $section);
                if ( !isset($wp_settings_fields) || !isset($wp_settings_fields[$page]) || !isset($wp_settings_fields[$page][$section['id']]) )
                    continue;
                echo '<table class="form-table">';
                do_settings_fields($page, $section['id']);
                echo '</table>';
            }
        }
    }
    /**
     * Show the section settings forms
     *
     * This function displays every sections in a different form
     */
    function show_forms() {
  
        if ($this->settings_menu['display_metabox'] )
            echo '<div class="metabox-holder" style="width:90%"><div class="postbox">';
        ?>
                 
                <form method="post" action="options.php">
                <?php 
                
                settings_fields( $this->settings_menu['name'] );
               // do_settings_sections( $this->settings_menu['name']);
                foreach ($this->settings_sections as $form) {  ?>
                    <div id="<?php echo $form['id']; ?>" class="group">
                        

                            <?php
                            $this->do_settings_sections_for_tab($this->settings_menu['name'], $form); ?>
                    </div>
         
                <?php } ?>
         <span style="padding:0 10px;" class="alignleft">
                    <?php //submit_button('Save Settings'); ?>
            
                    <p class="submit"><input onclick="changebutioval();" type="submit" value="Save Settings" class="button button-primary" id="submit" name="submit"></p>  
         </span>
             
                
                <span style="padding:0 10px;" class="alignleft">
                    <p class="submit">
                        <input name="reset-defaults" onclick="return confirm('<?php _e('Are you sure you want to restore all settings back to their default values?', $this->settings_menu['name']); ?>');" class="button-secondary" type="submit" value="<?php _e('Restore Defaults', $this->settings_menu['name']); ?>" />                      </p>
                </span> 
                
                 
                 
               <div class="clear"></div>
                            
                </form>
                        <?php
      if ($this->settings_menu['display_metabox'] )
            echo '</div></div>';
      $this->script();
       
    }

    /**
     * Tabbable JavaScript codes
     *
     * This code uses localstorage for displaying active tabs
     */
    function script() {
        ?>
<style>
    
    .description{
          color: #ffffff;
    }
    .postbox {
        background-image: url("<?php echo plugins_url('siri-wp-security/img/back.png');?>");
}
    #icon-options{
        background-image: url("<?php echo plugins_url('siri-wp-security/img/logo.png');?>");
        background-repeat: no-repeat;
        height: 63px;
        position: relative;
        top: 12px;
        margin-bottom:13px;
        background-size: 182px auto;
        
       }
.hovertootip {
    position:relative;
   top: -27px;
    left: 320px;
    width:60%;
    height: 0px;
}
#tool1{ display:none; background:url("<?php echo plugins_url('siri-wp-security/img/arrow.png');?>") ;
float: left;
    height: 12px;
    left: 22px;
    position: absolute;
    top: 8px;
    width: 12px;}


.tooltip { /* hide and position tooltip */
  top:-4px;
  left:32px;
  color:white;
  border-radius:5px;
  opacity:0;
  position:absolute;
  visibility:hidden;
  background-color:#21759B;
  padding:10px;
}

.hovertootip:hover .tooltip { /* display tooltip on hover */
    opacity:1;
    visibility:visible;

}
.hovertootip:hover #tool1 { /* display tooltip on hover */
    opacity:1;
    display: block;

}


.helpicon{width:20px; padding-top:4px;}
.bookmark{left: 3px;
    position: relative;
    top: 5px;
    width: 20px;}

.form-table th, .form-wrap label{ font-size:12px;}

.nav-tab{
    font-size: 16px !important;
    font-weight: bold !important;}
.nav-tab.nav-tab-active{ color:#ffffff !important;font-weight: normal !important; background: url("<?php echo plugins_url('siri-wp-security/img/hover.png');?>") !important;}

.wp-admin select{
	background-color: #FFFFFF;
    background-image: linear-gradient(#FFFFFF 20%, #F6F6F6 50%, #EEEEEE 52%, #F4F4F4 100%);
    border: 1px solid #AAAAAA;
    border-radius: 5px 5px 5px 5px;
    box-shadow: 0 0 3px #FFFFFF inset, 0 1px 1px rgba(0, 0, 0, 0.1);
	}
	
	.widget .widget-top, .postbox h3, .stuffbox h3 {
    border-bottom-color: #DFDFDF;
    box-shadow: 0 1px 0 #FFFFFF;
    font-family: sans-serif;
    font-size: 17px !important;
    font-weight: bold;
    text-shadow: 0 1px 0 #FFFFFF;
}
h4{
	    font-size: 17px;
    line-height: 17px;
    margin: 1.33em 0;
    text-shadow: 0 1px 0 #E5E5E5;}
.regular-text{ border:1px solid #c1c1c1 !important; background-color:#f6f6f6 !important;}
body{ font-family:sans-serif !important;}
.chzn-container-single .chzn-single{ border-radius:0px !important;}


input[type="checkbox"]{
	visibility: hidden;
}
.checkbox-wrap,
.checkbox-wrap > ul li,
.radio-wrap,
.radio-wrap > ul li {
	float: left;
	margin: 0 20px 0 -10px;
	position: relative;
	width: 20px;
}
.checkbox-wrap > ul,
.radio-wrap > ul {
	margin: 0;
	padding: 0;
}
.checkbox-wrap li,
.radio-wrap li {
	line-height: 2.4em;
	margin-left: 0 !important;
}
.checkbox-wrap label,
.radio-wrap label {
	-moz-border-bottom-colors: none;
	-moz-border-left-colors: none;
	-moz-border-right-colors: none;
	-moz-border-top-colors: none;
	background: #f5f5f5; /* Old browsers */
	background: -moz-linear-gradient(top, #ffffff 0%, #e6e6e6 100%); /* FF3.6+ */
	background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#ffffff), color-stop(100%,#e6e6e6)); /* Chrome,Safari4+ */
	background: -webkit-linear-gradient(top, #ffffff 0%,#e6e6e6 100%); /* Chrome10+,Safari5.1+ */
	background: -o-linear-gradient(top, #ffffff 0%,#e6e6e6 100%); /* Opera 11.10+ */
	background: -ms-linear-gradient(top, #ffffff 0%,#e6e6e6 100%); /* IE10+ */
	background: linear-gradient(to bottom, #ffffff 0%,#e6e6e6 100%); /* W3C */
	filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#ffffff', endColorstr='#e6e6e6',GradientType=0 ); /* IE6-9 */
	border-color: rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.1) #A2A2A2;
	border-image: none;
	border-style: solid;
	border-width: 1px;
	-webkit-box-shadow: 0 1px 0 rgba(255, 255, 255, 0.2) inset, 0 1px 2px rgba(0, 0, 0, 0.05);
	-moz-box-shadow: 0 1px 0 rgba(255, 255, 255, 0.2) inset, 0 1px 2px rgba(0, 0, 0, 0.05);
	box-shadow: 0 1px 0 rgba(255, 255, 255, 0.2) inset, 0 1px 2px rgba(0, 0, 0, 0.05);
	cursor: pointer;
	height: 20px;
	position: absolute;
	top: 0;
	width: 20px;
	text-indent: 30px;
}
.radio-wrap label {
	-webkit-border-radius: 20px;
	-moz-border-radius: 20px;
	border-radius: 20px;
}
.checkbox-wrap label:after,
.radio-wrap label:after {
	content: attr(title);
	visibility: hidden;
}
.checkbox-wrap label:before,
.radio-wrap label:before {
	-moz-border-bottom-colors: none;
	-moz-border-left-colors: none;
	-moz-border-right-colors: none;
	-moz-border-top-colors: none;
	border-color: -moz-use-text-color -moz-use-text-color #333333 #333333;
	border-image: none;
	border-style: none none solid solid;
	border-width: medium medium 3px 3px;
	content: "";
	height: 5px;
	left: 4px;
	-ms-filter: "progid:DXImageTransform.Microsoft.Alpha(Opacity=0)";
	filter: alpha(opacity=0);
	opacity: 0;
	position: absolute;
	top: 4px;
	-webkit-transform: rotate(-45deg);
	-moz-transform: rotate(-45deg);
	-ms-transform: rotate(-45deg);
	-o-transform: rotate(-45deg);
	transform: rotate(-45deg);
	width: 9px;
}
.checkbox-wrap input[type="checkbox"]:checked + label:before,
.radio-wrap input[type="radio"]:checked + label:before {
    -ms-filter: "progid:DXImageTransform.Microsoft.Alpha(Opacity=100)";
	filter: alpha(opacity=100);
	opacity: 1;
}
.js .widget .widget-top, .js .postbox h3{
     height: 30px;
    line-height: 33px;
    font-size: 18px;
}

</style>
        <style type="text/css">
        <!--
        	.postbox h3{cursor: auto!important;}
        -->
        </style>
        <script>
            function changebutioval()
            {
                var postbase=document.getElementById('post_base').value;
                var value=postbase.trim()
               if(value=='/%postname%/' || value=='%postname%' || value=='/%postname%' || value=='%postname%/')
               {
                  document.getElementById('post_base').value='/%year%/%postname%/';
                 
               } 
               var pagebase=document.getElementById('page_base').value;
                var pagevalue=pagebase.trim()
               if(pagevalue=='')
               {
                 document.getElementById('page_base').value='/page';
                } 
            }
        </script>
         <script>
            function addextracss()
            {
                var extrajq=document.getElementById('custom_login[custom_jquery]').value;
             
               var str=extrajq;
               var n=str.search("p.message");
               if(n=='-1')
               {
                var jq = " $('p.message').replaceWith('<p class=\"message\" style=\"margin: 147px 0 0 17px !important;position: absolute !important;width: 20% !important;\">Please fill the details. You will receive a link to create a new password via email.</p>'); if ($('#login_error').length > 0) { $('p.message').css(\"margin\", \"212px 0 0 17px\");}";
                var finaljq=extrajq.concat(jq);
                document.getElementById('custom_login[custom_jquery]').value=finaljq;  
               }
          
            }
        </script>
        <script>
            jQuery(document).ready(function($) {
                // Switches option sections
                $('.group').hide();
                
                $('#import_options').change(function(e){
                    
                    if (confirm('You may lose your current settings. Is it OK?')==true)
                        $('#import_field').val($(this).val());
                    else
                        $('#import_field').val('');  
                });
                
                $('.opener').change(function(e){ 
                    
                    var this_obj=$(this);
                    var id= this_obj.attr('id');
                    var name= this_obj.attr('name');
                      
                    if (this_obj.attr('type')=='checkbox' ) { 
                          
                        if (this_obj.is(':checked'))                
                            $('.open_by_'+id ).parentsUntil('tbody').slideDown('150');  
                        else
                            $('.open_by_'+id ).parentsUntil('tbody').slideUp('150');
   
                    }else if ( this_obj.attr('type')=='radio'){
                         
                        $('.open_by_'+ $('input[name="'+name+'"]:checked').attr('id') ).parentsUntil('tbody').slideDown('150');
                        //hide other   
                        $('.open_by_'+ $('input[name="'+name+'"]:not(:checked)').attr('id') ) .parentsUntil('tbody').slideUp('150');
                    } else if (this_obj.hasClass('selectbox')){
                        
                        $('.open_by_'+ id+'_'+this_obj.val() ).parentsUntil('tbody').slideDown('150');
                        //hide other   
                        $("[class^='open_by_"+ id+"_'],[class*=' open_by_"+ id+"_']").not('.open_by_'+ id +'_'+this_obj.val()).parentsUntil('tbody').slideUp();
                         
                    }    
                            
                });
                
                 
                //first time load should be after change
                $('.opener').trigger('change');
                    
                
                var activetab = '';
                if (typeof(localStorage) != 'undefined' ) {
                    activetab = localStorage.getItem("activetab");
                }
                if (activetab != '' && $(activetab).length ) {
                    $(activetab).fadeIn();
                } else {
                    $('.group:first').fadeIn();
                }
                $('.group .collapsed').each(function(){
                    $(this).find('input:checked').parent().parent().parent().nextAll().each(
                    function(){
                        if ($(this).hasClass('last')) {
                            $(this).removeClass('hidden');
                            return false;
                        }
                        $(this).filter('.hidden').removeClass('hidden');
                    });
                });

                if (activetab != '' && $(activetab + '-tab').length ) {
                    $(activetab + '-tab').addClass('nav-tab-active');
                }
                else {
                    $('.nav-tab-wrapper a:first').addClass('nav-tab-active');
                }
                $('.nav-tab-wrapper a').click(function(evt) {
                    $('.alignleft').show();$('.alignright').show();
                    $('#wpwrap').css("min-height","100%");
                    $('.nav-tab-wrapper a').removeClass('nav-tab-active');
                    $(this).addClass('nav-tab-active').blur();
                    var clicked_group = $(this).attr('href');
                    if (typeof(localStorage) != 'undefined' ) {
                        localStorage.setItem("activetab", $(this).attr('href'));
                    }
                    $('.group').hide();
                    $(clicked_group).fadeIn();
                    evt.preventDefault();
                });
                
               
                               $('#custom_login-tab').click(function(){
                                      // var cnt = $('#custom_login').html();
                                      // $('#loginSettings').html(cnt);
                                      
                                       $('.alignleft').hide();$('.alignright').hide();
                                        $('#wpwrap').css("min-height","420%");
                                       });
                                      
            window.onload = function()
                {
                     if($('#custom_login-tab').attr('class') == 'nav-tab nav-tab-active')
                        {
                       $('.alignleft').hide();$('.alignright').hide();
                       $('#wpwrap').css("min-height","420%");
                        }
                };
                                      
            });
        </script>
        <?php
    }

}
 