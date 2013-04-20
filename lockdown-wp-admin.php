<?php if (! defined('ABSPATH')) exit;
/*
Plugin Name: Lockdown WP Admin
Plugin URI: http://seanfisher.co/lockdown-wp-admin/
Donate link: http://seanfisher.co/donate/
Description: Securing the WordPress Administration interface by concealing the administration dashboard and changing the login page URL.
Version: 2.0.1
Author: Sean Fisher
Author URI: http://seanfisher.co/
License: GPL
*/

// This file name
define('LD_FILE_NAME', __FILE__ );

/**
 * This is the plugin that will add security to our site
 *
 * @author   Sean Fisher <me@seanfisher.co>
 * @version  1.9
 * @license   GPL 
**/
class WP_LockAuth
{	
	/**
	 * The version of lockdown WP Admin
	 *
	 * @global string
	 * @access private
	**/
	private $ld_admin_version = 2.0;
	
	/**
	 * The HTTP Auth name for the protected area
	 * Change this via calling the object, not by editing the file.
	 *
	 * @access	public
	 * @global	string
	**/
	public $relm = 'Secure Area';
	
	/**
	 * The current user ID from our internal array
	 *
	 * @access	private
	**/
	private $current_user = FALSE;
	
	/**
	 * The base to get the login url
	 *
	 * @access	private
	**/
	private $login_base = FALSE;
	
	public function __construct()
	{
		// We don't like adding network wide WordPress plugins.
		if (! class_exists('Disable_WPMS_Plugin_LD'))
			require_once( dirname( __FILE__ ) .'/no-wpmu.php' );
		
		// Add the action to setup the menu.
		add_action('admin_menu', array( $this, 'add_admin_menu'));
		
		// Setup the plugin.
		$this->setup_hide_admin();
		
		// Hide the login form
		$this->redo_login_form();
	}
	
	/**
	 * Get a username and password from the HTTP auth
	 *
	 * @return array|bool
	**/
	public function get_http_auth_creds()
	{
		// Since PHP saves the HTTP Password in a bunch of places, we have to be able to test for all of them
		$username = NULL;
		$password = NULL;
		
		// mod_php
		if (isset($_SERVER['PHP_AUTH_USER'])) 
		{
		    $username = (isset($_SERVER['PHP_AUTH_USER'])) ? $_SERVER['PHP_AUTH_USER'] : NULL;
		    $password = (isset($_SERVER['PHP_AUTH_PW'])) ? $_SERVER['PHP_AUTH_PW'] : NULL;
		}

		// most other servers
		elseif ($_SERVER['HTTP_AUTHENTICATION'])
		{
			if (strpos(strtolower($_SERVER['HTTP_AUTHENTICATION']),'basic') === 0)
			{
				list($username,$password) = explode(':',base64_decode(substr($_SERVER['HTTP_AUTHENTICATION'], 6)));
			}
		}
		
		// Check them - if they're null a/o empty, they're invalid.
		if ( is_null($username) OR is_null($password) OR empty($username) OR empty($password))
			return FALSE;
		else
			return array('username' => $username, 'password' => $password);
	}
	
	/**
	 * Update the users
	 *
	 * @access private
	**/
	public function update_users()
	{
		if (! isset( $_GET['page'] ) )
			return;
		
		if ( $_GET['page'] !== 'lockdown-private-users' )
			return;
		
		// Nonce
		if ( !isset( $_REQUEST['_wpnonce'] ) )
			return;
		
		$nonce = $_REQUEST['_wpnonce'];
		if ( !wp_verify_nonce( $nonce, 'lockdown-wp-admin' ) )
			wp_die('Security error, please try again.');
		
		// Add a user
		if ( isset( $_POST['private_username'] ) && isset( $_POST['private_password'] ) )
		{
			if ( $_POST['private_username'] !== '' && $_POST['private_password'] !== '' )
			{
				// Adding a user
				$users = $this->get_private_users();
				$add['user'] = sanitize_user( $_POST['private_username'] );
				$add['pass'] = trim( md5( $_POST['private_password'] ) );
				
				// See if it exists
				if ($this->user_exists($users, $add['user'])) :
					define('LD_ERROR', 'username-exists');
					return;
				endif;

				$users[] = $add;
				
				update_option('ld_private_users', $users);
				
				define('LD_WP_ADMIN', TRUE);
				return;
			}
		}
		
		// Deleting a user.
		if ( isset( $_GET['delete'] ) )
		{
			//	Delete the user.
			unset( $users );
			$users = $this->get_private_users();
			$to_delete = (int) $_GET['delete'];
			
			if ( count( $users ) > 0 )
			{
				foreach( $users as $key => $val )
				{
					if ( $key === $to_delete ) :
						if( $this->current_user !== '' && $to_delete === $this->current_user )
						{
							//	They can't delete themselves!
							define('LD_ERROR', 'delete-self');
							return;
						}
						
						unset( $users[$key] );
					endif;
				}			
			}
			
			update_option('ld_private_users', $users);
			
			define('LD_WP_ADMIN', TRUE);
			return;
		}
	}
	
