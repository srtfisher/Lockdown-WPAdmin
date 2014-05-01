<?php if (! defined('ABSPATH')) exit;
/*
Plugin Name: Lockdown WP Admin
Plugin URI: http://seanfisher.co/lockdown-wp-admin/
Donate link: http://seanfisher.co/donate/
Description: Securing the WordPress Administration interface by concealing the administration dashboard and changing the login page URL.
Version: 2.2
Author: Sean Fisher
Author URI: http://seanfisher.co/
License: GPL
*/

// This file name
define('LD_FILE_NAME', __FILE__ );
define('LD_PLUGIN_DIR', dirname(__FILE__));

/**
 * This is the plugin that will add security to our site
 *
 * @author   Sean Fisher <me@seanfisher.co>
 * @version  2.1
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
	public static $ld_admin_version = '2.2';
	
	/**
	 * The HTTP Auth name for the protected area
	 * Change this via calling the object, not by editing the file.
	 *
	 * @access	public
	 * @type	string
	**/
	public $relm = 'Secure Area';
	
	/**
	 * The current user ID from our internal array
	 *
	 * @access	private
	**/
	protected $current_user = FALSE;
	
	/**
	 * Check if the Auth passed
	 * See {@link WP_LockAuth::getAuthPassed()}
	 * 
	 * @type boolean
	 */
	protected $passed = FALSE;

	/**
	 * Admin Instance
	 *
	 * @type Lockdown_Admin
	 */
	public $admin;

	/**
	 * Application Instance
	 *
	 * @type  Lockdown_Application
	 */
	public $application;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct()
	{
		// We don't like adding network wide WordPress plugins.
		if (! class_exists('Disable_WPMS_Plugin_LD'))
			require_once( dirname( __FILE__ ) .'/no-wpmu.php' );
			
		require_once(LD_PLUGIN_DIR.'/src/Lockdown/Application.php');
		require_once(LD_PLUGIN_DIR.'/src/Lockdown/Admin.php');

		// Instantiate objects
		$this->admin = new Lockdown_Admin($this);
		$this->application = new Lockdown_Application($this);
	}
	
	/**
	 * Get the users for the private creds
	 *
	 * @deprecated Moved to `Lockdown_Application::getPrivateUsers()`
	**/
	public function get_private_users()
	{
		return $this->application->getPrivateUsers();
	}
	
	/**
	 * Set the current user
	 *
	 * @deprecated Moved to {@see Lockdown_Application::setUser()}
	**/
	protected function set_current_user( $array, $user )
	{
		return $this->application->setUser($array, $user);
	}

	/**
	 * Retrieve the Login Base
	 * @return string
	 */
	public function getLoginBase()
	{
		return $this->application->getLoginBase();
	}

	/**
	 * See if the auth passed
	 * 
	 * @return boolean
	 */
	public function getAuthPassed()
	{
		return (bool) $this->passed;
	}

	/**
	 * Update the Passed Auth Value
	 * See {@link WP_LockAuth::getAuthPassed()}
	 * 
	 * @access public
	 * @param boolean
	 */
	public function passed($value)
	{
		$this->passed = (bool) $value;
	}
}

/**
 * The function called at 'init'.
 * Sets up the object
 *
 * @return object
 * @access private
 * @since 1.0
 * @see do_action() Called by the 'init' action.
**/
function ld_setup_auth()
{
	// Instantiate the object
	$class = apply_filters('ld_class', 'WP_LockAuth');
	$auth_obj = new $class();

	return $auth_obj;
}

add_action('init', 'ld_setup_auth', 20);

/* End of file: lockdown-wp-admin.php */
/* Code is poetry. */
