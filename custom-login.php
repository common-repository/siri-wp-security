<?php
/**
 * Plugin Name: Siri WP Security
 * Plugin URI: http://www.siriinnovations.com
 * Description: Security is a pioneer task anywhere, chances shouldn't be drawn in terms of security. With Siri WP Security daunting task of securing your site, would be pretty easy, with just one click. Hide Admin folder to intruders, trying to gain access to your website. Vexed, seeing the old school wp-login URL. Then, how about customizing  your wp-login page. Customize wp-login page like never before, and create your own URL, isn't that cool.
 * Version: Alpha
 * Author: Siri Iinnovations
 * Author URI: http://www.siriinnovations.com
 * Text Domain: wp_security
 * 
 * Copyright (C) 2013  Siri Iinnovations
 * @license http://www.gnu.org/licenses/gpl-3.0.html
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 */
 
if ( !class_exists( 'Secure_login' ) ) :

define( 'SMW_TITLE', 'Siri WP Security'); 
define( 'SMW_VERSION', 'Alpha' );
define( 'SMW_SLUG', 'secure_wp'); //use _
define( 'SMW_PATH', dirname( __FILE__ ) );
define( 'SMW_DIR', basename( SMW_PATH ));
define( 'SMW_URL', plugins_url() . '/' . SMW_DIR );
define( 'SMW_FILE', plugin_basename( __FILE__ ) );


class SeureMyWP {
    const title=SMW_TITLE;
    const ver = SMW_VERSION;
    const slug = SMW_SLUG; 
    const path= SMW_PATH;
    const dir = SMW_DIR;
    const url= SMW_URL;
    const main_file= SMW_FILE;
    
    private $s;
    private $replace_old=array();
    private $replace_new=array();
    private $preg_replace_old=array();
    private $preg_replace_new=array();
    
   	/**
   	 * SeureMyWP::__construct() 
   	 * 
   	 * @return
   	 */
   	function __construct() {
        
        //Let's start, Bismillah!
   	    register_activation_hook( __FILE__, array (&$this, 'on_activate_callback') );
		register_deactivation_hook( __FILE__, array (&$this, 'on_deactivate_callback') ); 

        //Fix a WP problem caused by filters order for deactivation
        if (isset($_GET['action']) && $_GET['action']=='deactivate' && isset($_GET['plugin']) && $_GET['plugin']==self::main_file && is_admin()){
            delete_option(self::slug);
        }
        
        if  ( (isset($_POST['action']) && $_POST['action']=='deactivate-selected') || (isset($_POST['action2']) && $_POST['action2']=='deactivate-selected') && is_admin()){
            $plugins = isset( $_POST['checked'] ) ? (array) $_POST['checked'] : array();
            foreach ($plugins as $plugin)
                if ($plugin==self::main_file)
                    delete_option(self::slug);
        } 
         
   	    include_once('lib/class.helper.php') ;
        $this->h= new PP_Helper(self::slug, self::ver);

        if (is_admin())  {
            include_once('lib/class.settings-api.php') ;
            add_action( 'init', array( &$this, 'register_settings' ), 5 );
        }
        
        $this->options = get_option(self::slug);
        add_action('pp_settings_api_filter', array( &$this, 'pp_settings_api_filter'), 100, 1);
        
        add_action( 'init', array( &$this, 'init' ), 1);
        add_action( 'wp', array( &$this, 'wp' ) );
        add_action( 'generate_rewrite_rules', array( &$this, 'add_rewrite_rules'));
        add_action( 'admin_notices', array(&$this, 'admin_notices'));
         
        add_filter( '404_template', array (&$this, 'custom_404_page'), 10, 1);
	     
            
        if ($this->opt('email_from_name') )
			add_filter('wp_mail_from_name', array( &$this, 'email_from_name' ));
	  	
         
	  	if ($this->opt('email_from_address') )
            add_filter('wp_mail_from', array( &$this, 'email_from_address' ));
	  	
	  
        if ($this->opt('hide_wp_login')){
            add_action( 'site_url', array( &$this, 'add_login_key_to_action_from_login' ), 101, 4 );
              
            add_filter('login_url', array( &$this,'add_key_login_to_url_login'), 101, 2);
            add_filter('logout_url', array( &$this,'add_key_login_to_url_logout'), 101, 2);
            add_filter('lostpassword_url', array( &$this,'add_key_login_to_url_password'), 101, 2);  
            add_filter('register', array( &$this,'add_key_login_to_url_login'), 101, 2);
        }    
            
        if (!is_admin()){
            add_action('get_header',array(&$this, 'ob_starter'));
        }

        add_action( 'admin_enqueue_scripts', array( $this, 'admin_css_js' ) );
        
        if (function_exists('bp_is_current_component'))
            add_action( 'bp_uri', array( $this, 'bp_uri' ) );

    }
    
    /**
     * SeureMyWP::bp_uri()
     * Fix buddypress pages URL when page_base is enabled
     * 
     * @return
     */
    function bp_uri($uri){
        if($this->opt('page_base'))
            return str_replace(trim($this->opt('page_base') ,' /').'/','', $uri);
        else
            return $uri;
    }
    
    /**
     * SeureMyWP::admin_notices() 
     * Displays necessary information in admin panel
     * 
     * @return
     */
    function admin_notices(){
         global $current_user;
       
        if (in_array('administrator', $current_user->roles))
            flush_rewrite_rules(true);
        
        if (isset($_GET['page']) && $_GET['page']==self::slug && !$this->is_permalink())
            echo '<div class="updated error" style="width: 50%; float: left; position: fixed; margin: -8% 0px 0px 16%;"><p>' . __('Your <a href="options-permalink.php">permalink structure</a> is off. In order to get all features of this plugin please enable it.', self::slug ) . '</p></div>';
            
           
        if (basename($_SERVER['PHP_SELF']) == 'options-permalink.php' && $this->is_permalink() && isset($_POST['permalink_structure']))
          echo '<div class="updated"><p>' . sprintf(__('We are refreshing this page in order to fully implement changes. %s', self::slug ), '<a href="options-permalink.php">Manual Refresh</a>' ). '<script type="text/JavaScript"><!--  setTimeout("window.location = \"options-permalink.php\";", 3000);   --></script></p> </div>';                                    
           
    }
  
    /**
     * SeureMyWP::email_from_name()
     * 
     * Change mail name
     * @return
     */
  	function email_from_name(){
		return $this->opt('email_from_name');
  	}
  
     /**
     * SeureMyWP::email_from_address()
     * 
     * Change mail address
     * @return
     */
  	function email_from_address(){
		return $this->opt('email_from_address');
  	}
    
   /**
     * SeureMyWP::wp()
     * 
     * Disable WP components when permalink is enabled
     * @return
     */
    function wp(){
        if ((is_feed() || is_comment_feed())&& !isset($_GET['feed']) && !$this->opt('feed_enable'))
            $this->block_access();
        if (is_author() && !isset($_GET['author']) && !isset($_GET['author']) && !$this->opt('author_enable'))
            $this->block_access();
        if (is_search() && !isset($_GET['s']) && !$this->opt('search_enable'))
            $this->block_access();
        if (is_paged() && !isset($_GET['paged']) && !$this->opt('paginate_enable'))
            $this->block_access(); 
        if (is_page() && !isset($_GET['page_id']) && !isset($_GET['pagename']) && !$this->opt('page_enable'))
            $this->block_access();
        if (is_single() && !isset($_GET['p']) && !$this->opt('post_enable'))
            $this->block_access(); 
        if (is_category() && !isset($_GET['cat']) && !$this->opt('category_enable'))
            $this->block_access();
        if (is_tag() && !isset($_GET['tag']) && !$this->opt('tag_enable'))
            $this->block_access();
        if ((is_date() || is_time()) && !isset($_GET['monthnum']) && !isset($_GET['m'])  && !isset($_GET['w']) && !isset($_GET['second']) && !isset($_GET['year']) && !isset($_GET['day']) && !isset($_GET['hour']) && !isset($_GET['second']) && !isset($_GET['minute']) && !isset($_GET['calendar']) && $this->opt('disable_archive'))
            $this->block_access(); 
        if ((is_tax() || is_post_type_archive() || is_trackback() || is_comments_popup() || is_attachment()) && !isset($_GET['post_type']) && !isset($_GET['taxonamy']) && !isset($_GET['attachment']) && !isset($_GET['attachment_id']) && $this->opt('disable_other_wp'))
            $this->block_access(); 
            
               
    }
    /**
     * SeureMyWP::admin_css_js()
     * 
     * Adds admin.js to options page
     * @return
     */
    function admin_css_js(){
        
        if (isset($_GET['page']) && $_GET['page']==self::slug){
            wp_enqueue_script( 'jquery' );
    		wp_register_script( self::slug.'_admin_js', self::url. '/js/admin.js' , array('jquery'), self::ver, false );
                wp_enqueue_script( 'wp-color-picker' );
                wp_enqueue_script( 'jquery-chosen', plugins_url( 'assets/js/my.jquery.min.js', CUSTOM_LOGIN_FILE ), array( 'jquery' ), '0.9.12', false );
            wp_enqueue_script(  self::slug.'_admin_js');
	    }
       
    }
    
    /**
     * SeureMyWP::pp_settings_api_filter()
     * Filter after updateing Options
     * @param mixed $post
     * @return
     */
    function pp_settings_api_filter($post){
        global $wp_rewrite;  
        if (isset($post[self::slug]['admin_key']) && $this->opt('admin_key')!=$post[self::slug]['admin_key']) {
          $body = "Hi-\nThis is %s plugin. Here is your new WordPress login address:\nURL: %s\n\nBest Regards,\n%s";
            $body = sprintf(__($body, self::slug), self::title, wp_login_url(), self::title );
            $subject = sprintf(__('[%s] Your New WP Login!', self::slug), self::title);
            wp_mail(get_option('admin_email'), $subject, $body);
        }
        $wp_rewrite->set_permalink_structure(trim($post[self::slug]['post_base'], '/ ')); 
        $wp_rewrite->set_category_base(trim($post[self::slug]['category_base'], '/ ')); 
        $wp_rewrite->set_tag_base(trim($post[self::slug]['tag_base'], '/ ')); 
        flush_rewrite_rules();
        return $post;   
    }
  
    /**
     * SeureMyWP::add_login_key_to_action_from()
     * Add admin key to links in wp-login.php
     * @param string $url
     * @param string $path
     * @param string $scheme
     * @param int $blog_id
     * @return
     */
    function add_login_key_to_action_from($url, $path, $scheme, $blog_id ){
            if ($url)
        	if ($scheme=='login' || $scheme=='login_post' )
            	return add_query_arg(self::slug, $this->opt('admin_key'), $url);
            
        return $url; 
    }
      function add_login_key_to_action_from_login($url, $path, $scheme, $blog_id ){
            if ($url)
        	if ($scheme=='login' || $scheme=='login_post' )
                {
               $Path=$_SERVER['REQUEST_URI'];
                $url=$Path;   
                }  
        return $url; 
    }
    
    
    /**
     * SeureMyWP::add_key_login_to_url()
     * Add admin key to wp-login url
     * @param mixed $url
     * @param string $redirect
     * @return
     */
    function add_key_login_to_url($url, $redirect='0'){
        	if ($url)
       		return add_query_arg(self::slug, $this->opt('admin_key'), $url);
    }
     function add_key_login_to_url_login($url, $redirect='0'){
        	if ($url)
                    $url=get_site_url ().'/'.$this->opt('admin_key').'/';
       		return $url;
    }
      function add_key_login_to_url_logout($url, $redirect='0'){
        	if ($url)
                    $url=get_site_url ().'/'.$this->opt('admin_key').'/logout/';
                error_reporting(0);
       	            wp_clear_auth_cookie();
                  do_action('wp_logout');
            return $url;
    }
    function add_key_login_to_url_password($url, $redirect='0'){
                if ($url)
                $url=get_site_url ().'/'.$this->opt('admin_key').'/lostpassword/';
       	        return $url;
    }
    /**
     * SeureMyWP::ob_starter()
     * 
     * @return
     */
    function ob_starter(){
        return ob_start(array(&$this, "global_html_filter")) ;
    }
    
    /**
     * SeureMyWP::custom_404_page()
     * 
     * @param mixed $templates
     * @return
     */
    function custom_404_page($templates){
        global $current_user;
        $visitor=esc_attr((is_user_logged_in()) ? $current_user->user_login : $_SERVER["REMOTE_ADDR"]);
        
        if ($this->opt('custom_404') && $this->opt('custom_404_page'))
            wp_redirect(add_query_arg( array('by_user'=>$visitor, 'ref_url'=> urldecode($_SERVER["REQUEST_URI"])), get_permalink($this->opt('custom_404_page')))) ;
           
        else
            return $templates;
            
        die();            
        
    }
    
    /**
     * SeureMyWP::do_feed_base()
     * 
     * @param boolean $for_comments
     * @return
     */
    function do_feed_base( $for_comments ) {
    	if ( $for_comments )
   		   load_template( ABSPATH . WPINC . '/feed-rss2-comments.php' );
    	else
	       load_template( ABSPATH . WPINC . '/feed-rss2.php' );
    }
    /**
     * SeureMyWP::is_permalink()
     * Is permalink enabled?
     * @return
     */
    function is_permalink(){
        global $wp_rewrite;
        if (!isset($wp_rewrite) || !is_object($wp_rewrite) || !$wp_rewrite->using_permalinks())
            return false;
        return true;     
    }
    
