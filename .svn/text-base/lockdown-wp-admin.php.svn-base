<?php
/*
Plugin Name: Lockdown WordPress Admin
Plugin URI: http://talkingwithsean.com/2011/01/lockdown-wp-admin/
Description: Securing the WordPress Administration interface.
Version: 1.4.1
Author: Sean Fisher
Author URI: http://talkingwithsean.com/
License: GPL v3
*/



//	This file name
define('LD_FILE_NAME', __FILE__ );

/**
 *	This is the plugin that will add security to our site
 *
 *	@author Sean Fisher <me@tlksean.me>
 *	@version 1.1.2
 *	@license GPL v3
**/
class WP_LockAuth {
	
	/**
	 * The version of lockdown WP Admin
	 *
	 * @param string
	 * @access private
	**/
	private $ld_admin_version = '1.4';
	
	/**
	 * The HTTP Auth name for the protected area
	 * Change this via calling the object, not by editing the file.
	 *
	 * @access public
	 * @global string
	**/
	public $relm = "Secure Area";
	
	/**
	 * The current user ID from our internal array
	 *
	 * @access private
	**/
	private $current_user = FALSE;
	
	/**
	 * The base to get the login url
	 *
	 * @access private
	**/
	private $login_base = FALSE;
	
	function WP_LockAuth()
	{
		//	We don't like adding network wide WordPress plugins.
		require_once( dirname( __FILE__ ) .'/no-wpmu.php' );
		
		//	Add the action to setup the menu.
		add_action('admin_menu', array( &$this, 'add_admin_menu'));
		
		//	Setup the plugin.
		$this->setup_hide_admin();
		
		//	Hide the login form
		$this->redo_login_form();
		
		//	We no longer update the options here, but rather when we call on the callback function from the menu.
		//	More secure.
	}
	
	/**
	 * Update the users
	 *
	 * @access private
	**/
	function update_users()
	{
		if ( !isset( $_GET['page'] ) )
			return;
		
		if ( $_GET['page'] !== 'lockdown-private-users' )
			return;
		
		//	Nonce
		if ( !isset( $_REQUEST['_wpnonce'] ) )
			return;
		
		$nonce = $_REQUEST['_wpnonce'];
		if ( !wp_verify_nonce( $nonce, 'lockdown-wp-admin' ) )
			wp_die('Security error, please try again.');
		
		//	---------------------------------------------------
		
		//	Add a user
		if ( isset( $_POST['private_username'] ) && isset( $_POST['private_password'] ) )
		{
			if ( $_POST['private_username'] !== '' && $_POST['private_password'] !== '' )
			{
				//	Adding a user.
				$users = $this->get_private_users();
				$add['user'] = sanitize_user( $_POST['private_username'] );
				$add['pass'] = trim( md5( $_POST['private_password'] ) );
				
				$users[] = $add;
				
				update_option('ld_private_users', $users);
				
				define('LD_WP_ADMIN', TRUE);
				//wp_redirect( admin_url('admin.php?page=lockdown-private-users&updated=true'));
				return;
			}
		}
		
		//	Deleting a user.
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
	function update_options()
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
		header('WWW-Authenticate: Basic realm="'.$this->relm.'"');
		header('HTTP/1.0 401 Unauthorized');
		echo '<h1>Authorization Required.</h1>';
		exit;
	}
	
	/**
	 * Check for a HTTP auth session
	 *
	 * If they find one, we will setup the 'INTERNAL_AUTH_PASSED' constant.
	 * If they failed, it will send the HTTP auth headers to get the username/
	 * password.
	 *
	 * @uses self::inauth_headers() When we need the username/pass
	 * @access public
	**/
	public function setup()
	{
		/* Check for values in $PHP_AUTH_USER and $PHP_AUTH_PW */
		if ((!isset($_SERVER['PHP_AUTH_USER'])) || (!isset($_SERVER['PHP_AUTH_PW']))) {
			$this->inauth_headers();
		
		} else if ((isset($_SERVER['PHP_AUTH_USER'])) && (isset($_SERVER['PHP_AUTH_PW']))){
			
			/* Values contain some values, so check to see if they're correct */
			
			if (($_SERVER['PHP_AUTH_USER'] != $this->current_user) || (md5($_SERVER['PHP_AUTH_PW']) != $this->current_pass)) {
				 /* If either the username entered is incorrect, or the password entered is incorrect, send the headers causing dialog box to appear */
				 $this->inauth_headers();
				 
			} else if (($_SERVER['PHP_AUTH_USER'] === $this->current_user) || ( md5($_SERVER['PHP_AUTH_PW'] ) === $this->current_pass)) {
				
				 /* if both values are correct, print success message */
				 //	We're good here!
				 define('INTERNAL_AUTH_PASSED', TRUE);
			}
		} 
	}
	