	/**
	 * Update the options
	 *
	 * @access private
	**/
	public function update_options()
	{
		if ( !isset( $_GET['page'] ) )
			return;
		
		if ( $_GET['page'] !== 'lockdown-wp-admin' )
			return;
		
		if ( !isset( $_POST['did_update'] ) )
			return;
		
		//	Nonce
		$nonce = $_POST['_wpnonce'];
		if (! wp_verify_nonce($nonce, 'lockdown-wp-admin') )
			wp_die('Security error, please try again.');
		
		//	---------------------------------------------------
		//	They're updating.
		//	---------------------------------------------------
		if ( isset( $_POST['http_auth'] ) )
			update_option('ld_http_auth', trim( strtolower( $_POST['http_auth'] ) ) );
		else
			update_option('ld_http_auth', 'none' );
		
		if ( !isset( $_POST['hide_wp_admin'] ) )
		{
			update_option('ld_hide_wp_admin', 'nope');
		}
		else
		{
			if ( $_POST['hide_wp_admin'] === 'yep' )
				update_option('ld_hide_wp_admin', 'yep');
			else
				update_option('ld_hide_wp_admin', 'nope');
		}
		
		if ( isset( $_POST['login_base'] ) )
		{
			$exp = explode('/', $_POST['login_base'], 2);
			$base = reset( $exp );
			$base = sanitize_title_with_dashes( $base);
			$base = str_replace('/', '', $base);
			
			$disallowed = array(
				'user', 'wp-admin', 'wp-content', 'wp-includes', 'wp-feed.php', 'index', 'feed', 'rss', 'robots', 'robots.txt', 'wp-login.php',
			);
			if ( in_array( $base, $disallowed ) )
			{
				define('LD_DIS_BASE', TRUE);
			}
			else
			{
				
				update_option('ld_login_base', $base);
				$this->login_base = sanitize_title_with_dashes ( $base );
			}
		}
		
		//	Redirect
		define('LD_WP_ADMIN', TRUE);
		return;
	}
	
	/**
	 * Send headers to the browser that are going to ask for a username/pass
	 * from the browser.
	 *
	 * @access private
	 * @return void
	**/
	private function inauth_headers()
	{
		//	Disable if there is a text file there.
		if ( file_exists(dirname(__FILE__).DIRECTORY_SEPARATOR.'disable_auth.txt'))
			return;
		
		header('WWW-Authenticate: Basic realm="'.$this->relm.'"');
		header('HTTP/1.0 401 Unauthorized');
		echo '<h1>Authorization Required.</h1>';
		exit;
	}
	
	/**
	 * Get the users for the private creds
	 *
	 * @access private
	**/
	public function get_private_users()
	{
		$opt = get_option('ld_private_users');
		if ( !is_array( $opt ) )
			return array();
		
		return $opt;
	}
	
	/**
	 * Setup hiding wp-admin
	 *
	 * @access void
	**/
	protected function setup_hide_admin()
	{
		$opt = get_option('ld_hide_wp_admin');
		
		//	Nope, they didn't enable it.
		if ( $opt !== 'yep' )
			return $this->setup_http_area();
		
		// We're gonna hide it.
		$no_check_files = array('async-upload.php', 'admin-ajax.php', 'wp-app.php');
		$no_check_files = apply_filters('no_check_files', $no_check_files);
		
		$explode = explode('/', $_SERVER['SCRIPT_FILENAME'] );
		$file = end( $explode );
	    	
	    	if ( in_array( $file, $no_check_files ) )
	    	{
			define('INTERNAL_AUTH_PASSED', TRUE);
			return;
		}
    	
		// We only will hide it if we are in admin (/wp-admin/)
		if ( is_admin() )
		{
			// Non logged in users.
			if ( ! is_user_logged_in() )
				$this->throw_404();
						
			// Setup HTTP auth.
			$this->setup_http_area();
		}
	}
	