    /**
     * SeureMyWP::block_access()
     * 
     * @return
     */
    function block_access(){
        global $wp_query, $current_user;
        $visitor = esc_attr((is_user_logged_in()) ? $current_user->user_login : $_SERVER["REMOTE_ADDR"]);
        
        $url=esc_url('http'.(empty($_SERVER['HTTPS'])?'':'s').'://'.$_SERVER['SERVER_NAME']. $_SERVER['REQUEST_URI']);
       
        if ($this->opt('spy_notifier')) {
            $body = "Hi-\nThis is %s plugin. We guesses someone is researching about your WordPress site.\n\nHere is some more details:\nVisitor: %s\nURL: %s\nUser Agent: %s\n\nBest Regards,\n%s";
            $body = sprintf(__($body, self::slug), self::title, $visitor, $url, $_SERVER['HTTP_USER_AGENT'], self::title);
            $subject = sprintf(__('[%s] Someone is mousing!', self::slug), self::title);
            wp_mail(get_option('admin_email'), $subject, $body);   
        }

        status_header( 404 );
        nocache_headers();
        
  
        if ( $this->h->str_contains($_SERVER['PHP_SELF'], '/wp-admin/') || $this->h->ends_with($_SERVER['PHP_SELF'], '.php')) {  	
            $visitor=esc_attr((is_user_logged_in()) ? $current_user->user_login : $_SERVER["REMOTE_ADDR"]);
       
            if ($this->opt('custom_404') && $this->opt('custom_404_page') )   {
                wp_redirect(add_query_arg( array('by_user'=>$visitor, 'ref_url'=> urldecode($_SERVER["REQUEST_URI"])), get_permalink($this->opt('custom_404_page')))) ; 
            }else{
                $response = wp_remote_get( home_url('/nothing_404_404') );
                echo $response['body'];
            }

        }else{
            require_once( get_404_template() );      
        }  
                   
        die();
    }
    
    /**
     * SeureMyWP::nice_search_redirect()
     * 
     * @return
     */
    function nice_search_redirect() {
        global $wp_rewrite;
        if (!isset($wp_rewrite) || !is_object($wp_rewrite) || !$wp_rewrite->using_permalinks()) 
            return;
        
        if ($this->opt('nice_search_redirect') && $this->is_permalink()){
            $search_base = $wp_rewrite->search_base;
                
            if (is_search() && strpos($_SERVER['REQUEST_URI'], "/{$search_base}/") === false) {
                if (isset($_GET['s']))
                    $keyword= get_query_var('s');
                
                if (isset($_GET[$this->opt('search_query')]))
                    $keyword= get_query_var($this->opt('search_query'));
                
                wp_redirect(home_url("/{$search_base}/" . urlencode($keyword)));
                exit();
            }
        }
    }


    /**
     * SeureMyWP::remove_menu_class()
     * 
     * @param array $classes
     * @return
     */
    function remove_menu_class($classes) {
	  	$new_classes=array();
        if (is_array($classes)) {
             foreach($classes as $class){
                if ($this->h->starts_with( $class, 'current-'))
				  $new_classes[]=$class;
				 
             }
        }else{
            $new_classes='';
        }                   
        
        return $new_classes;   
	
    }
    /**
     * SeureMyWP::global_html_filter()
     * Filter output HTML
     * @param mixed $buffer
     * @return
     */
    function global_html_filter($buffer){
        
        if ($this->replace_old)
            $buffer = str_replace($this->replace_old, $this->replace_new, $buffer);
        
        if ($this->preg_replace_old)
            $buffer = preg_replace($this->preg_replace_old, $this->preg_replace_new, $buffer);
            
        return $buffer;        
        
    }
    /**
     * SeureMyWP::remove_ver_scripts()
     * 
     * @param string $src
     * @return
     */
    function remove_ver_scripts($src){
        if ( strpos( $src, 'ver=' ) )
            $src = remove_query_arg( 'ver', $src );
        return $src;
    }

    /**
     * SeureMyWP::global_css_filter()
     * Generate new style from main file 
     * @return
     */
    function global_css_filter(){
        global $wp_query;

        if (isset($wp_query->query_vars['style_wrapper']) && $wp_query->query_vars['style_wrapper'] && $this->is_permalink() ){
		    error_reporting(0); 
		    $css_file=get_template_directory().'/style.css';
          
            header ('Content-type: text/css; charset=UTF-8');
            $css=file_get_contents($css_file);
            
            if ($this->opt('minify_new_style') )  {
                $to_remove=array ('%\n\r%','!/\*.*?\*/!s', '/\n\s*\n/',"%(\s){1,}%");
                $css = preg_replace($to_remove, ' ', $css);
            }
            
            if ($this->opt('clean_new_style') )  {
                $old = array ('wp-caption', 'alignright', 'alignleft','alignnone', 'aligncenter');
                $new = array ('x-caption', 'x-right', 'x-left','x-none', 'x-center');
                $css = str_replace($old, $new, $css);
			    //We replace HTML, too 
            } 
            echo $css;
          
               
            exit;    
        }
               
    }
    
    /**
     * SeureMyWP::init()
     * 
     * @return
     */
    function init(){                  
        global $wp_rewrite,$wp,$wp_roles,$wp_query, $current_user;
        load_plugin_textdomain(self::slug, FALSE, self::dir.'/lang/');
        
        $wp_roles->add_cap( 'administrator', self::slug . '_trusted');  
        if ( $this->opt('trusted_user_roles') )  {
            foreach ($this->opt('trusted_user_roles') as $trusted_role) 
                $wp_roles->add_cap( $trusted_role, self::slug . '_trusted');    
        }
       
        $is_trusted=false;   
        if (current_user_can(self::slug . '_trusted') || (isset($_GET[self::slug]) && $_GET[self::slug]==$this->opt('admin_key')) )
            $is_trusted=true;
            
        if ($this->opt('remove_html_comments'))  {
            //comments and more than 2 space or line break will be remove. Simple & quick but not perfect! 
            $this->preg_replace_old[]='!/\*.*?\*/!s';
            $this->preg_replace_new[]=' ';
            $this->preg_replace_old[]='/\n\s*\n/';
            $this->preg_replace_new[]=' ';
            $this->preg_replace_old[]='/<!--(.*?)-->/';
            $this->preg_replace_new[]= ' ';
            $this->preg_replace_old[]="%(\s){2,}%";
            $this->preg_replace_new[]= ' ';
        }
        
        if ($this->opt('remove_ver_scripts')) {
            add_filter( 'style_loader_src', array( &$this, 'remove_ver_scripts'), 9999 );
            add_filter( 'script_loader_src', array( &$this, 'remove_ver_scripts'), 9999 ); 
        }
        
        
        if ($this->opt('remove_default_description') )       
            add_filter('get_bloginfo_rss',  array( &$this, 'remove_default_description'));
        
        
        if ($this->opt('nice_search_redirect') && $this->is_permalink()) 
            add_action('template_redirect', array( &$this, 'nice_search_redirect'));
        
       
        if ($this->opt('replace_in_html')){
            $replace_in_html=$this->h->replace_newline($this->opt('replace_in_html'),'|');
            $replace_lines=explode('|', $replace_in_html);
            if ($replace_lines) {
                foreach ($replace_lines as $line)  {
                    $replace_word=explode('=', $line);
                    if (isset($replace_word[0]) && isset($replace_word[1])) {
                        $this->replace_old[]=trim($replace_word[0], ' ');
                        $this->replace_new[]=trim($replace_word[1], ' ');
                    }
                }                                                 
            }
   
        } 
       
         
        if ($this->opt('remove_menu_class') )  {
            add_filter('nav_menu_css_class', array( &$this, 'remove_menu_class'));
            add_filter('nav_menu_item_id', array( &$this,'remove_menu_class'));
            add_filter('page_css_class', array( &$this,'remove_menu_class'));    
        }
            
                           
        if ($this->opt('remove_body_class') )
            add_filter('body_class', create_function('', 'return array();'), 999);
            
        if ($this->opt('remove_post_class') )
            add_filter('post_class', create_function('', 'return array();'), 999);
            
   
        if ($this->opt('hide_admin_bar') && !$is_trusted)  
            add_filter( 'show_admin_bar', '__return_false' );
         
            
        if ($this->opt('disable_canonical_redirect'))
            add_filter('redirect_canonical', create_function('','return false;'), 101 , 2);
              
        $feed_enable=$this->opt('feed_enable');
         
        if (!$feed_enable && !is_admin()) {
            unset($_GET['feed']);
            unset($_GET[$this->opt('feed_query')]);	
            add_action('do_feed', array( &$this, 'block_access'), 1);
            add_action('do_feed_rdf', array( &$this, 'block_access'), 1);
            add_action('do_feed_rss',array( &$this, 'block_access'), 1);
            add_action('do_feed_rss2', array( &$this, 'block_access'), 1);
            add_action('do_feed_atom', array( &$this, 'block_access'), 1);
            
            //...and our own feed type!
            $new_feed_base= trim($this->opt('feed_base'), '/ ');
            if ($new_feed_base) {
                add_action('do_feed_'.$new_feed_base, array( &$this, 'block_access'), 1);
            }
        }        
        if (!$feed_enable || $this->opt('remove_feed_meta')){
            remove_action('wp_head', 'feed_links', 2);
            //Remove automatic the links to the extra feeds such as category feeds.
            remove_action('wp_head', 'feed_links_extra', 3);
        }
         
        $new_feed_query= $this->opt('feed_query');
        if ($new_feed_query && $new_feed_query!='feed' && !is_admin()) {
            if (isset($_GET['feed']))
                unset($_GET['feed']);
             
            $wp->add_query_var($new_feed_query);
            if (isset($_GET[$new_feed_query]))
                $_GET['feed']=$_GET[$new_feed_query];

            if (!$this->is_permalink()){
                $this->preg_replace_old[]='#('.home_url().'(/\?)[0-9a-z=_/.&\-;]*)(feed=)#';  //;&amp;
                $this->preg_replace_new[]='$1'.$new_feed_query.'=' ;
            }
        }
         
        $new_feed_base= trim($this->opt('feed_base'), '/ ');	
         
		if ( $new_feed_base && 'feed' != $new_feed_base && $this->is_permalink() ) {
		    $wp_rewrite->feed_base = $new_feed_base;
            add_feed($new_feed_base, array(&$this, 'do_feed_base'));
            
            
            $this->preg_replace_old[]= '#('.home_url().'/[0-9a-z_\-/.]*)(/feed)#';
            $this->preg_replace_new[]= '$1/'.$new_feed_base ;
                
            //Remove default 'feed' type
            $feeds=$wp_rewrite->feeds;
            unset($feeds[0]);
            $wp_rewrite->feeds=$feeds;
		}
        

        
        $author_enable=$this->opt('author_enable');
       
        if (!$author_enable && !is_admin()) {
            unset($_GET['author']);
            unset($_GET['author_name']);
            unset($_GET[$this->opt('author_query')]);	

        }  
              
        $new_author_query= $this->opt('author_query');
        if ($new_author_query && $new_author_query!='author' && !is_admin()) {
            if (isset($_GET['author']))
                unset($_GET['author']);
            
            if (isset($_GET['author_name']))
                unset($_GET['author_name']);
             
            $wp->add_query_var($new_author_query);
            
            if (isset($_GET[$new_author_query]) && is_numeric($_GET[$new_author_query]) )
                $_GET['author']=$_GET[$new_author_query];
            
            if (isset($_GET[$new_author_query]) && !is_numeric($_GET[$new_author_query]) )
                $_GET['author_name']=$_GET[$new_author_query];
            
            if (!$this->is_permalink()){
                $this->preg_replace_old[]='#('.home_url().'(/\?)[0-9a-z=_/.&\-;]*)((author|author_name)=)#';
                $this->preg_replace_new[]='$1'.$new_author_query.'=' ;
            }
        }
     
        $new_author_base= trim($this->opt('author_base'), '/ ');	

		if ( $new_author_base && 'author' != $new_author_base && $this->is_permalink()) {
		    $wp_rewrite->author_base = $new_author_base;
            
		}
        
        
        if ($this->opt('author_without_base') && $this->is_permalink())  {
            $wp_rewrite->author_structure = $wp_rewrite->root  . '/%author%' ;
        
        }
        
        $search_enable=$this->opt('search_enable');
       
        if (!$search_enable && !is_admin()) {
            unset($_GET['s']);
            unset($_GET[$this->opt('search_query')]);	
        }  
              
        $new_search_query= $this->opt('search_query');
        
        if ($new_search_query && $new_search_query!='s' && !is_admin()) {
            if (isset($_GET['s']))
                unset($_GET['s']);
             
            $wp->add_query_var($new_search_query);
            
            if (isset($_GET[$new_search_query]) )
                $_GET['s']=$_GET[$new_search_query];
            
            if (!$this->is_permalink()){
                
                $this->replace_old[]= ' name="s" ';
                $this->replace_new[]= ' name="'.$new_search_query.'" ';
                
            }
            
            
        }
     
        $new_search_base= trim($this->opt('search_base'), '/ ');	

		if ( $new_search_base && 'search' != $new_search_base && $this->is_permalink()) {
		    $wp_rewrite->search_base = $new_search_base; 
		}
        
        
        
        $paginate_enable=$this->opt('paginate_enable');
       
        if (!$paginate_enable && !is_admin()) {
            unset($_GET['paged']);  
            unset($_GET[$this->opt('paginate_query')]);	
        }  
              
        $new_paginate_query= $this->opt('paginate_query');
        
        if ($new_paginate_query && $new_paginate_query!='paged' && !is_admin()) {
            if (isset($_GET['paged']))
                unset($_GET['paged']);
             
            $wp->add_query_var($new_paginate_query);
            
            if (isset($_GET[$new_paginate_query]) )
                $_GET['paged']=$_GET[$new_paginate_query];
            
            if (!$this->is_permalink()){
                $this->preg_replace_old[]='#('.home_url().'(/\?)[0-9a-z=_/.&\-;]*)(paged=)#';
                $this->preg_replace_new[]='$1'.$new_paginate_query.'=' ;                
            }
        }
     
        $new_paginate_base= trim($this->opt('paginate_base'), '/ ');	

		if ( $new_paginate_base && 'page' != $new_paginate_base && $this->is_permalink()) {
		    $wp_rewrite->pagination_base = $new_paginate_base; 
		}
        
        
        
        $page_enable=$this->opt('page_enable');
        if (!$page_enable && !is_admin()) {
            unset($_GET['pagename']);
            unset($_GET['page_id']);
            unset($_GET[$this->opt('page_query')]);	
        }  
              
        $new_page_query= $this->opt('page_query');
        
        if ($new_page_query && $new_page_query!='page_id' && !is_admin()) {
            if (isset($_GET['page_id']))
                unset($_GET['page_id']);
            
            if (isset($_GET['pagename']))
                unset($_GET['pagename']);
                 
            $wp->add_query_var($new_page_query);
            
            if (isset($_GET[$new_page_query]) && is_numeric($_GET[$new_page_query]) )
                $_GET['page_id']=$_GET[$new_page_query];
            
            if (isset($_GET[$new_page_query]) && !is_numeric($_GET[$new_page_query]) )
                $_GET['pagename']=$_GET[$new_page_query];
            
            if (!$this->is_permalink()){
                $this->preg_replace_old[]='#('.home_url().'(/\?)[0-9a-z=_/.&\-;]*)((page_id|pagename)=)#';
                $this->preg_replace_new[]='$1'.$new_page_query.'=' ;                
            }
        }
     
        $new_page_base= trim($this->opt('page_base'), '/ ');	
        
		if ( $new_page_base && $this->is_permalink()) {
		  
		   $wp_rewrite->page_base = $new_page_base;
           $wp_rewrite->page_structure = $wp_rewrite->root .'/'.$new_page_base.'/'. '%pagename%'; 
           
		}
        
        $post_enable=$this->opt('post_enable');
       
        if (!$post_enable && !is_admin()) {
            unset($_GET['p']);
           
            unset($_GET[$this->opt('post_query')]);	
        }  
              
        $new_post_query= $this->opt('post_query');
        
        if ($new_post_query && $new_post_query!='p' && !is_admin()) {
            $wp->add_query_var($new_post_query);
            
            if (isset($_GET['p']))
                unset($_GET['p']);
                
            if (isset($_GET[$new_post_query]) && is_numeric($_GET[$new_post_query]) )
                $_GET['p']=$_GET[$new_post_query];
            
            if (!$this->is_permalink()){
                $this->preg_replace_old[]='#('.home_url().'(/\?)[0-9a-z=_/.&\-;]*)(p=)#';
                $this->preg_replace_new[]='$1'.$new_post_query.'=' ;                
            } 
        }
        
        
      if (basename($_SERVER['PHP_SELF']) == 'options-permalink.php' && isset($_POST['permalink_structure']) ){
            $this->options['post_base'] = $_POST['permalink_structure'];
            update_option(self::slug, $this->options);
         
      } 
      
        
        $category_enable=$this->opt('category_enable');
       
        if (!$category_enable && !is_admin()) {
            unset($_GET['cat']);
            unset($_GET[$this->opt('category_name')]);	
        }  
              
        $new_category_query= $this->opt('category_query');
        
        if ($new_category_query && $new_category_query!='cat' && !is_admin()) {
            $wp->add_query_var($new_category_query);
            
            unset($_GET['cat']);
            unset($_GET['category_name']);
            if (isset($_GET[$new_category_query]) && is_numeric($_GET[$new_category_query]) )
                $_GET['cat']=$_GET[$new_category_query];
                
            if (isset($_GET[$new_category_query]) && !is_numeric($_GET[$new_category_query]) )
                $_GET['category_name']=$_GET[$new_category_query];
                
            if (!$this->is_permalink()){
                $this->preg_replace_old[]='#('.home_url().'(/\?)[0-9a-z=_/.&\-;]*)((cat|category_name)=)#';
                $this->preg_replace_new[]='$1'.$new_category_query.'=' ;                
            }
        }

        if (basename($_SERVER['PHP_SELF']) == 'options-permalink.php' && isset($_POST['category_base']) ){
            $this->options['category_base'] = $_POST['category_base'];
            update_option(self::slug, $this->options); 
        }
        
        $tag_enable=$this->opt('tag_enable');
       
        if (!$tag_enable && !is_admin()) {
            unset($_GET['tag']);	
        }  
              
        $new_tag_query= $this->opt('tag_query');
        
        if ($new_tag_query && $new_tag_query!='tag' && !is_admin()) {
            $wp->add_query_var($new_tag_query);
            
            unset($_GET['tag']);
            if (isset($_GET[$new_tag_query])  )
                $_GET['tag']=$_GET[$new_tag_query];
                
            if (!$this->is_permalink()){
                $this->preg_replace_old[]='#('.home_url().'(/\?)[0-9a-z=_/.&\-;]*)(tag=)#';
                $this->preg_replace_new[]='$1'.$new_tag_query.'=' ;                
            }
        }

        
        if (basename($_SERVER['PHP_SELF']) == 'options-permalink.php' && isset($_POST['tag_base']) ){
            $this->options['tag_base'] = $_POST['tag_base'];
            update_option(self::slug, $this->options); 
        } 
            
        
        if ($this->opt('disable_archive') && !is_admin()) {
            unset($_GET['year']);
            unset($_GET['m']);
            unset($_GET['w']);
            unset($_GET['day']);
            unset($_GET['hour']);
            unset($_GET['minute']);
            unset($_GET['second']);
            
            unset($_GET['calendar']);
            unset($_GET['monthnum']);
        }
        
        
        if ($this->opt('disable_other_wp') && !is_admin()) {
            unset($_GET['post_type']);
            unset($_GET['cpage']);
            unset($_GET['term']);
            unset($_GET['taxonomy']);   
            unset($_GET['robots']);
            
            unset($_GET['attachment_id']);
            unset($_GET['attachment']);
            
            unset($_GET['withcomments']);
            unset($_GET['withoutcomments']);
            
            unset($_GET['orderby']);
            unset($_GET['order']);
            
            //There's still a little more but we ignore them
        }
                	
        
        if ($this->opt('remove_other_meta')){
            //Remove generator name and version from your Website pages and from the RSS feed.
            add_filter('the_generator', create_function('', 'return "";'));
            //Display the XHTML generator that is generated on the wp_head hook, WP version
            remove_action( 'wp_head', 'wp_generator' ); 
            //Remove the link to the Windows Live Writer manifest file.
            remove_action('wp_head', 'wlwmanifest_link'); 
            //Remove EditURI
            remove_action('wp_head', 'rsd_link');
            //Remove index link.
            remove_action('wp_head', 'index_rel_link');
            //Remove previous link.
            remove_action('wp_head', 'parent_post_rel_link', 10, 0);      
            //Remove start link.
            remove_action('wp_head', 'start_post_rel_link', 10, 0);
            //Remove relational links (previous and next) for the posts adjacent to the current post.
            remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);
            //Remove shortlink if it is defined.
            remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);
            
