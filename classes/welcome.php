<?php
/*
 * 
 * @package     Secure Login
 * Copyright (C) 2013  Siri Iinnovations
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 *
 */
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


class Custom_Login_Welcome {
	
	/**
	 * @var string
	 */
	public $minimum_capability = 'manage_options';


	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menus') );
		add_action( 'admin_head', array( $this, 'admin_head' ) );
		add_action( 'admin_init', array( $this, 'welcome'    ) );
	}


	public function admin_menus() {
		$login = CUSTOMLOGIN();
		// About Page
		add_dashboard_page(
			__( 'Welcome to Custom Login', $login->domain ),
			__( 'Welcome to Custom Login', $login->domain ),
			$this->minimum_capability,
			'custom-login-about',
			array( $this, 'about_screen' )
		);
	}


	public function admin_head() {
		remove_submenu_page( 'index.php', 'custom-login-about' );

		// Badge for welcome page
		$badge_url = CUSTOM_LOGIN_URL . 'assets/images/welcome-badge.png';
		?>
		<style type="text/css" media="screen">
		/*<![CDATA[*/
		.cl-badge {
			padding-top: 150px;
			height: 52px;
			width: 185px;
			color: #666;
			font-weight: bold;
			font-size: 14px;
			text-align: center;
			text-shadow: 0 1px 0 rgba(255, 255, 255, 0.8);
			margin: 0 -5px;
			background: url('<?php echo $badge_url; ?>') no-repeat;
		}

		.about-wrap .cl-badge {
			position: absolute;
			top: 0;
			right: 0;
		}
		/*]]>*/
		</style>
		<?php
	}


	public function about_screen() {
		list( $display_version ) = explode( '-', CUSTOM_LOGIN_VERSION );
		$login = CUSTOMLOGIN();
		?>
		<div class="wrap about-wrap">
			<h1><?php printf( __( 'Welcome to Custom Login %s', $login->domain ), $display_version ); ?></h1>
			<div class="about-text"><?php printf( __( 'Thank you for updating to the latest version! Custom Login %s is ready to make your login page better!', $login->domain ), $display_version ); ?></div>
			<div class="cl-badge"><?php printf( __( 'Version %s', $login->domain ), $display_version ); ?></div>

			<h2 class="nav-tab-wrapper">
				<a class="nav-tab nav-tab-active" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'custom-login-about' ), 'index.php' ) ) ); ?>">
					<?php _e( "What's New", $login->domain ); ?>
				</a>
			</h2>

			

			<div class="changelog">
				<h3><?php _e( 'Under the Hood', $login->domain ); ?></h3>

			
			</div>

			<div class="return-to-dashboard">
				<a href="<?php echo esc_url( admin_url( 'options-general.php?page=' . $login->domain ) ); ?>"><?php _e( 'Go to Custom Login Settings', $login->domain ); ?></a>
			</div>
		</div>
		<?php
	}


	public function welcome() {
		global $edd_options;

		// Bail if no activation redirect
		if ( ! get_transient( '_edd_activation_redirect' ) )
			return;

		// Delete the redirect transient
		delete_transient( '_edd_activation_redirect' );

		// Bail if activating from network, or bulk
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) )
			return;

		wp_safe_redirect( admin_url( 'index.php?page=edd-about' ) ); exit;

	}
}
new Custom_Login_Welcome();