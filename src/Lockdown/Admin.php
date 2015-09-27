<?php
/**
 * Admin Interface for Lockdown WP Admin
 *
 * @package lockdown
 * @codeCoverageIgnore
 */
class Lockdown_Admin {
	/**
	 * Main Instance Storage
	 *
	 * @var Lockdown_Manager
	 */
	protected $instance;

	/**
	 * Message Storage
	 *
	 * @var array
	 */
	protected $messages = array();

	/**
	 * Valid HTTP Auth Settings
	 *
	 * @var arry
	 */
	protected $valid_http_auth = array( 'none', 'wp_creds', 'private' );

	/**
	 * Admin Constructor
	 *
	 * @param Lockdown_Manager
	 */
	public function __construct( Lockdown_Manager $instance ) {
		$this->instance = $instance;

		// Add the action to setup the menu.
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
	}

	/**
	 * Adds the admin menu
	 *
	 * @access private
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Lockdown WP', 'lockdown-wp-admin' ),
			__( 'Lockdown WP', 'lockdown-wp-admin' ),
			'manage_options',
			'lockdown-wp-admin',
			array( $this, 'display_settings_page' )
		);

		add_submenu_page(
			'lockdown-wp-admin',
			__( 'Private Users', 'lockdown-wp-admin' ),
			__( 'Private Users', 'lockdown-wp-admin' ),
			'manage_options',
			'lockdown-private-users',
			array( $this, 'display_settings_users_page' )
		);
	}

	/**
	 * The callback for the admin area
	 *
	 * You need the 'manage_options' capability to get here.
	 */
	public function display_settings_page() {
		// Update the options
		$this->settings_page_update();

		// The UI
		require_once( LD_PLUGIN_DIR . '/views/settings.php' );
	}

	/**
	 * The callback for ther private users management.
	 *
	 * You need the 'manage_options' capability to get here.
	 */
	public function display_settings_users_page() {
		// Update the users options
		$this->users_page_update();

		// The UI
		$private_users = $this->instance->application->getPrivateUsers();
		require_once( LD_PLUGIN_DIR . '/views/private-users.php' );
	}

	/**
	 * Update the options
	 *
	 * @access private
	 */
	public function settings_page_update() {
		if ( ! isset( $_GET['page'] ) || 'lockdown-wp-admin' !== $_GET['page'] || empty( $_POST['did_update'] ) ) {
			return;
		}

		// Nonce
		$nonce = $_POST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'lockdown-wp-admin' ) ) {
			wp_die( __( 'Security error, please try again.', 'lockdown-wp-admin' ) );
		}

		// ---------------------------------------------------
		// They're updating.
		// ---------------------------------------------------
		$use_http_auth = 'none';
		if ( ! empty( $_POST['http_auth'] ) && in_array( $_POST['http_auth'], $this->valid_http_auth ) ) {
			$use_http_auth = $_POST['http_auth'];
		}
		$this->instance->application->setHttpAuth( $use_http_auth );
		$this->instance->application->setHideWpAdmin( ( ! empty( $_POST['hide_wp_admin'] ) && 'yes' === $_POST['hide_wp_admin'] ) );

		if ( isset( $_POST['login_base'] ) ) {
			$base = sanitize_title_with_dashes( $_POST['login_base'] );
			$base = str_replace( '/', '', $base );

			$disallowed = array(
				'user',
				'wp-admin',
				'wp-content',
				'wp-includes',
				'wp-feed.php',
				'index',
				'feed',
				'rss',
				'robots',
				'robots.txt',
				'wp-login.php',
				'wp-login',
				'wp-config',
				'blog',
				'sitemap',
				'sitemap.xml',
			);

			if ( in_array( $base, $disallowed ) ) {
				return $this->add_message( __( 'That login base is not permitted.', 'lockdown-wp-admin' ), 'error' );
			} else {
				$this->instance->application->setLoginBase( sanitize_title_with_dashes( $base ) );
			}
		}

		$this->add_message( __( 'Settings saved.', 'lockdown-wp-admin' ) );
	}

	/**
	 * Update the users
	 *
	 * @access private
	 */
	public function users_page_update() {
		if ( ! isset( $_GET['page'] ) || 'lockdown-private-users' !== $_GET['page'] || empty( $_REQUEST['_wpnonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'lockdown-wp-admin' ) ) {
			wp_die( __( 'Security error, please try again.', 'lockdown-wp-admin' ) );
		}

		// Add a user
		if ( ! empty( $_POST['private_username'] ) && ! empty( $_POST['private_password'] ) ) {
			try {
				$this->instance->application->addPrivateUser( $_POST['private_username'], $_POST['private_password'] );
			} catch ( Exception $e ) {
				return $this->add_message( $e->getMessage(), 'error' );
			}

			return $this->add_message( __( 'User added.', 'lockdown-wp-admin' ) );
		}

		// Deleting a user (have to use isset since 'delete' could be 0)
		if ( isset( $_GET['delete'] ) ) {
			$users = $this->instance->application->getPrivateUsers();
			$to_delete = (int) $_GET['delete'];

			if ( ! empty( $users ) ) {
				foreach ( $users as $key => $val ) {
					if ( $key === $to_delete ) {
						if ( $to_delete === $this->instance->application->current_user ) {
							// They can't delete themself.
							return $this->add_message( __( 'You cannot delete yourself.', 'lockdown-wp-admin' ) );
						} else {
							unset( $users[ $key ] );
						}
					}
				}
			}

			$this->instance->application->setPrivateUsers( $users );
			$this->add_message( __( 'User deleted.', 'lockdown-wp-admin' ) );
		}
	}

	/**
	 * Add a message to display
	 *
	 * @param string $message
	 */
	public function add_message( $message, $type = 'normal' ) {
		$this->messages[] = compact( 'message', 'type' );
	}

	/**
	 * Retrive messages
	 *
	 * @return array
	 */
	public function get_messages() {
		return $this->messages;
	}
}