            $this->replace_old[]='<link rel="profile" href="#" />';
            $this->replace_new[]='';
            
            $this->replace_old[]='<link rel="pingback" href="'. get_bloginfo( 'pingback_url' ).'" />';
            $this->replace_new[]='';
             
            //Added from roots
            if (!class_exists('WPSEO_Frontend')) 
                remove_action('wp_head', 'rel_canonical'); 
        }
           
         if ( $this->opt('new_style_path') && $this->is_permalink() && !isset($_POST['wp_customize']) )  {
            
            $rel_style_path = trim(str_replace(site_url(),'', get_stylesheet_uri()), '/');
            
            $new_style_path = trim($this->opt('new_style_path'), '/ ') ;
            $new_style_path = str_replace('[theme_path]', trim($this->opt('new_theme_path'),' /'), $new_style_path) ;
            $new_style_path = str_replace('.', '\.', $new_style_path) ;
            
            $wp->add_query_var('style_wrapper');
           	$wp_rewrite->add_rule($new_style_path, 'index.php?style_wrapper=true', 'top') ;
            $this->replace_old[] = $rel_style_path;
            $this->replace_new[] = str_replace('\.', '.', $new_style_path);
            
            add_action('wp', array( &$this, 'global_css_filter'));
            if ($this->opt('clean_new_style') )  {
                $old = array ('wp-caption', 'alignright', 'alignleft','alignnone', 'aligncenter');
                $new = array ('x-caption', 'x-right', 'x-left','x-none', 'x-center');

                $this->replace_old = array_merge($this->replace_old, $old);
                $this->replace_new = array_merge($this->replace_new, $new);
                
                $this->preg_replace_old[]='#wp\-(image|att)\-[0-9]*#';
                $this->preg_replace_new[]='';
                
            }
        }
            
        
        
        //These 3 should be after page base so get_permalink in block access should work correctly
        if ($this->opt('hide_wp_admin') && !$is_trusted)  {
            if ( $this->h->str_contains($_SERVER['PHP_SELF'], '/wp-admin/')) { 
                if (!$this->h->ends_with($_SERVER['PHP_SELF'], '/admin-ajax.php')) {
                    $this->block_access();
                }        
            }
        }                           
        
        //$is_trusted: When user request xmlrpc.php current user will be set to 0 by WP so only admin key works    
        if ($this->opt('avoid_direct_access') && !$is_trusted)  {   
            if ( $this->h->ends_with($_SERVER['PHP_SELF'], '.php') && !$this->h->str_contains($_SERVER['PHP_SELF'], '/wp-admin/')) {
                $white_list= explode(",", $this->opt('direct_access_except'));
                $white_list[]='wp-login.php';
                $white_list[]='index.php';
                $block = true;
                
                foreach ($white_list as $white_file) {
                    if ($this->h->ends_with($_SERVER['PHP_SELF'], trim($white_file,', \r\n'))) 
                        $block= false;
                }
                
                if ($block)
                    $this->block_access();      
            }
        }
             
        if ($this->opt('hide_wp_login') && !$is_trusted)  {
            if ($this->h->ends_with($_SERVER['PHP_SELF'], '/wp-login.php') ) { 
                $this->block_access();
            }                
        }
        
        //Fix a WooCommerce problem
        if (function_exists('woocommerce_get_page_id') && trim($this->opt('page_base'),' /') )  {
             $this->replace_old []= get_permalink(woocommerce_get_page_id('shop')); 
             $this->replace_new []= str_replace(trim($this->opt('page_base'),' /').'/', '', get_permalink(woocommerce_get_page_id('shop')));
        }
        
        //We only need replaces in this line. htaccess related works don't work here. They need flush and generate_rewrite_rules filter
	    $this->add_rewrite_rules($wp_rewrite);

     
    }
    
    /**
     * SeureMyWP::remove_default_description()
     * 
     * @param mixed $bloginfo
     * @return
     */
    function remove_default_description($bloginfo) {
        return ($bloginfo == 'Just another WordPress site') ? '' : $bloginfo;
    }



    /**
     * SeureMyWP::add_rewrite_rules()
     * 
     * @param mixed $wp_rewrite
     * @return
     */
    function add_rewrite_rules( $wp_rewrite ) 
    {  
        global $wp_rewrite, $wp;                  
       
        //Order is important 
        if ($this->opt('rename_plugins') && $this->opt('new_plugin_path') && $this->is_permalink()) {
            foreach ((array) get_option('active_plugins') as $active_plugin)  {

                //Ignore itself or a plugin without folder 
                if ( !$this->h->str_contains($active_plugin,'/') || $active_plugin==self::main_file)
                    continue;
                    
                $new_plugin_path = str_replace('[theme_path]', trim($this->opt('new_theme_path'),' /'), $this->opt('new_plugin_path')) ;
                $new_plugin_path = trim($new_plugin_path, '/ ') ;
            
                //This is not just a line of code. I spent around 2 hours for this :|
                $codename_this_plugin=  hash('crc32', $active_plugin );
                $rel_this_plugin_path = trim(str_replace(site_url(),'', plugin_dir_url($active_plugin)), '/');
                //Allows space in plugin folder name 
                $rel_this_plugin_path=str_replace(' ','\ ', $rel_this_plugin_path);  
                
                $new_this_plugin_path = $new_plugin_path . '/' . $codename_this_plugin ;
                $new_non_wp_rules[$new_this_plugin_path.'/(.*)'] = $rel_this_plugin_path.'/$1';
                    
                $this->replace_old[]=$rel_this_plugin_path;
                $this->replace_new[]=$new_this_plugin_path;
                
    
            }
        }  
        
        if ($this->opt('new_include_path') && $this->is_permalink()){     
            $rel_include_path = trim(WPINC);
            
            $new_include_path = str_replace('[theme_path]', trim($this->opt('new_theme_path'),' /'), $this->opt('new_include_path')) ;
            $new_include_path = str_replace('[plugin_path]', trim($this->opt('new_plugin_path'),' /'), $new_include_path) ;
            $new_include_path = trim($new_include_path, '/ ') ; 
            $new_non_wp_rules[$new_include_path.'/(.*)'] = $rel_include_path.'/$1';
            
            $this->replace_old[]=$rel_include_path;
            $this->replace_new[]=$new_include_path;
        }
        
        
        if ($this->opt('new_upload_path') && $this->is_permalink()){
            $upload_path=wp_upload_dir();
            $rel_upload_path = trim(str_replace(site_url(),'', $upload_path['baseurl']), '/');;
                                             
            $new_upload_path = str_replace('[theme_path]', trim($this->opt('new_theme_path'),' /'), $this->opt('new_upload_path') ) ;
            $new_upload_path = str_replace('[plugin_path]', trim($this->opt('new_plugin_path'),' /'), $new_upload_path) ;
            
            $new_upload_path = trim($new_upload_path, '/ ') ; 
            $new_non_wp_rules[$new_upload_path.'/(.*)'] = $rel_upload_path.'/$1';
            
            $this->replace_old[]=$rel_upload_path;
            $this->replace_new[]=$new_upload_path;
        }
        
        
        if ($this->opt('new_plugin_path') && $this->is_permalink()){    
            $rel_plugin_path = trim(str_replace(site_url(),'', WP_PLUGIN_URL), '/');
            
            $new_plugin_path = str_replace('[theme_path]', trim($this->opt('new_theme_path'),' /'), $this->opt('new_plugin_path') ) ;
            $new_plugin_path = trim($new_plugin_path, '/ ') ;
            $new_non_wp_rules[$new_plugin_path.'/(.*)'] = $rel_plugin_path.'/$1';
            
            $this->replace_old[]=$rel_plugin_path;
            $this->replace_new[]=$new_plugin_path;  
        }
        
        
        if ($this->opt('new_theme_path') && $this->is_permalink() && !isset($_POST['wp_customize'])){
            $rel_theme_path = trim(str_replace(site_url(),'', get_stylesheet_directory_uri()), '/');
            
            $new_theme_path = trim($this->opt('new_theme_path'), '/ ') ;
            $new_non_wp_rules[$new_theme_path.'/(.*)'] = $rel_theme_path.'/$1';
            
            $this->replace_old[]=$rel_theme_path;
            $this->replace_new[]=$new_theme_path;    
        }
        
       
        if ($this->opt('replace_admin_ajax') && trim($this->opt('replace_admin_ajax'), '/ ')!='admin-ajax.php' && trim($this->opt('replace_admin_ajax') )!='wp-admin/admin-ajax.php' && $this->is_permalink())  {
            $this->options['replace_admin_ajax'] = trim($this->opt('replace_admin_ajax'), '/ ');
            $admin_ajax = str_replace('.','\\.',$this->options['replace_admin_ajax'] );
            $new_non_wp_rules[$admin_ajax] = 'wp-admin/admin-ajax.php';
            
            $this->replace_old[]='wp-admin/admin-ajax.php';
            $this->replace_new[]= $this->options['replace_admin_ajax'];      
        }
         
        if ($this->opt('replace_comments_post') && trim($this->opt('replace_comments_post'), '/ ')!='wp-comments-post.php' && $this->is_permalink())        {
            $this->options['replace_comments_post'] = trim($this->opt('replace_comments_post'), '/ ');
            $comments_post = str_replace('.','\\.', $this->options['replace_comments_post'] );
            $new_non_wp_rules[$comments_post] = 'wp-comments-post.php';
            
            $this->replace_old[]='wp-comments-post.php';
            $this->replace_new[]= $this->options['replace_comments_post'];      
        }   
            
         
        if ($this->opt('hide_other_wp_files') && $this->is_permalink()){
            $rel_content_path = trim(str_replace(site_url(),'', WP_CONTENT_URL), '/');
            $rel_plugin_path = trim(str_replace(site_url(),'', WP_PLUGIN_URL), '/');
            $rel_theme_path_with_theme = trim(str_replace(site_url(),'', get_stylesheet_directory_uri()), '/');
            $rel_theme_path= str_replace('/'.get_stylesheet(), '', $rel_theme_path_with_theme);
            $rel_include_path = trim(WPINC);
            
            $style_path_reg='';
            if ($this->opt('new_style_path') && !isset($_POST['wp_customize']))
                $style_path_reg = '|'.$rel_theme_path_with_theme.'/style\.css';
                
            $new_non_wp_rules['readme\.html|license\.txt|'.$rel_content_path.'/debug\.log'.$style_path_reg.'|'.$rel_include_path.'/$'] = 'nothing_404_404';   
        }
        
        
        
        
        
        if ($this->opt('avoid_direct_access') )  {   
            $white_list= explode(",", $this->opt('direct_access_except'));
            $white_list[]='wp-login.php';
            $white_list[]='index.php';
            $white_list[]='wp-admin/';
            $block = true;
            $white_regex = '';
            foreach ($white_list as $white_file) {
                 $white_regex.=str_replace(array('.', ' '), array('\.',''), $white_file ).'|';  //make \. remove spaces 
            }
            $white_regex=substr($white_regex, 0 ,strlen($white_regex)-1); //remove last |
            $new_non_wp_rules['('.$white_regex.')(.*)'] = '$1$2';
            $new_non_wp_rules['(.*)\.php$'] = 'nothing_404_404';
            $new_non_wp_rules['(.*)/lostpassword/$']='wp-login.php?secure_wp=$1&action=lostpassword';
            $new_non_wp_rules['(.*)/logout/$']='wp-login.php?secure_wp=$1';
            $new_non_wp_rules['(.*)/$'] = 'wp-login.php?secure_wp=$1';
            add_filter('mod_rewrite_rules', array(&$this, 'mod_rewrite_rules'),10, 1);      
        }
       
        if (isset($new_non_wp_rules) && $this->is_permalink())
           $wp_rewrite->non_wp_rules = array_merge($wp_rewrite->non_wp_rules, $new_non_wp_rules);
        return $wp_rewrite;              

    }
    /**
     * SeureMyWP::mod_rewrite_rules()
     * Fix WP generated rules
     * @param mixed $key
     * @return
     */                       
    function mod_rewrite_rules($rules){
       $home_root = parse_url(home_url());
  		if ( isset( $home_root['path'] ) )
			$home_root = trailingslashit($home_root['path']);
		else
			$home_root = '/';
            
        $rules=str_replace('(.*) '.$home_root.'$1$2 ', '(.*) $1$2 ', $rules);    
        return $rules;    
    }


	/**
	 * SeureMyWP::on_activate_callback()
	 * 
	 * @return
	 */
	function on_activate_callback() {
        flush_rewrite_rules();
	}
	
	/**
	 * Register deactivation hook
	 * SeureMyWP::on_deactivate_callback()
	 * 
	 * @return
	 */
	function on_deactivate_callback() {
   delete_option( 'custom_login' );
   delete_option( '_transient_custom_login_style' );
        flush_rewrite_rules();
	} 
    
    /**
     * SeureMyWP::opt()
     * Get options value
     * @param mixed $key
     * @return
     */
    function opt($key){
        if (isset($this->options[$key]))
            return $this->options[$key];
        return false;  
    }    
    
    
    /**
	 * Register settings page
	 *
	 */
	/**
	 * SeureMyWP::register_settings()
	 * 
	 * @return
	 */
	function register_settings() {
		$sections = array(
           array(
                'id' => 'general',
                'title' => __( 'Login Settings', self::slug )
            ),
           array(
                'id' => 'additional',
                'title' => __( 'Additional Settings', self::slug )
            ),
            array(
                'id' => 'permalink',
                'title' => __( 'Permalinks', self::slug )
            ),
            array(
                'id' => 'urls',
                'title' => __( 'URLs', self::slug )
            ),
            array(
                'id' => 'start',
                'title' => __( 'Import & Export Options', self::slug )
            )
                                
        );    
                
        $fields['permalink'] = array(
                    array(
                        'name' => 'new_theme_path',
                        'label' => __( 'Give your own new theme path', self::slug ),
                        'desc' => __( 'Change wordpress theme path e.g. "/template"', self::slug ),
                        'type' => 'text',
                        'default' => '',
                        'class' =>'permalink_req'
                        ),
                        
                     array(
                        'name' => 'new_style_path',
                        'label' => __( 'Give your own new style path', self::slug ),
                        'desc' => __( 'Change wordpress theme style path e.g. "/template/main.css" or /style', self::slug ),
                        'type' => 'text',
                        'default' => '',
                        'class' =>'permalink_req'
                        ),
                     
                     array(
                        'name' => 'minify_new_style',
                        'label' => __( 'Remove comments and WP details', self::slug ),
                        'desc' => __( 'Remove comments and WP details and compress it (Require new style path).', self::slug ),
                        'type' => 'checkbox',
                        'default' => '',
                        'class' =>'permalink_req'
                        ),
                     array(
                        'name' => 'clean_new_style',
                        'label' => __( 'Replace WP classes', self::slug ),
                        'desc' => __( 'Replace WP classes (wp-caption, etc) with their "x-" version e.g x-caption (Require new style path).', self::slug ),
                        'type' => 'checkbox',
                        'default' => '',
                        'class' =>'permalink_req'
                        ),
                           
                     array(
                        'name' => 'new_include_path',
                        'label' => __( 'Give your own new wp-includes path', self::slug ),
                        'desc' => __( 'Change wordpress wp-include folder path e.g. "/lib"', self::slug ),
                        'type' => 'text',
                        'default' => '',
                        'class' =>'permalink_req'
                        )
                     ,
                     array(
                        'name' => 'new_plugin_path',
                        'label' => __( 'Give your own new plugin path', self::slug ),
                        'desc' => __( 'Change plugin folder path e.g. "/modules"', self::slug ),
                        'type' => 'text',
                        'default' => '',
                        'class' =>'permalink_req'
                        ),
                     
                     
                     array(
                        'name' => 'rename_plugins',
                        'label' => __( 'Rename Plugins', 'wedevs' ),
                        'desc' => __( 'Change each plugin folder name with a codename (Require new plugin path).', self::slug ),
                        'type' => 'checkbox',
                        'default' => '',
                        'class' =>'permalink_req'
                       
                        )
                     ,
                     array(
                        'name' => 'new_upload_path',
                        'label' => __( 'Give your own new upload path', self::slug ),
                        'desc' => __( 'e.g. "/file"', self::slug ),
                        'type' => 'text',
                        'default' => '',
                        'class' =>'permalink_req'
                        ) 
                    ,
                    array(
                        'name' => 'replace_comments_post',
                        'label' => __( 'Post Comment', self::slug ),
                        'desc' => __( 'Change "wp_comments_post.php" URL (e.g. "/user_submit" or "/folder/user_submit.php").', self::slug ),
                        'type' => 'text',
                        'default' => '',
                        'class' =>'permalink_req'
                        )
                     ,
                     array(
                        'name' => 'replace_admin_ajax',
                        'label' => __( 'AJAX URL', self::slug ),
                        'desc' => __( 'Change wp-admin/admin_ajax.php URL (e.g. "/ajax" or "ajax.php").', self::slug ),
                        'type' => 'text',
                        'default' => '',
                        'class' =>'permalink_req'
                        )
                       );
        
        $fields['urls'] = array(
                    
                    array(
                        'name' => 'author_enable',
                        'label' => __( 'Author', self::slug ),
                        'desc' => '',
                        'type' => 'select',
                        'default' => '1',
                        'class' =>'opener',
                        'options' => array(
                            '1' => __( 'Enable Authors URL', self::slug ),
                            '0' => __( 'Disable Authors URL', self::slug )
                            )
                        ),
                    array(
                        'name' => 'author_base',
                        'label' => __( 'Author Base', self::slug ),
                        'desc' => __( 'Change "/author/username" (e.g. user, profile, members/editrs/).', self::slug ),
                        'type' => 'text',
                        'default' => '/author',
                        'class' =>' open_by_author_enable_1 permalink_req'
                        ) 
                    ,
                    array(
                        'name' => 'author_query',
                        'label' => __( 'Author Query', self::slug ),
                        'desc' => __( 'Change /?author=1 and /?author_name=username (e.g. u, user, member).', self::slug ),
                        'type' => 'text',
                        'default' => 'author',
                        'class' =>' open_by_author_enable_1'
                        )
                    ,
                    array(
                        'name' => 'author_without_base',
                        'label' => __( 'Author without base', self::slug ),
                        'desc' => __( 'Use username directly and without base (e.g. domain.com/admin).', self::slug ),
                        'type' => 'checkbox',
                        'default' => '',
                        'class' =>'open_by_author_enable_1 permalink_req'
                        )
                    ,
                    array(
                        'name' => 'feed_enable',
                        'label' => __( 'Feeds', self::slug ),
                        'desc' => '',
                        'type' => 'select',
                        'default' => '1',
                        'class' =>'opener',
                        'options' => array(
                            '1' => __( 'Enable Feeds URL', self::slug ),
                            '0' => __( 'Disable All Feeds URL' , self::slug )
                            )
                        ),
                    array(
                        'name' => 'feed_base',
                        'label' => __( 'Feed Base', self::slug ),
                        'desc' => __( 'Change /feed (e.g. xml, rss, index.xml).', self::slug ),
                        'type' => 'text',
                        'default' => '/feed',
                        'class' =>' open_by_feed_enable_1 permalink_req'
                        ) 
                    ,
                      
                    array(
                        'name' => 'feed_query',
                        'label' => __( 'Feed Query', self::slug ),
                        'desc' => __( 'Change /?feed=rss2 (e.g. xml, rss, sitefeed).', self::slug ),
                        'type' => 'text',
                        'default' => 'feed',
                        'class' =>' open_by_feed_enable_1'
                        )  
                    ,
                     
                     array(
                        'name' => 'post_enable',
                        'label' => __( 'Post', self::slug ),
                        'desc' => '',
                        'type' => 'select',
                        'default' => '1',
                        'class' =>'opener',
                        'options' => array(
                            '1' => __('Enable Posts URL', self::slug ),
                            '0' => __('Disable Posts URL', self::slug )
                            )
                     ),
                    array(
                        'name' => 'post_base',
                        'label' => __( 'Post Permalink', self::slug ),
                        'desc' => __( 'Change default WP post permalink.', self::slug ),
                        'type' => 'text',
                        'default' =>'/%year%/%postname%/',
                        'class' =>' open_by_post_enable_1 permalink_req'
                        ) 
                    ,
                    array(
                        'name' => 'post_query',
                        'label' => __( 'Post Query', self::slug ),
                        'desc' => __( 'Change /?p=1 (e.g. article_id, news_id or pid).', self::slug ),
                        'type' => 'text',
                        'default' => 'p',
                        'class' =>' open_by_post_enable_1'
                        )
                    ,
                    array(
                        'name' => 'page_enable',
                        'label' => __( 'Page', self::slug ),
                        'desc' => '',
                        'type' => 'select',
                        'default' => '1',
                        'class' =>'opener',
                        'options' => array(
                            '1' => __('Enable Pages URL', self::slug ),
                            '0' => __('Disable Pages URL', self::slug )
                            )
                     ),
                    array(
                        'name' => 'page_base',
                        'label' => __( 'Page Base', self::slug ),
                        'desc' => __( 'Change /sample-page to /X/sample-page (e.g. pages, static).', self::slug ),
                        'type' => 'text',
                        'default' => '/page',
                        'class' =>' open_by_page_enable_1 permalink_req'
                        ) 
                    ,
                    array(
                        'name' => 'page_query',
                        'label' => __( 'Page Query', self::slug ),
                        'desc' => __( 'Change /?page_id=1 or /?page_name=about (e.g. pages).', self::slug ),
                        'type' => 'text',
                        'default' => 'page_id',
                        'class' =>' open_by_page_enable_1'
                        )
                    ,
                    
                    array(
                        'name' => 'paginate_enable',
                        'label' => __( 'Paginate', self::slug ),
                        'desc' => '',
                        'type' => 'select',
                        'default' => '1',
                        'class' =>'opener',
                        'options' => array(
                            '1' => __( 'Enable Paginates URL', self::slug ),
                            '0' => __( 'Disable Paginates URL', self::slug )
                            )
                        ),
                    array(
                        'name' => 'paginate_base',
                        'label' => __( 'Paginate Base', self::slug ),
                        'desc' => __( 'Change /page/2 (e.g. pages, go).', self::slug ),
                        'type' => 'text',
                        'default' => '/pages',
                        'class' =>' open_by_paginate_enable_1 permalink_req'
                        ) 
                    ,
                    array(
                        'name' => 'paginate_query',
                        'label' => __( 'Paginate Query', self::slug ),
                        'desc' => __( 'Change /?paged=2.', self::slug ),
                        'type' => 'text',
                        'default' => 'paged',
                        'class' =>' open_by_paginate_enable_1'
                        )
                    ,
                    array(
                        'name' => 'category_enable',
                        'label' => __( 'Category', self::slug ),
                        'desc' => '',
                        'type' => 'select',
                        'default' => '1',
                        'class' =>'opener',
                        'options' => array(
                            '1' => __('Enable Categories URL', self::slug ),
                            '0' => __('Disable Categories URL', self::slug )
                            )
                     ),
                    array(
                        'name' => 'category_base',
                        'label' => __( 'Category Base', self::slug ),
                        'desc' => __( 'Change /category/uncategorized. (e.g. topic, all).', self::slug ),
                        'type' => 'text',
                        'default' => get_option('category_base'),
                        'class' =>' open_by_category_enable_1 permalink_req'
                        ) 
                    ,
                    array(
                        'name' => 'category_query',
                        'label' => __( 'Category Query', self::slug ),
                        'desc' => __( 'Change /?cat=1 or /?category_name=uncategorized (e.g. topic).', self::slug ),
                        'type' => 'text',
                        'default' => 'cat',
                        'class' =>' open_by_category_enable_1'
                        )
                    ,
                    
                    array(
                        'name' => 'tag_enable',
                        'label' => __( 'Tag', self::slug ),
                        'desc' => '',
                        'type' => 'select',
                        'default' => '1',
                        'class' =>'opener',
                        'options' => array(
                            '1' => __('Enable Tags URL', self::slug ),
                            '0' => __('Disable Tags URL', self::slug )
                            )
                     ),
                    array(
                        'name' => 'tag_base',
                        'label' => __( 'Tag Base', self::slug ),
                        'desc' => __( 'Change /tag/tag1 (e.g. keyword, find).', self::slug ),
                        'type' => 'text',
                        'default' => get_option('tag_base'),
                        'class' =>' open_by_tag_enable_1 permalink_req'
                        ) 
                    ,
                    array(
                        'name' => 'tag_query',
                        'label' => __( 'Tag Query', self::slug ),
                        'desc' => __( 'Change /?tag=tag1 (e.g. keyword, find).', self::slug ),
                        'type' => 'text',
                        'default' => 'tag',
                        'class' =>' open_by_tag_enable_1'
                        )
                    ,

                    array(
                        'name' => 'search_enable',
                        'label' => __( 'Search', self::slug ),
                        'desc' => '',
                        'type' => 'select',
                        'default' => '1',
                        'class' =>'opener',
                        'options' => array(
                            '1' => __('Enable Search', self::slug ),
                            '0' => __('Disable Search' , self::slug )
                            )
                        ),
                    array(
                        'name' => 'search_base',
                        'label' => __( 'Search Base', self::slug ),
                        'desc' => __( 'Change /search/keyword (e.g. find, s, dl).', self::slug ),
                        'type' => 'text',
                        'default' => '/search',
                        'class' =>' open_by_search_enable_1 permalink_req'
                        ) 
                    ,
                    array(
                        'name' => 'search_query',
                        'label' => __( 'Search Query', self::slug ),
                        'desc' => __( 'Change /?s=keyword (e.g. find, s, dl).', self::slug ),
                        'type' => 'text',
                        'default' => 's',
                        'class' =>' open_by_search_enable_1'
                        )
                    , 
                    array(
                        'name' => 'nice_search_redirect',
                        'label' => __( 'Search base redirect', self::slug ),
                        'desc' => __( 'Redirect all search queries to permalink (e.g. /search/test instead /?s=test).', self::slug ),
                        'type' => 'checkbox',
                        'default' => 'on',
                        'class' =>'open_by_search_enable_1 permalink_req'
                        )
                     , 
                    
                    array(
                        'name' => 'disable_archive',
                        'label' => __( 'Disable Archive', self::slug ),
                        'desc' => __( 'Disable archive queries (yearly, monthly or daily archives).', self::slug ),
                        'type' => 'checkbox',
                        'default' => '',
                        'class' =>''
                        )
                    , 
                    array(
                        'name' => 'disable_other_wp',
                        'label' => __( 'Disable Other WP', self::slug ),
                        'desc' => __( 'Disable other WordPress queries like post type, taxonamy, attachments, comment page etc. Post types may be used by themes or plugins.', self::slug ),
                        'type' => 'checkbox',
                        'default' => '',
                        'class' =>''
                        )
        
            );
                         
        $fields['general'] = 
                array(
                         array(
                            'name' => 'custom_404',
                            'label' => __( 'Choose 404 Page', self::slug ),
                            'desc' => __( '', self::slug ),
                            'type' => 'radio',
                            'default' => '0',
                            'class' =>'opener',
                            'options' => array(
                                '0' => 'Default 404 page from theme',
                                '1' => 'Choose a custom page'
                                )
                            )
                        ,      
                        array(
                            'name' => 'custom_404_page',
                            'label' => __( 'Custom 404 Page', self::slug ),
                            'desc' => __( 'You can use this page as 404 page.', self::slug ),
                            'type' => 'pagelist',
                            'default' => '',
                            'class' =>'open_by_custom_404_1'
                            )
                        ,
                        array(
                            'name' => 'trusted_user_roles',
                            'label' => __( 'User Roles', self::slug ),
                            'desc' => __( 'Choose trusted user roles. (Administrator are trusted by default)', self::slug ),
                            'type' => 'rolelist',
                            'class' =>''
                            
                        )  
                        ,
                        array(
                            'name' => 'hide_wp_login',
                            'label' => __( 'Hide Login Page', self::slug ),
                            'desc' => __( 'Hide wp-login.php. [<b>Important:</b> You need to remember new address to login!]', self::slug ),
                            'type' => 'checkbox',
                            'default' => '',
                            'class' =>'opener'
                            )
                        ,
                        array(
                            'name' => 'admin_key',
                            'label' => __( 'Choose Admin Login', self::slug ),
                            'desc' => sprintf(__( '<br>URL: %s <a title="Bookmark Your Login Path" href="%s"><img class="bookmark" src="'.plugins_url().'/siri-wp-security/img/Books-icon.png"></a>', self::slug ), '<span style="font-weight: bold;color: #21759B;">'.site_url().'/'.$this->opt('admin_key').'/</span>', site_url($this->opt('admin_key')).'/' ),
                            'type' => 'text',
                            'default' => '1234',
                            'class' =>'open_by_hide_wp_login'
                            )
                        ,
                        array(
                            'name' => 'hide_wp_admin',
                            'label' => __( 'Hide Admin', self::slug ),
                            'desc' => __( 'Hide wp-admin folder and its files for untrusted users.', self::slug ),
                            'type' => 'checkbox',
                            'default' => 'on',
                            'class' =>''
                            )
                        ,
                        array(
                            'name' => 'spy_notifier',
                            'label' => __( 'Send Notifications to admin', self::slug ),
                            'desc' => __( 'Send an email to site admin whenever someone visits 404 page!', self::slug ),
                            'type' => 'checkbox',
                            'default' => '',
                            'class' =>''
                            )
                      );
        $fields['additional'] = 
                array(
                        array(
                            'name' => 'remove_feed_meta',
                            'label' => __( 'Feed Meta', self::slug ),
                            'desc' => __( 'Remove auto-generated feeds from header.', self::slug ),
                            'type' => 'checkbox',
                            'default' => 'on',
                            'class' =>'open_by_feed_enable_1'
                            )
                        ,    
                         array(
                            'name' => 'remove_other_meta',
                            'label' => __( 'Other Meta', self::slug ),
                            'desc' => __( 'Remove other header metas like short link, previous and next links, etc.', self::slug ),
                            'type' => 'checkbox',
                            'default' => 'on',
                            'class' =>''
                            )
                         ,
                        array(
                            'name' => 'hide_admin_bar',
                            'label' => __( 'Hide Admin Bar', self::slug ),
                            'desc' => __( 'Hide admin bar for untrusted users.', self::slug ),
                            'type' => 'checkbox',
                            'default' => 'on',
                            'class' =>''
                            )

                         ,
      
                         array(
                            'name' => 'remove_default_description',
                            'label' => __( 'Default Tagline', self::slug ),
                            'desc' => __( 'Remove \'Just another WordPress blog\' from your feed.', self::slug ),
                            'type' => 'checkbox',
                            'default' => 'on',
                            'class' =>''
                            )
                         ,
                        
                         array(
                            'name' => 'remove_html_comments',
                            'label' => __( 'Compress Page', self::slug ),
                            'desc' => __( 'Remove whitespaces and inline comments from html output (for untrusted user).', self::slug ),
                            'type' => 'checkbox',
                            'default' => 'on',
                            'class' =>''
                            )
                         ,

                         array(
                            'name' => 'avoid_direct_access',
                            'label' => __( 'Hide PHP Files', self::slug ),
                            'desc' => __( 'Avoid direct access to php files (include plugins file, except wp-admin) (Recommended).', self::slug ),
                            'type' => 'checkbox',
                            'default' => 'on',
                            'class' =>'opener'
                            ),
                         array(
                            'name' => 'direct_access_except',
                            'label' => __( 'Except Files', self::slug ),
                            'desc' => __( 'Except these files (or folders). Separate with comma(,)', self::slug ),
                            'type' => 'textarea',
                            'default' => 'index.php, wp-content/repair.php, wp-comments-post.php, wp-includes/js/tinymce/wp-tinymce.php',
                            'class' =>'open_by_avoid_direct_access'
                            )
                         ,
                         array(
                            'name' => 'hide_other_wp_files',
                            'label' => __( 'Hide Other Files', self::slug ),
                            'desc' => __( 'Hide license.txt, wp-includes, wp-content/debug.log, etc.', self::slug ),
                            'type' => 'checkbox',
                            'default' => '',
                            'class' =>'permalink_req'
                            )     
                          ,
                          array(
                            'name' => 'disable_canonical_redirect',
                            'label' => __( 'Canonical Redirect', self::slug ),
                            'desc' => __( 'Disable canonical redirect. This is requiring when you want to use URL queries.', self::slug ),
                            'type' => 'checkbox',
                            'default' => 'on',
                            'class' =>''
                            
                         ),
                         array(
                            'name' => 'email_from_name',
                            'label' => __( 'Email sender name', self::slug ),
                            'desc' => __( 'e.g.  Siri', self::slug ),
                            'type' => 'text',
                            'default' => '',
                            'class' =>''
                            )
                         ,
                         array(
                            'name' => 'email_from_address',
                            'label' => __( 'Email sender address', self::slug ),
                            'desc' => __( 'e.g. name@domain.com', self::slug ),
                            'type' => 'text',
                            'default' => '',
                            'class' =>''
                            )
                         ,  
                         array(
                            'name' => 'replace_in_html',
                            'label' => __( 'Replace in HTML', self::slug ),
                            'desc' => __( 'Replace keywords (case-sensitive) in HTML output. One per line (e.g. old=new)', self::slug ),
                            'type' => 'textarea',
                            'default' => '',
                            'class' =>''
                            ) 

                        
                        
                );
        $fields['start'] = 
            array(
                array(
                    'name' => 'import_options',
                    'label' => __( 'Import Options', self::slug ),
                    'desc' => __( 'Paste your settings code below or choose a pre-made settings scheme.', self::slug ),
                    'type' => 'import',
                    'default' => '',
                    'class' =>'',
                    'options' => array(
                             'Low Privacy - More Compatibilty' => '{"custom_404":"0","custom_404_page":"761","admin_key":"1234","remove_feed_meta":"on","hide_admin_bar":"on","remove_other_meta":"on","remove_post_class":"on","remove_menu_class":"on","remove_default_description":"on","remove_ver_scripts":"on","direct_access_except":"index.php, wp-content/repair.php, wp-includes/js/tinymce/wp-tinymce.php, wp-comments-post.php","disable_canonical_redirect":"on","email_from_name":"","email_from_address":"","replace_in_html":"","new_theme_path":"","new_style_path":"","new_include_path":"","new_plugin_path":"","new_upload_path":"","replace_comments_post":"","replace_admin_ajax":"","author_enable":"1","author_base":"","author_query":"","feed_enable":"1","feed_base":"","feed_query":"","post_enable":"1","post_base":"'.get_option('permalink_structure').'","post_query":"","page_enable":"1","page_base":"","page_query":"","paginate_enable":"1","paginate_base":"","paginate_query":"","category_enable":"1","category_base":"'.get_option('category_base').'","category_query":"","tag_enable":"1","tag_base":"'.get_option('tag_base').'","tag_query":"","search_enable":"1","search_base":"","search_query":"","nice_search_redirect":"on","import_options":"","export_options":"","debug_report":"","minify_new_style":"","clean_new_style":"","rename_plugins":"","separator2":"","author_without_base":"","disable_archive":"","disable_other_wp":"","trusted_user_roles":"","hide_wp_login":"","hide_wp_admin":"","spy_notifier":"","separator":"","remove_body_class":"","remove_html_comments":"","avoid_direct_access":"","hide_other_wp_files":""}'
                             ,
                            
                            'Medium Privacy ' => '{"custom_404":"0","custom_404_page":"","hide_wp_login":"on","admin_key":"1234","hide_wp_admin":"on","remove_feed_meta":"on","hide_admin_bar":"on","remove_other_meta":"on","remove_body_class":"on","remove_post_class":"on","remove_menu_class":"on","remove_default_description":"on","remove_html_comments":"on","remove_ver_scripts":"on","avoid_direct_access":"on","direct_access_except":"index.php, wp-content/repair.php, wp-includes/js/tinymce/wp-tinymce.php, wp-comments-post.php","hide_other_wp_files":"","disable_canonical_redirect":"on","email_from_name":"'.get_bloginfo('blogname').'","email_from_address":"noreply@testmail.com","replace_in_html":"","new_theme_path":"/template","new_style_path":"/template/main.css","minify_new_style":"on","clean_new_style":"on","new_include_path":"/lib","new_plugin_path":"/modules","rename_plugins":"on","new_upload_path":"/file","replace_comments_post":"/user_submit.php","replace_admin_ajax":"ajax.php","author_enable":"1","author_base":"profile","author_query":"user","author_without_base":"","feed_enable":"1","feed_base":"index.xml","feed_query":"sitefeed","post_enable":"1","post_base":"'.get_option('permalink_structure').'","post_query":"article_id","page_enable":"1","page_base":"","page_query":"page_num","paginate_enable":"1","paginate_base":"/page/","paginate_query":"go","category_enable":"1","category_base":"'.get_option('category_base').'","category_query":"topic","tag_enable":"1","tag_base":"'.get_option('tag_base').'","tag_query":"keyword","search_enable":"1","search_base":"search","search_query":"find","nice_search_redirect":"on","disable_archive":"on","disable_other_wp":"","import_options":"","export_options":"","debug_report":"","separator2":"","trusted_user_roles":"","spy_notifier":"","separator":""}'
                          ,
                          'High Privacy - Less Compatibility' => '{"custom_404":"0","custom_404_page":"761","hide_wp_login":"on","admin_key":"1234","hide_wp_admin":"on","remove_feed_meta":"on","hide_admin_bar":"on","remove_other_meta":"on","remove_body_class":"on","remove_post_class":"on","remove_menu_class":"on","remove_default_description":"on","remove_html_comments":"on","remove_ver_scripts":"on","avoid_direct_access":"on","direct_access_except":"index.php, wp-content/repair.php, wp-includes/js/tinymce/wp-tinymce.php, wp-comments-post.php","hide_other_wp_files":"on","disable_canonical_redirect":"on","email_from_name":"'.get_bloginfo('blogname').'","email_from_address":"noreply@test-mail.com","replace_in_html":"","new_theme_path":"/template","new_style_path":"/template/main.css","minify_new_style":"on","clean_new_style":"on","new_include_path":"/template/lib","new_plugin_path":"/template/ext","rename_plugins":"on","new_upload_path":"/storage","replace_comments_post":"submit_comment.php","replace_admin_ajax":"ajax","author_enable":"1","author_base":"profile","author_query":"profile","author_without_base":"on","feed_enable":"1","feed_base":"rss.xml","feed_query":"rss","post_enable":"1","post_base":"%category%/%postname%","post_query":"entry","page_enable":"1","page_base":"/page","page_query":"page_num","paginate_enable":"1","paginate_base":"list","paginate_query":"list","category_enable":"1","category_base":"cat","category_query":"category","tag_enable":"1","tag_base":"keyword","tag_query":"keyword","search_enable":"1","search_base":"find","search_query":"find","nice_search_redirect":"on","disable_archive":"on","disable_other_wp":"on","import_options":"","export_options":"","debug_report":"","separator2":"","trusted_user_roles":"","spy_notifier":"","separator":""}',
                            
                           
                    
                    )
                )
                ,
                array(
                    'name' => 'export_options',
                    'label' => __( 'Export Options', self::slug ),
                    'desc' => __( 'Copy your export code and save it somewhere for later use.', self::slug ),
                    'type' => 'export',
                    'default' => '',
                    'class' =>''
                    
                )
                ,
                array(
                    'name' => 'debug_report',
                    'label' => __( 'Debug Report', self::slug ),
                    'desc' => __( 'Provide above report to support team to get better and faster service.', self::slug ),
                    'type' => 'debug_report',
                    'default' => '',
                    'class' =>''
                    
                )
            ); 

        $menu=array(
                    'name' => self::slug,
                    'title' => self::title,
                    'icon_path' => '',
                    'role' => '',
                    'template_file' =>'',
                    'display_metabox' => '1',
                    'plugin_file' => self::main_file ,
                    'action_link' => '<b>Settings</b>'
                   );
        
                
        foreach ($fields as $tab=>$field){
            $i=0;
            foreach ($field as $option) {
                if ($this->h->str_contains($option['class'], 'permalink_req') && !get_option('permalink_structure'))
                    unset($fields[$tab][$i]) ;
                $i++;
            }
        } 
        
        $this->s = new PP_Settings_API($fields, $sections, $menu);
    }

}