	/**
	 * Get the current file name
	 *
	 * @return string JUST the file name
	**/
	public function get_file()
	{
		//	We're gonna hide it.
		$no_check_files = array('async-upload.php');
		$no_check_files = apply_filters('no_check_files', $no_check_files);
		
		$explode = explode('/', $_SERVER['SCRIPT_FILENAME'] );
		return end( $explode );
	}
	
	/**
	 * Setting up the HTTP Auth
	 *
	 * Here, we only check if it's enabled
	 *
	 * @access protected
	**/
	protected function setup_http_area()
	{
		//	We save what type of auth we're doing here.
		$opt = get_option('ld_http_auth');
		
		// What type of auth are we doing?
		switch( $opt )
		{
			//	HTTP auth is going to ask for their WordPress creds.
			case 'wp_creds' :
				$creds = $this->get_http_auth_creds();
				if (! $creds )
					$this->inauth_headers(); // Invalid credentials
				
				//	Are they already logged in as this?
				$current_uid = get_current_user_id();
				
				//	We fixed this for use with non WP-MS sites
				$requested_user = get_user_by('login', $creds['username']);
				
				//	Not a valid user.
				if (! $requested_user )
					$this->inauth_headers();
				
				//	The correct User ID.
				$requested_uid = (int) $requested_user->ID;
				
				//	Already logged in?
				if ( $current_uid === $requested_uid )
				{
					define('INTERNAL_AUTH_PASSED', TRUE);
					return;
				}
				
				//	Attempt to sign them in if they aren't already
				if (! is_user_logged_in() ) :
					//	Try it via wp_signon
					$creds = array();
					$creds['user_login'] = $creds['username'];
					$creds['user_password'] = $creds['password'];
					$creds['remember'] = true;
					$user = wp_signon( $creds, false );
					
					//	In error :(
					if ( is_wp_error($user) )
						$this->inauth_headers();
				endif;
				
				//	They passed!
				define('INTERNAL_AUTH_PASSED', TRUE);
			break;
			
			// Private list of users to check
			case 'private' :
				$users = $this->get_private_users();
				
				// We want a user to exist.
				// If nobody is found, we won't lock them out!
				if ( ! $users || ! is_array( $users ) )
					return;
				
				//	Let's NOT lock everybody out
				if ( count( $users ) < 1 )
					return;
				
				// Get the HTTP auth creds
				$creds = $this->get_http_auth_creds();
				
				// Invalid creds
				if (! $creds )
					$this->inauth_headers();
				
				//	Did they enter a valid user?
				if ( $this->user_array_check( $users, $creds['username'], $creds['password'] ) )
				{
					define('INTERNAL_AUTH_PASSED', TRUE);
					$this->set_current_user( $users, $creds['username'] );
					return;
				}
				else
				{
					return $this->inauth_headers();
				}
				
			break;
			
			// Unknown type of auth
			default :
				return FALSE;
		}
		
	}
	/**
	 * Check an internal array of users against a passed user and pass
	 *
	 * @access protected
	 * @return bool
	 *
	 * @param array $array The array of users
	 * @param string $user The username to check for
	 * @param string $pass The password to check for (plain text)
	**/
	protected function user_array_check( $array, $user, $pass )
	{
		foreach( $array as $key => $val )
		{
			if ( $val['user'] === $user && md5( $pass ) === $val['pass'] )
				return TRUE;
		}
		
		return FALSE;
	}

	/**
	 * See if a user exists in the array
	 *
	 * @access protected
	 * @return boolean
	 * @param array Array of users
	 * @param string
	 */
	protected function user_exists($array, $user)
	{
		if (count($array) == 0) return FALSE;

		foreach ($array as $k => $v) :
			if ($v['user'] == $user)
				return TRUE;
		endforeach;

		return FALSE;
	}
	
	/**
	 * Set the current user
	 *
	 * @access private
	 * @param array
	 * @param integer
	**/
	private function set_current_user( $array, $user )
	{
		foreach( $array as $key => $val )
		{
			if ( $val['user'] === $user )
				$this->current_user = $key;
		}
	}
	
	/**
	 * Adds the admin menu
	 *
	 * @acces private
	**/
	public function add_admin_menu()
	{
		add_menu_page('Lockdown WP', 'Lockdown WP', 'manage_options', 'lockdown-wp-admin', array( $this, 'admin_callback'));
		add_submenu_page( 'lockdown-wp-admin', 'Private Users', 'Private Users', 'manage_options', 'lockdown-private-users',  array( $this, 'sub_admin_callback'));
	}
	
	/**
	 * The callback for the admin area
	 *
	 * You need the 'manage_options' capability to get here.
	**/
	public function admin_callback()
	{
		//	Update the options
		$this->update_options();
		
		//	The UI
		require_once( dirname( __FILE__ ) . '/admin.php' );
	}	
	
