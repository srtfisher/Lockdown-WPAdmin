<?php
/**
* Lockdown WP Admin Manager Class
*
* @author   Sean Fisher <me@seanfisher.co>
* @version  2.3
* @license  GPL
* @package  lockdown
*/
class Lockdown_Manager
{
  /**
  * The version of lockdown WP Admin
  *
  * @global string
  */
  public static $ld_admin_version = '2.3';

  /**
  * The HTTP Auth name for the protected area
  * Change this via calling the object, not by editing the file.
  *
  * @access	public
  * @type	string
  */
  public $relm = 'Secure Area';

  /**
  * The current user ID from our internal array
  *
  * @access	private
  **/
  protected $current_user = FALSE;

  /**
  * Check if the Auth passed
  * See {@link Lockdown_Manager::getAuthPassed()}
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
   * Static Instance of Manager
   *
   * @var Lockdown_Manager
   */
  protected static $instance;

  /**
  * Constructor
  *
  * @return void
  */
  public function __construct()
  {
    // We don't like adding network wide WordPress plugins.
    if (! class_exists('Disable_WPMS_Plugin_LD')) {
      require_once( LD_PLUGIN_DIR . '/no-wpmu.php' );
    }

    // Include dependant classes
    require_once(LD_PLUGIN_DIR.'/src/Lockdown/Application.php');
    require_once(LD_PLUGIN_DIR.'/src/Lockdown/Admin.php');

    // Instantiate objects
    $this->admin = new Lockdown_Admin($this);
    $this->application = new Lockdown_Application($this);
  }

  /**
   * Retrieve the singleton instance of the manager
   *
   * @return Lockdown_Manager
   */
  public static function instance()
  {
    if (!static::$instance) {
      static::$instance = new static;
    }

    return static::$instance;
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
  * See {@link Lockdown_Manager::getAuthPassed()}
  *
  * @access public
  * @param boolean
  */
  public function passed($value)
  {
    $this->passed = (bool) $value;
  }
}