	/**
	 * Get the users for the private creds
	 *
	 * @access private
	**/
	function get_private_users()
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
	function setup_hide_admin()
	{
		$opt = get_option('ld_hide_wp_admin');
		
		//	Nope!
		if ( $opt !== 'yep' )
			return;
		
		//	We're gonna hide it.
		$no_check_files = array('async-upload.php');
		$no_check_files = apply_filters('no_check_files', $no_check_files);
		
		$explode = explode('/', $_SERVER['SCRIPT_FILENAME'] );
		$file = end( $explode );
    	if ( in_array( $file, $no_check_files ) )
    	{
			define('INTERNAL_AUTH_PASSED', TRUE);
			return;
		}
    	
		//	We only will hide it if we are in admin (/wp-admin/)
		if ( is_admin() )
		{
			//	Non logged in users.
			if ( !is_user_logged_in() )
			{
				//	If they AREN'T logged in and they tried to access wp-admin
				//	we'll just serve them a 404!
				status_header(404);
				require( get_404_template() );
				
				exit;
			}
			
			//	Setup HTTP auth.
			$this->setup_http_area();
		}
	}
	
	/**
	 * Get the current file name
	 *
	 * @return string JUST the file name
	**/
	function get_file()
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
	 * @access private
	**/
	function setup_http_area()
	{
		//	We save what type of auth we're doing here.
		$opt = get_option('ld_http_auth');
		
		switch( $opt )
		{
			//	HTTP auth is going to ask for their WordPress creds.
			case('wp_creds');
				
				/* Check for values in $PHP_AUTH_USER and $PHP_AUTH_PW */
				if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']))
					$this->inauth_headers();
				
				//	Are they already logged in as this?
				$current_uid = get_current_user_id();
				
				//	We fixed this for use with non WP-MS sites
				$requested_user = get_user_by('login', $_SERVER['PHP_AUTH_USER']);
				
				//	Not a valid user.
				if ( !$requested_user )
					$this->inauth_headers();
				
				//	The correct User ID.
				$requested_uid = (int) $requested_user->ID;
				
				//	Already logged in?
				if ( $current_uid === $requested_uid )
				{
					define('INTERNAL_AUTH_PASSED', TRUE);
					return;
				}
				
				//	Attempt to sign them in if they aren't alerady
				if ( !is_user_logged_in() ) :
					//	Try it via wp_signon
					$creds = array();
					$creds['user_login'] = $_SERVER['PHP_AUTH_USER'];
					$creds['user_password'] = $_SERVER['PHP_AUTH_PW'];
					$creds['remember'] = true;
					$user = wp_signon( $creds, false );
					
					//	In error :(
					if ( is_wp_error($user) )
						$this->inauth_headers();
				endif;
				
				//	They passed!
				define('INTERNAL_AUTH_PASSED', TRUE);
				break;
			
			case('private');
				$users = $this->get_private_users();
				
				//	We want a user to exist
				//	If nobody is found, we won't lock them out!
				if ( !$users || !is_array( $users ) )
					return;
				
				//	Let's NOT lock everybody out
				if ( count( $users ) === 0 )
					return;
				
				/* Check for values in $PHP_AUTH_USER and $PHP_AUTH_PW */
				if ( !isset( $_SERVER['PHP_AUTH_USER'] ) || !isset( $_SERVER['PHP_AUTH_PW'] ) )
					$this->inauth_headers();
				
				//	Did they enter a valid user?
				if ( $this->user_array_check( $users, $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] ) )
				{
					//	Yes!!
					define('INTERNAL_AUTH_PASSED', TRUE);
					$this->set_current_user( $users, $_SERVER['PHP_AUTH_USER'] );
					return;
				}
				else
				{
					//	Nope
					$this->inauth_headers();
					return;
				}
				
				break;
		}
		
	}
	/**
	 * Check an internal array of users against a passed user and pass
	 *
	 * @access public
	 * @return bool
	 *
	 * @param array $array The array of users
	 * @param string $user The username to check for
	 * @param string $pass The password to check for (plain text)
	**/
	function user_array_check( $array, $user, $pass )
	{
		foreach( $array as $key => $val )
		{
			if ( $val['user'] === $user && md5( $pass ) === $val['pass'] )
				return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * Set the current user
	 *
	 * @access private
	**/
	function set_current_user( $array, $user )
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
	function add_admin_menu()
	{
		add_menu_page('Lockdown WP', 'Lockdown WP', 'manage_options', 'lockdown-wp-admin', array( &$this, 'admin_callback'));
		add_submenu_page( 'lockdown-wp-admin', 'Private Users', 'Private Users', 'manage_options', 'lockdown-private-users',  array( &$this, 'sub_admin_callback'));
	}
	
	/**
	 * The callback for the admin area
	 *
	 * You need the 'manage_options' capability to get here.
	**/
	function admin_callback()
	{
		//	Update the options
		$this->update_options();
		
		//	The stats
		$check_stats_sent = get_transient('ld_send_stats');
		if ( !$check_stats_sent )
			$this->send_stats();
		
		//	The UI
		require_once( dirname( __FILE__ ) . '/admin.php' );
	}	
	
	/**
	 * The callback for ther private users management.
	 *
	 * You need the 'manage_options' capability to get here.
	**/
	function sub_admin_callback()
	{
		//	Update the users options
		$this->update_users();
		
		//	The UI
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
		if ( $login_base == NULL || !$login_base || $login_base == '' )
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
		{
			status_header(404);
			require( get_404_template() );
			
			exit;
		}
		
		//	Is this the "login" url?
		if ( $base !== $this->login_base )
			return FALSE;
		
		//	We dont' want a WP plugin caching this page
		@define('NO_CACHE', TRUE);
		@define('WTC_IN_MINIFY', TRUE);
		@define('WP_CACHE', FALSE);
		
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
	 * Send stats
	 *
	 * Send anyomous stats to help out the development of the plugin.
	 * This should be pretty temporary.
	 * @access private
	**/
	public function send_stats()
	{
		global $wp_version;
		
		$to_post = array(
			'ld_admin_version'	=>	$this->ld_admin_version,
			'server'			=>	$_SERVER['HTTP_HOST'],
			'request_url'		=>	$_SERVER['REQUEST_URI'],
			'wordpress_version'	=>	$wp_version,
			'url'				=>	get_bloginfo( 'url' ),
			//	I reconsidered this..
			//	'admin_email'		=>	get_bloginfo('admin_email'),
			'charset'			=>	get_bloginfo('charset'),
			'login_base'		=>	$this->login_base,
			'ld_http_auth'		=>	get_option('ld_http_auth'),
			'ld_hide_wp_admin'	=>	get_option('ld_hide_wp_admin'),
			'permalink_structure'	=>	get_option('permalink_structure'),
			'server_software'		=> $_SERVER['SERVER_SOFTWARE'],
			'query_string'			=> $_SERVER['QUERY_STRING'],
			'wp_version'			=>	$wp_version,
		);
		
		if ( function_exists('got_mod_rewrite '))
			$to_post['got_mod_rewrite '] = got_mod_rewrite();
		
		$options = array(
			'timeout' => ( ( defined('DOING_CRON') && DOING_CRON ) ? 30 : 3),
			'body' => array( 'data' => serialize( $to_post ) ),
			'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' )
		);

		$raw_response = wp_remote_post('http://labs.talkingwithsean.com/lockdown-api/main/send/', $options);
		
		//	Set that we sent it
		set_transient('ld_send_stats', 'true', 604800);
		
		//	What'd they respond?
		if ( is_wp_error( $raw_response ) )
			return FALSE;
	
		if ( 200 != $raw_response['response']['code'] )
			return FALSE;
	
		$response = json_decode( unserialize( $raw_response['body'] ) );
		
		if ( !is_array( $response ) )
			return FALSE;
			
	}
}

/**
 * The function called at 'init'.
 *
 * Sets up the object
 *
 * @return void
 * @access private
 * @version 1.0
 * @see do_action() Called by the 'init' hook'
**/
function ld_setup_auth()
{
	//	Setup the object.
	$auth_obj = new WP_LockAuth();
}

add_action('init', 'ld_setup_auth');

/* End of file: lockdown-wp-admin.php */