	/**
	 * The callback for ther private users management.
	 *
	 * You need the 'manage_options' capability to get here.
	**/
	public function sub_admin_callback()
	{
		// Update the users options
		$this->update_users();
		
		// The UI
		$private_users = $this->get_private_users();
		require_once( dirname( __FILE__ ) . '/admin-private-users.php' );
	}
	
	/**
	 * Rename the login URL
	 *
	 * @access public
	**/
	public function redo_login_form()
	{
		$login_base = get_option('ld_login_base');
		
		//	It's not enabled.
		if ( $login_base == NULL || ! $login_base || $login_base == '' )
			return;
		
		$this->login_base = $login_base;
		unset( $login_base );
		
		//	Setup the filters for the new login form
		add_filter('wp_redirect', array( &$this, 'filter_wp_login'));
		add_filter('network_site_url', array( &$this, 'filter_wp_login'));
		add_filter('site_url', array( &$this, 'filter_wp_login'));
		
		//	We need to get the URL
		//	This means we need to take the current URL,
		//	strip it of an WordPress path (if the blog is located @ /blog/)
		//	And then remove the query string
		//	We also need to remove the index.php from the URL if it exists
		
		//	The blog's URL
		$blog_url = trailingslashit( get_bloginfo('url') );
		
		//	The Current URL
		$schema = is_ssl() ? 'https://' : 'http://';
		$current_url = $schema . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		
		$request_url = str_replace( $blog_url, '', $current_url );
		$request_url = str_replace('index.php/', '', $request_url);
		
		list( $base, $query ) = explode( '?', $request_url, 2 );
		
		//	Remove trailing slash
		$base = rtrim($base,"/");
		$exp = explode( '/', $base, 2 );
		$super_base = reset( $exp );
		
		//	Are they visiting wp-login.php?
		if ( $super_base == 'wp-login.php')
			$this->throw_404();
		
		//	Is this the "login" url?
		if ( $base !== $this->login_base )
			return FALSE;
		
		// We dont' want a WP plugin caching this page
		@define('NO_CACHE', TRUE);
		@define('WTC_IN_MINIFY', TRUE);
		@define('WP_CACHE', FALSE);
		
		// Hook onto this
		do_action('ld_login_page');
		
		include ABSPATH . "/wp-login.php";
		exit;
	}
	
	/**
	 * Filters out wp-login to whatever they named it
	 *
	 * @access public
	**/
	public function filter_wp_login( $str )
	{
		return str_replace('wp-login.php', $this->login_base, $str);
	}
	
	/**
	 * Launch and display the 404 page depending upon the template
	 *
	 * @param		void
	 * @return		void
	**/
	public function throw_404()
	{
		// Change WP Query
		global $wp_query;
		$wp_query->set_404();
		status_header(404);

		// Disable that pesky Admin Bar
		add_filter('show_admin_bar', '__return_false', 900);  
		remove_action( 'admin_footer', 'wp_admin_bar_render', 10);  
		remove_action('wp_head', 'wp_admin_bar_header', 10);
		remove_action('wp_head', '_admin_bar_bump_cb', 10);
		wp_dequeue_script( 'admin-bar' );
		wp_dequeue_style( 'admin-bar' );
		
		// Template
		$four_tpl = get_404_template();

		// Handle the admin bar
		@define('APP_REQUEST', TRUE);
		@define('DOING_AJAX', TRUE);
		
		if ( empty($four_tpl) OR ! file_exists($four_tpl) )
		{
			// We're gonna try and get TwentyTen's one
			$twenty_ten_tpl = apply_filters('LD_404_FALLBACK', WP_CONTENT_DIR . '/themes/twentytwelve/404.php');
			
			if (file_exists($twenty_ten_tpl))
				require($twenty_ten_tpl);
			else
				wp_die('404 - File not found!', '', array('response' => 404));
		}
		else
		{
			// Their theme has a template!
			require( $four_tpl );
		}
		
		// Either way, it's gonna stop right here.
		exit;
	}
}

/**
 * The function called at 'init'.
 *
 * Sets up the object
 *
 * @return void
 * @access private
 * @since 1.0
 * @see do_action() Called by the 'init' action.
**/
function ld_setup_auth()
{
	// Instantiate the object
	$class = apply_filters('ld_class', 'WP_LockAuth');
	$auth_obj = new $class();
}

add_action('init', 'ld_setup_auth');

/* End of file: lockdown-wp-admin.php */
/* Code is poetry. */