$SeureMyWP = new SeureMyWP();

  class Secure_login {
	
	/** Singleton *************************************************************/
	private static $instance;
	
	/**
	 * Version
	 */
	var $version = 'Alpha';
	
	/**
	 * Plugin vars
	 */
	var $id,
		$domain;
	
	/**
	 * Private settings
	 */
	private static $settings_api;
	private static $sections;
	
	/**
	 * Options page
	 */
	public static $options_page;

	/**
	 * Main Instance
	 *
	 * @staticvar 	array 	$instance
	 * @return 		The one true instance
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new secure_login;
			self::$instance->setup_constants();
			self::$instance->plugin_textdomain();
			self::$instance->required_functions();
			self::$instance->init();
		}
		return self::$instance;
	}
	
	/**
	 * To infinity and beyond
	 */
	function init() {
		
		/* vars */
		$this->id		= 'custom_login';
		$this->domain	= 'custom_login';
		
		/* Constants */
		add_action( 'admin_init',							array( $this, 'check_version' ), 1 );
		
		/* Constants */
		add_action( 'init',									array( $this, 'setup_constants' ) );
		
		/* Scripts */
		add_action( 'login_enqueue_scripts',				array( $this, 'enqueue_scripts' ) );
		
		/* Custom jQuery templates */
		add_action( 'login_footer',							array( $this, 'login_footer_jquery' ) );
		
		/* Includes */
		add_action( 'init',									array( $this, 'required_classes' ) );
		add_action( 'init',									array( $this, 'required_functions' ) );
		
		/* Shortcodes */
		add_action( 'init',									array( $this, 'add_shortcodes' ) );
		
		/* Settings */
		add_action( 'admin_init',							array( $this, 'admin_init' ), 9 );
		add_action( 'admin_menu',							array( $this, 'admin_menu' ), 9 );
		
		/* Clear transient cache button */
		add_action( $this->id .
			'_form_bottom_' . $this->id,					array( $this, 'delete_transient_button_output' ) );
			
		/* Delete transient action */
        add_action( 'admin_action_' . 
			$this->id . '-delete_transient',				array( $this, 'delete_custom_login_transient_cache' ) );
			
		/* Notices */
		add_action( 'admin_notices',						array( $this, 'admin_messages' ) );
		
		/* Add a settings page to the plugin menu */

		
		/* Filter in your URL */
		add_filter( 'login_headerurl',						array( $this, 'login_url' ) );
		
		/* Filter in your description */
		add_filter( 'login_headertitle',					array( $this, 'login_title' ) );			
		
		/* Custom HTML */
		add_action( 'login_footer',							array( $this, 'login_footer_html' ) );
                
	}
	

	public function check_version() {
		global $wp_version;
		
		if ( version_compare( $wp_version, '3.5', '<' ) ) {
			add_action( 'admin_notices', array( $this, 'version_notification' ) );
			if ( is_plugin_active( plugin_basename( __FILE__ ) ) ) deactivate_plugins( plugin_basename( __FILE__ ) );
		}
	}
	
	/**
	 * Deactivation notice
	 *
	 */
	public function version_notification() {
		global $wp_version;
		
		$html  = '<div class="error"><p>'; 
		$html .= sprintf( __( 'Custom Login has been deactivated because it requires a WordPress version greater than 3.5. You are running <code>%s</code>', $this->domain ), $wp_version );
		$html .= '</p></div>';
		
		echo $html;
	}
	

	public function setup_constants() {
		
		// Plugin version
		if ( ! defined( 'CUSTOM_LOGIN_VERSION' ) )
			define( 'CUSTOM_LOGIN_VERSION', $this->version );
			
		// Plugin settings
		if ( ! defined( 'CUSTOM_LOGIN_SETTINGS' ) )
			define( 'CUSTOM_LOGIN_SETTINGS', $this->id );

		// Plugin Folder URL
		if ( ! defined( 'CUSTOM_LOGIN_URL' ) )
			define( 'CUSTOM_LOGIN_URL', plugin_dir_url( __FILE__ ) );

		// Plugin Folder Path
		if ( ! defined( 'CUSTOM_LOGIN_DIR' ) )
			define( 'CUSTOM_LOGIN_DIR', plugin_dir_path( __FILE__ ) );

		// Plugin Root File
		if ( ! defined( 'CUSTOM_LOGIN_FILE' ) )
			define( 'CUSTOM_LOGIN_FILE', __FILE__ );
	}
	
	/**
	 * Load the plugin translations
	 *
	 */
	public function plugin_textdomain() {
		load_plugin_textdomain( 'custom-login', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
	

	public function enqueue_scripts() {
		if ( !$this->is_active() )
			return;	
		
		global $cl_css_atts;

		$cl_css_atts = array(
			'version'	=> CUSTOM_LOGIN_VERSION,
		);
		$cl_css_atts = wp_parse_args( get_option( $this->id, array() ), $cl_css_atts );
		
		ob_start();
			echo "<style type=\"text/css\">\n";
				Custom_Login_Templates::get_template_part( 'wp-login', 'style' );
			echo "\n</style>";
		echo ob_get_clean();
		
		/* Custom jQuery */
		$jquery = $this->get_option( 'custom_jquery', $this->id );
		if ( !empty( $jquery ) ) {
			wp_enqueue_script( array( 'jquery' ) );
		}
	}
	
	/**
	 * Database access to the scripts and styles.
	 *
	 * @since	2.1
	 * @return string|void
	 */
	public function login_footer_jquery() {		
		$jquery = $this->get_option( 'custom_jquery', $this->id );
		if ( !empty( $jquery ) ) :
					
			global $cl_js_atts;
		
			$cl_js_atts = array(
				'version'	=> CUSTOM_LOGIN_VERSION,
			);
			$cl_js_atts = wp_parse_args( get_option( $this->id, array() ), $cl_js_atts );
			
			foreach( $cl_js_atts as $atts => $value ) {
				if ( 'custom_jquery' !== $atts && 'version' !== $atts )
					unset( $cl_js_atts[$atts] );
			}
			
			ob_start();
				echo "<script type=\"text/javascript\">\n";
					Custom_Login_Templates::get_template_part( 'wp-login', 'script' );
				echo "\n</script>";				
			echo ob_get_clean();
			
		endif; // jQUery
	}
	
	/**
	 * Includes required functions
	 *
	 */
	public function required_classes() {
		if ( is_admin() ) {
			require_once( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'classes/class.settings-api.php' );
			$this->settings_api = new Extendd_Plugin_Settings_API;
			$this->settings_api->set_prefix( $this->id );
			$this->settings_api->set_domain( $this->domain );
			$this->settings_api->set_version( $this->version );
		}
		require_once( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'classes/templates.php' );
		require_once( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'classes/welcome.php' );
	}
	
	/**
	 * Includes required functions
	 *
	 */
	public function required_functions() {
		require_once( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'functions/upgrades/upgrade-functions.php' );
		require_once( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'functions/upgrades/upgrades.php' );
		//require_once( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'functions/install.php' ); // Deprecated as of 2.0.3
		//require_once( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'functions/post-types.php' ); // Deprecated as of 2.0.3
		require_once( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'functions/scripts-styles.php' );
	}
	
	/**
	 * Helper function to require classes if they do not exist.
	 *
	 */
	public function class_exists_require( $class_name, $dir_path = null ) {
		if ( !class_exists( $class_name ) ) {
			$dir_path = trailingslashit( plugin_dir_path( __FILE__ ) ) . 'classes/' . $dir_path;
			
			if ( file_exists( $dir_path ) )	require_once( $dir_path );
		}
	}
	
	/**
	 * Add shortcodes
	 *
	 */
	public function add_shortcodes() {
		add_shortcode( $this->domain, array( $this, 'login_shortcode' ) );
	}
	
	/**
	 * Login shortcode
	 *
	 */
	public function login_shortcode( $atts ) {
		return false;
	}
	
	/**
	 * Get the value of a settings field
	 *
	 * $this->get_option( 'field_name', 'section_name', 'default value' );
	 *
	 * @param string $option settings field name
	 * @param string $section the section name this field belongs to
	 * @param string $default default text if it's not found
	 * @return mixed
	 */
	public function get_option( $option, $section, $default = '' ) {
	 	$options = get_option( $section );
	 	if ( isset( $options[$option] ) ) {
			return $options[$option];
               }
		return $default;
	}
	
	/** 
	 * Registers settings section and fields
 	 */
    function admin_init() {
				
        $this->sections = array(
            array(
                'id'	=> $this->id,
                'title' => __( 'Login Form Design', $this->domain )
            ),
        );

        $fields = array(
            $this->id => array(
                array(
                    'name'		=> 'active',
                    'label'		=> __( 'Activate Design login settings', $this->domain ),
                    'desc'		=> __( 'on &amp; off.', $this->domain ),
                    'type'		=> 'checkbox',
                   
                ),
				/** BREAK **/
                array(
                    'name' 		=> 'break_1',
                    'label'		=> __( '<div class="line"><h4>Login Page Settings</h4></div><h4>Login Page Settings</h4><br>', $this->domain ),
                    'desc' 		=> '',
                    'type' 		=> 'html',
                ),
				/** BREAK **/
                array(
                    'name' 		=> 'html_background_color',
                    'label' 	=> __( 'Background Color', $this->domain ),
                    'desc' 		=> '',
                    'type' 		=> 'colorpicker',
                    'default' 	=> '',
                ),
                array(
                    'name' 		=> 'html_background_url',
                    'label' 	=> __( 'Background Image', $this->domain ),
                    'desc' 		=> __( 'Upload an image or a repeating pattern (optional).', $this->domain ),
                    'type' 		=> 'file',
                    'default' 	=> '',
					'page_id'	=> '0',
					'sanitize_callback' => 'esc_url',
                ),
                 array(
                    'name' 		=> 'html_background_patterns',
                    'label' 	=> __( 'Background Image Pattern', $this->domain ),
                    'desc' 		=> __( 'Choose pattern (Background Image option will not be work).', $this->domain ),
                    'type' 		=> 'select1',
                    'options' 	=> array(
                       ''.plugins_url('siri-wp-security/img/patters/pattern_1.png').''		=> 'pattern 1',
                       ''.plugins_url('siri-wp-security/img/patters/pattern_2.png').''		=> 'pattern 2',
                       ''.plugins_url('siri-wp-security/img/patters/pattern_3.png').''		=> 'pattern 3',
                        ''.plugins_url('siri-wp-security/img/patters/pattern_4.png').''		=> 'pattern 4',
                       ''.plugins_url('siri-wp-security/img/patters/pattern_5.png').''		=> 'pattern 5',
                       ''.plugins_url('siri-wp-security/img/patters/pattern_6.png').''		=> 'pattern 6',
                        ''.plugins_url('siri-wp-security/img/patters/pattern_7.png').''		=> 'pattern 7',
                       ''.plugins_url('siri-wp-security/img/patters/pattern_8.png').''		=> 'pattern 8',
                       ''.plugins_url('siri-wp-security/img/patters/pattern_9.png').''		=> 'pattern 10',
                        ''.plugins_url('siri-wp-security/img/patters/pattern_10.png').''	=> 'pattern 11',
                       ''.plugins_url('siri-wp-security/img/patters/pattern_11.png').''		=> 'pattern 12',
                       ''.plugins_url('siri-wp-security/img/patters/pattern_12.jpg').''		=> 'pattern 13',
			
                    ),
                ),
                array(
                    'name' 		=> 'html_background_position',
                    'label' 	=> __( 'Background Position', $this->domain ),
                    'desc' 		=> '',
                    'type' 		=> 'select',
                    'options' 	=> array(
                                        'center center'	=> 'center center',
                                        'center top'	=> 'center top',
                                        'center bottom'	=> 'center bottom',
                                        'left top'		=> 'left top',
                                        'left center'	=> 'left center',
                                        'left bottom'	=> 'left bottom',
                                        'right top'		=> 'right top',
                                        'right center'	=> 'right center',
                                        'right bottom'	=> 'right bottom',
						
                    ),
                ),
                array(
                    'name' 		=> 'html_background_repeat',
                    'label' 	=> __( 'Background repeat', $this->domain ),
                    'desc' 		=> '',
                    'type' 		=> 'select',
                    'options' 	=> array(
                        'repeat' 	=> 'repeat',
                        'no-repeat'	=> 'no-repeat',
                        'repeat-x' 	=> 'repeat-x',
                        'repeat-y' 	=> 'repeat-y',
                    )
                ),
                array(
                    'name' 		=> 'html_background_size',
                    'label' 	=> __( 'Background size', $this->domain ),
                    'desc' 		=> '',
                    'type' 		=> 'select',
                    'options' 	=> array(
                        'none'		=> 'none',
                        'cover' 	=> 'cover',
                        'contain' 	=> 'contain',
                        'flex' 		=> 'flex',
                    )
                ),
				/** BREAK **/
                array(
                    'name' 		=> 'break_2',
                    'label'		=> __( '<div class="line"><h4>Add Logo</h4></div><h4>Add Logo</h4>', $this->domain ),
                    'desc' 		=> '',
                    'type' 		=> 'html',
                ),
				/** BREAK **/
                array(
                    'name'		=> 'hide_wp_logo',
                    'label'		=> __( 'Hide the WP logo', $this->domain ),
                    'desc'		=> __( 'Works when there is no logo present, otherwise the WP logo is replaced by your logo.', $this->domain ),
                    'type'		=> 'checkbox'
                ),
                array(
                    'name' 		=> 'logo_background_url',
                    'label' 	=> __( 'Logo', $this->domain ),
                    'desc' 		=> __( 'Replace the WordPress logo (optional).', $this->domain ),
                    'type' 		=> 'file',
                    'default' 	=> '',
					'page_id'	=> '0',
					'sanitize_callback' => 'esc_url',
                ),
                array(
                    'name' 		=> 'logo_background_position',
                    'label' 	=> __( 'Logo Background Position', $this->domain ),
                    'desc' 		=> '',
                    'type' 		=> 'select',
                    'options' 	=> array(
                        'left top'		=> 'left top',
						'left center'	=> 'left center',
						'left bottom'	=> 'left bottom',
						'right top'		=> 'right top',
						'right center'	=> 'right center',
						'right bottom'	=> 'right bottom',
						'center top'	=> 'center top',
						'center center'	=> 'center center',
						'center bottom'	=> 'center bottom',
					),
                ),
                array(
                    'name' 		=> 'logo_background_repeat',
                    'label' 	=> __( 'Logo Background repeat', $this->domain ),
                    'desc' 		=> '',
                    'type' 		=> 'select',
                    'options' 	=> array(
                        'no-repeat'	=> 'no-repeat',
                        'repeat' 	=> 'repeat',
                        'repeat-x' 	=> 'repeat-x',
                        'repeat-y' 	=> 'repeat-y',
                    )
                ),
                array(
                    'name' 		=> 'logo_background_size',
                    'label' 	=> __( 'Logo Background size', $this->domain ),
                    'desc' 		=> '',
                    'type' 		=> 'select',
                    'options' 	=> array(
                        'none'		=> 'none',
                        'cover' 	=> 'cover',
                        'contain' 	=> 'contain',
                        'flex' 		=> 'flex',
                    )
                ),
				/** BREAK **/
                array(
                    'name' 		=> 'break_3',
                    'label'		=> __( '<div class="line"><h4>Login form</h4></div><h4>Login form</h4>', $this->domain ),
                    'desc' 		=> '',
                    'type' 		=> 'html',
                ),
				/** BREAK **/
                array(
                    'name' 		=> 'login_form_background_color',
                    'label' 	=> __( 'Background Color', $this->domain ),
                    'desc' 		=> '',
                    'type' 		=> 'colorpicker',
                    'default' 	=> ''
                ),
                array(
                    'name' 		=> 'login_form_background_url',
                    'label' 	=> __( 'Background URL', $this->domain ),
                    'desc' 		=> __( 'Add an image to the form (optional).', $this->domain ),
                    'type' 		=> 'file',
                    'default' 	=> '',
					'page_id'	=> '0',
					'sanitize_callback' => 'esc_url',
                ),
                array(
                    'name' 		=> 'login_form_background_position',
                    'label' 	=> __( 'Background Position', $this->domain ),
                    'desc' 		=> '',
                    'type' 		=> 'select',
                    'options' 	=> array(
                        'left top'		=> 'left top',
						'left center'	=> 'left center',
						'left bottom'	=> 'left bottom',
						'right top'		=> 'right top',
						'right center'	=> 'right center',
						'right bottom'	=> 'right bottom',
						'center top'	=> 'center top',
						'center center'	=> 'center center',
						'center bottom'	=> 'center bottom',
                    ),
                ),
                array(
                    'name' 		=> 'login_form_background_repeat',
                    'label' 	=> __( 'Background repeat', $this->domain ),
                    'desc' 		=> '',
                    'type' 		=> 'select',
                    'options' 	=> array(
                        'no-repeat'	=> 'no-repeat',
                        'repeat' 	=> 'repeat',
                        'repeat-x' 	=> 'repeat-x',
                        'repeat-y' 	=> 'repeat-y',
                    )
                ),
                array(
                    'name' 		=> 'login_form_background_size',
                    'label' 	=> __( 'Background size', $this->domain ),
                    'desc' 		=> '',
                    'type' 		=> 'select',
                    'options' 	=> array(
                        'none'		=> 'none',
                        'cover' 	=> 'cover',
                        'contain' 	=> 'contain',
                        'flex' 		=> 'flex',
                    )
                ),
                array(
                    'name' 		=> 'login_form_border_radius',
                    'label' 	=> __( 'Border Radius', $this->domain ),
                    'desc' 		=> '',
                    'type' 		=> 'text',
					'size'		=> 'small',
                    'default' 	=> '20',
					'sanitize_callback' => 'absint',
                ),
                array(
                    'name' 		=> 'login_form_border_size',
                    'label' 	=> __( 'Border Size', $this->domain ),
                    'desc' 		=> '',
                    'type' 		=> 'text',
					'size'		=> 'small',
                    'default' 	=> '',
					'sanitize_callback' => 'absint',
                ),
                array(
                    'name' 		=> 'login_form_border_color',
                    'label' 	=> __( 'Border Color', $this->domain ),
                    'desc' 		=> '',
                    'type' 		=> 'colorpicker',
                    'default' 	=> ''
                ),
                array(
                    'name' 		=> 'login_form_box_shadow',
                    'label' 	=> __( 'Box Shadow', $this->domain ),
                    'desc' 		=> sprintf( __( 'box-shadow: 10px 10px 5px #888888;', $this->domain )),
                    'type' 		=> 'text',
					'size'		=> 'medium',
                    'default' 	=> '5px 5px 10px'
                ),
                array(
                    'name' 		=> 'login_form_box_shadow_color',
                    'label' 	=> __( 'Box Shadow Color', $this->domain ),
                    'desc' 		=> '',
                    'type' 		=> 'colorpicker',
                    'default' 	=> ''
                ),
		
                array(
                    'name' 		=> 'label_color',
                    'label' 	=> __( 'Label Color', $this->domain ),
                    'desc' 		=> '',
                    'type' 		=> 'colorpicker',
                    'default' 	=> ''
                ),
				/** BREAK **/
                array(
                    'name' 		=> 'break_5',
                    'label'		=> __( '<div class="line"><h4>Below form anchor (Links) settings</h4></div><h4>Below form anchor (Links) settings</h4>', $this->domain ),
                    'desc' 		=> '',
                    'type' 		=> 'html',
                ),
				/** BREAK **/
                array(
                    'name' 		=> 'nav_color',
                    'label' 	=> __( ' nav color', $this->domain ),
                    'desc' 		=> '',
                    'type' 		=> 'colorpicker',
                    'default' 	=> '',
                ),
                array(
                    'name' 		=> 'nav_text_shadow_color',
                    'label' 	=> __( ' nav text-shadow color', $this->domain ),
                    'desc' 		=> '',
                    'type' 		=> 'colorpicker',
                    'default' 	=> '',
                ),
                array(
                    'name' 		=> 'nav_hover_color',
                    'label' 	=> __( ' nav color hover', $this->domain ),
                    'desc' 		=> '',
                    'type' 		=> 'colorpicker',
                    'default' 	=> '',
                ),
                array(
                    'name' 		=> 'nav_text_shadow_hover_color',
                    'label' 	=> __( ' nav text-shadow hover', $this->domain ),
                    'desc' 		=> '',
                    'type' 		=> 'colorpicker',
                    'default' 	=> '',
                ),
				/** BREAK **/
                array(
                    'name' 		=> 'break_6',
                    'label'		=> __( '<div class="line"><h4>Add our own HTML/CSS & jQuery(For addtional styles)</h4></div><h4>Add our own HTML (For addtional styles)</h4>', $this->domain ),
                    'desc' 		=> '',
                    'type' 		=> 'html',
                ),
				/** BREAK **/
				array(
					'name' 		=> 'custom_css',
					'label' 	=> __( 'Custom CSS', $this->domain ),
					'desc' 		=> 'Default styles (Note:Befoure designing your loginform please remove this styles).',
					'type' 		=> 'textarea',
                                        'default' 	=> '
                                            #login form{
min-height: 184px !important;
background-color: #f9f9f9 !important;
border: 1px solid #C7C7C7 !important;
}

.login #nav, .login #backtoblog {
    background-color: #246cb4 !important;
    margin: -31px 0 16px 8px !important;
    padding: 10px 82px;
    text-shadow: 0 1px 0 #FFFFFF !important;
    border-radius: 0px 0px 15px 15px  !important;
}

.login #nav a, .login #backtoblog a {
    color: #FFFFFF !important;
    font-size: 13px !important;
    text-shadow: none !important;
}

#login form {
    box-shadow: 1px 5px 17px !important;
}

.login #nav a:hover, .login #backtoblog a:hover {
    color: yellow !important;
}

.wp-core-ui .button-primary{
background-image: linear-gradient(to bottom, #2971B7, #2971B7) !important;
}

.wp-core-ui .button-primary.hover, .wp-core-ui .button-primary:hover, .wp-core-ui .button-primary.focus, .wp-core-ui .button-primary:focus{
background-image: linear-gradient(to bottom, #185c9e, #185c9e) !important;
}

.login form .input, .login input[type=&quot;text&quot;]{
background: none repeat scroll 0 0 #F0F0F0 !important;

}

.login label {
    color: #424241 !important;
    font-size: 15px !important;
}
.login h1 a {
    background-image: url("'.plugins_url().'/siri-wp-security/img/logo.png") !important;
 background-size: 54% auto !important;
position: relative !important;
top: 25px !important;
padding: 0 0 31px !important;
}',
					'sanitize_callback' => 'esc_attr',
				),
                
				array(
					'name' 		=> 'custom_html',
					'label' 	=> __( 'Custom HTML', $this->domain ),
					'desc' 		=> '',
					'type' 		=> 'textarea',
					'sanitize_callback' => 'stripslashes_deep', //Allow HTML
				),
              		array(
					'name' 		=> 'custom_jquery',
					'label' 	=> __( 'Custom jQuery', $this->domain ),
					'desc' 		=> '',
					'type' 		=> 'textarea',
					'sanitize_callback' => 'wp_specialchars_decode',
				),
			),
        );
		
        //set sections and fields
        $this->settings_api->set_sections( $this->sections );
		$this->settings_api->set_fields( $fields );

        //initialize them
        $this->settings_api->admin_init();
		
		
		return $this;

    }

    /**
	 * Register the plugin page
	 */
    function admin_menu() {
        if ( function_exists( 'wp_enqueue_media' ) ) wp_enqueue_media();
		
		wp_enqueue_style( 'wp-color-picker' );
		
		
	        wp_enqueue_style( 'jquery-chosen', plugins_url( 'assets/css/stylessh.css', CUSTOM_LOGIN_FILE ), false, '0.9.12', 'screen' );
		
		/* Admin */
		wp_enqueue_style( $this->domain, plugins_url( 'assets/css/admin.css', CUSTOM_LOGIN_FILE ), false, '', 'screen' );
		
		/* Genericons */
		wp_enqueue_style( 'genericons', plugins_url( 'assets/css/genericons.css', CUSTOM_LOGIN_FILE ), false, '', 'screen' );
		
	        add_menu_page('Siri WP Security', 'Siri WP Security', 'edit_posts', 'secure_wp', array(&$this, 'plugin_page'), plugins_url('img/Siri_Icon.png', __FILE__));
		add_action( 'admin_footer-' . $this->options_page, array( $this->settings_api, 'inline_jquery' ) );
    }
	
	/**
	 * Delete the transient
	 *
	 */
	public function delete_transient_button_output() {
		$button  = '<div style="padding-left: 10px">';
		$button .= wpautop( $this->delete_transient_button_link() );
		$button .= '<span class="">' . __( 'If your stylesheet isn\'t updating click update above to delete the transient cache.', $this->domain ) . '</span>';
		$button .= '</div>';
		
		echo $button;
	}
	
	/**
	 * Delete button link output
	 *
	 * @return string
	 */
	public function delete_transient_button_link( $class = 'button' ) {
		return sprintf( '<a href="%s" title="%s" class="%s">%s</a>', 
			esc_url( wp_nonce_url( add_query_arg( array( 'action' => $this->id . '-delete_transient' ), admin_url() ), $this->id . '-delete_transient' ) ),
			esc_attr__( 'Clear the transient cache', $this->domain ),
			sanitize_html_class( $class ),
			__( 'Clear stylesheet cache', $this->domain )
		);
	}
	
	/**
	 * Check for post activation on edit.php, when proper action
	 * is called set the post ID and write the content (CSS) to file.
	 * 
	 * @return array()
	 */
	public function delete_custom_login_transient_cache() {
		
		if ( !( isset( $_GET[$this->id . '-delete_transient'] ) || ( isset( $_REQUEST['action'] ) && $this->id . '-delete_transient' == $_REQUEST['action'] ) ) )
			return;
			
		check_admin_referer( $this->id . '-delete_transient' );
		
		delete_transient( $this->id . '_style' );
		delete_transient( $this->id . '_script' );
		
		/* Redirect */
		wp_redirect( admin_url( sprintf( 'options-general.php?page=%s&settings-updated=true&message=1', $this->domain ) ) );
		exit;
	}
	
	/**
	 * Show a message when prompted
	 *
	 * @return string|void
	 */
	public function admin_messages() {
		global $pagenow;
		
		if ( ( isset( $_GET['page'] ) && isset( $_GET['message'] ) ) && $pagenow == 'options-general.php' && $this->domain == $_GET['page'] ) {
			
			$html = '<div id="setting-error-transitent_deleted" class="updated"><p>'; 
			
			switch ( $_GET['message'] ) {
				case 1 :
					$html .= __( 'The cache has been deleted.', $this->domain );
					break;
					
				case 2 :
					$html .= '';
					break;					
			}
			
			$html .= '</p></div>';
		
			echo $html;
		}
	}
	
	/**
	 * Plugin Action
	 */
	public function plugin_action_links( $links, $file ) {
		if ( plugin_basename( __FILE__ ) === $file ) {
			$settings_link = '<a href="' . admin_url( 'options-general.php?page=custom-login' ) . '">' . __( 'Settings', $this->domain ) . '</a>';
			array_unshift( $links, $settings_link ); // before other links
		}
		return $links;
	}

	/**
	 * Display the plugin settings options page
	 */
    public function plugin_page() {
        echo '<div id="icon-options"></div>
            <div class="wrap" id="loginsettingsDiv" style="margin: 46px 0px 0px; position: absolute; width: 100%;">';

       // $this->settings_api->show_navigation();
        $this->settings_api->show_forms();

        echo '</div>';
		
		if ( defined( 'WP_LOCAL_DEV' ) && WP_LOCAL_DEV && WP_DEBUG ) {
			/**
			foreach( apply_filters( $this->id . '_add_settings_sections', $this->sections ) as $section )
				echo '<pre data-id="'.$section['id'].'">' . print_r( get_option( $section['id'] ), true ) . '</pre>';
			echo '<pre data-id="custom_login_settings">' . print_r( get_option( 'custom_login_settings' ), true ) . '</pre>';
			
			echo '<pre data-id="' . $this->id . '_ignore_announcement">' . print_r( get_user_meta( get_current_user_id(), $this->id . '_ignore_announcement', true ), true ) . '</pre>';
			echo '<pre data-id="' . $this->id . '_announcement_message">' . print_r( get_option( $this->id . '_announcement_message' ), true ) . '</pre>';
			// */
		}
		
    }


	public function sidebar( $args ) {
		$content  = '<ul class="social">';
		if ( defined( 'WP_LOCAL_DEV' ) && WP_LOCAL_DEV ) {
			$content .= '<li class="queries genericon-warning">' . $this->get_queries( true ) . '</li>';
		}
		
		$content .= '<li class="delete genericon-warning">' . $this->delete_transient_button_link( '' ) . '</li>';

		$content .= '</ul>';
		$this->settings_api->postbox( $this->id . '_sidebar', sprintf( __( '<a href="%s">%s</a> | <code>version %s</code>', $this->domain ), '', ucwords( str_replace( '-', ' ', $this->domain ) ), $this->version ), $content, true );
	}
	
	/**
	 * Is plugin active
	 *
	 */
	public function is_active() {
		$active = $this->get_option( 'active', $this->id );
		if ( isset( $active ) && 'on' === $active )
			return true;
			
		return false;
	}
	
	/**
	 * Replace the default link to your URL
	 *
	 */
	public function login_url() {
		if ( !is_multisite() ) return home_url( '/' );
		else return network_home_url( '/' );
	}
	
	/**
	 * Replace the default title to your description
	 *
	 */
	public function login_title() {
		return get_bloginfo( 'description' );
	}
	
	/**
	 * If there is custom HTML set in the settings echo it to the
	 * `login_footer` hook in wp-login.php.
	 *
	 */
	public function login_footer_html() {
		$custom_html = $this->get_option( 'custom_html', $this->id );
		$html  = '';
		
		if ( !empty( $custom_html ) )
			$html .= $custom_html;
		
		$html .= defined( 'WP_DEBUG' ) && WP_DEBUG ? $this->get_queries() : '';
		$html .= "\n";
		
		echo $html;
	}
	
	/**
	 * Helper function to get total numer of queries and execution time
	 *
	 */
	public function get_queries( $display = false ) {
		return sprintf( ( $display ? '' : '<div style="display:none">' ) . esc_attr__( '%s queries in %s seconds.', $this->domain ) . ( $display ? '' : '</div>' ), get_num_queries(), timer_stop() );
	}
        
	
}   

   
endif;

/**
 * The main function responsible for returning the one true
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $custom_login = CUSTOMLOGIN(); ?>
 *
 * @return The one true Instance
 */
if ( !function_exists( 'CUSTOMLOGIN' ) ) {
	function CUSTOMLOGIN() {
		return Secure_login::instance();
	}
}

// Out of the frying pan, and into the fire.
CUSTOMLOGIN();
