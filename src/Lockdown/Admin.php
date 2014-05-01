<?php
class Lockdown_Admin {
	/**
	 * Main Instance Storage
	 *
	 * @var WP_LockAuth
	 */
	protected $instance;

	/**
	 * Admin Constructor
	 * 
	 * @param WP_LockAuth
	 */
	public function __construct(WP_LockAuth $instance)
	{
		$this->instance = $instance;

		// Add the action to setup the menu.
		add_action('admin_menu', array( $this, 'add_admin_menu'));
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
		// Update the options
		$this->updateSettings();
		
		// The UI
		require_once( LD_PLUGIN_DIR . '/views/settings.php' );
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
		$private_users = $this->instance->application->getPrivateUsers();
		require_once( LD_PLUGIN_DIR . '/admin-private-users.php' );
	}

	/**
	 * Update the options
	 *
	 * @access private
	**/
	public function updateSettings()
	{
		if ( !isset( $_GET['page'] ) || $_GET['page'] !== 'lockdown-wp-admin' || !isset( $_POST['did_update'] ))
			return;
		
		// Nonce
		$nonce = $_POST['_wpnonce'];
		if ( ! wp_verify_nonce($nonce, 'lockdown-wp-admin') )
			wp_die('Security error, please try again.');
		
		// ---------------------------------------------------
		// They're updating.
		// ---------------------------------------------------
		if ( isset( $_POST['http_auth'] ) )
			update_option('ld_http_auth', trim( strtolower( $_POST['http_auth'] ) ) );
		else
			update_option('ld_http_auth', 'none' );
		
		if ( ! isset( $_POST['hide_wp_admin'] ) )
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
			$base = sanitize_title_with_dashes( $_POST['login_base']);
			$base = str_replace('/', '', $base);
			
			$disallowed = array(
				'user', 'wp-admin', 'wp-content', 'wp-includes', 'wp-feed.php', 'index', 'feed', 'rss', 'robots', 'robots.txt', 'wp-login.php',
				'wp-login', 'wp-config', 'blog', 'sitemap', 'sitemap.xml',
			);
			if ( in_array( $base, $disallowed ) )
			{
				return define('LD_DIS_BASE', TRUE);
			}
			else
			{
				
				update_option('ld_login_base', $base);
				$this->instance->application->setLoginBase(sanitize_title_with_dashes ( $base ));
			}
		}
		
		// Redirect
		return define('LD_WP_ADMIN', TRUE);
	}

	/**
	 * Update the users
	 *
	 * @access private
	**/
	public function update_users()
	{
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'lockdown-private-users')
			return;
		
		// Nonce
		if ( ! isset( $_REQUEST['_wpnonce'] ) )
			return;
		
		$nonce = $_REQUEST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'lockdown-wp-admin' ) )
			wp_die('Security error, please try again.');
		
		// Add a user
		if ( isset( $_POST['private_username'] ) && isset( $_POST['private_password'] ) )
		{
			if ( $_POST['private_username'] !== '' && $_POST['private_password'] !== '' )
			{
				// Adding a user
				$users = $this->instance->application->getPrivateUsers();
				$add['user'] = sanitize_user( $_POST['private_username'] );
				$add['pass'] = trim( md5( $_POST['private_password'] ) );
				
				// See if it exists
				if ($this->instance->application->userExists($users, $add['user']))
					return define('LD_ERROR', 'username-exists');
				else
					$users[] = $add;
				
				update_option('ld_private_users', $users);
				
				return define('LD_WP_ADMIN', TRUE);
			}
		}
		
		// Deleting a user.
		if ( isset( $_GET['delete'] ) )
		{
			// Delete the user.
			unset( $users );
			$users = $this->instance->application->getPrivateUsers();
			$to_delete = (int) $_GET['delete'];
			
			if ( count( $users ) > 0 )
			{
				foreach( $users as $key => $val )
				{
					if ( $key === $to_delete ) :
						if( $this->current_user !== '' && $to_delete === $this->current_user )
						{
							// They can't delete themselves!
							return define('LD_ERROR', 'delete-self');
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
}