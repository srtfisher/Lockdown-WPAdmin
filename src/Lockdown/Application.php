<?php
/**
 * Application Layer of Lockdown WP Admin
 *
 * @package lockdown
 */
class Lockdown_Application {
	/**
	 * Main Instance Storage
	 *
	 * @var Lockdown_Manager
	 */
	protected $instance;

	/**
	 * The base to get the login url
	 *
	 * @access	private
	 */
	protected $login_base;

	/**
	 * Admin Constructor
	 *
	 * @param Lockdown_Manager
	 */
	public function __construct( Lockdown_Manager $instance ) {
		$this->instance = $instance;

		// Setup the plugin.
		$this->ininitializeConceal();

		// Hide the login form
		$this->renameLogin();
	}

	/**
	 * Setup hiding wp-admin
	 */
	protected function ininitializeConceal() {
		// Nope, they didn't enable it.
		if ( ! $this->is_hiding_admin() ) {
			return;
		}

		// We're gonna hide it.
		$no_check_files = apply_filters( 'no_check_files',
			array( 'async-upload.php', 'admin-ajax.php', 'wp-app.php' )
		);

		if ( empty( $_SERVER['SCRIPT_FILENAME'] ) ) {
			$script_filename = $_SERVER['PATH_TRANSLATED'];
		} else {
			$script_filename = $_SERVER['SCRIPT_FILENAME'];
		}

		$explode = explode( '/', $script_filename );
		$file = array_pop( $explode );

		// Disable for WP-CLI
		if ( defined( 'WP_CLI' ) and WP_CLI ) {
			return $this->instance->passed( true );
		}

		// If request is for a file we're ignoring
		if ( in_array( $file, $no_check_files ) ) {
			return $this->instance->passed( true );
		}

		// We only will hide it if we are in admin (/wp-admin/)
		if ( is_admin() ) {
			// Non logged in users.
			if ( ! is_user_logged_in() ) {
				$this->throw404();
			}

			// Setup HTTP auth.
			$this->setupHttpCheck();
		}
	}

	/**
	 * Launch and display the 404 page depending upon the template
	 *
	 * @param   void
	 * @return  void
	 */
	public function throw404() {
		// Change WP Query
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );

		// Disable that pesky Admin Bar
		add_filter( 'show_admin_bar', '__return_false', 900 );
		remove_action( 'admin_footer', 'wp_admin_bar_render', 10 );
		remove_action( 'wp_head', 'wp_admin_bar_header', 10 );
		remove_action( 'wp_head', '_admin_bar_bump_cb', 10 );
		wp_dequeue_script( 'admin-bar' );
		wp_dequeue_style( 'admin-bar' );

		// Complete override of the 404 handling
		if ( has_action( 'ld_on_invalid_access' ) ) {
			do_action( 'ld_on_invalid_access', array( $this ) );
			exit;
		}

		// Template
		$four_tpl = apply_filters( 'LD_404', get_404_template() );

		// Handle the admin bar
		@define( 'APP_REQUEST', true );
		@define( 'DOING_AJAX', true );

		if ( empty( $four_tpl ) or ! file_exists( $four_tpl ) ) {
			// We're gonna try and get TwentyTen's one
			$wp_theme_404 = apply_filters( 'LD_404_FALLBACK', WP_CONTENT_DIR . '/themes/twentyfifteen/404.php' );

			if ( file_exists( $wp_theme_404 ) ) {
				require( $wp_theme_404 );
			} else {
				wp_die( '404 - File not found!', '', array( 'response' => 404 ) );
			}
		} else {
			// Their theme has a template!
			require( $four_tpl );
		}

		// Either way, it's gonna stop right here.
		exit;
	}



	/**
	 * Rename the login URL
	 *
	 * @see do_action() Calls `ld_login_page` right before we call `wp-login.php`
	 * @access public
	 */
	public function renameLogin() {
		$this->login_base = get_option( 'ld_login_base' );

		// It's not enabled.
		if ( empty( $this->login_base ) || ! $this->login_base ) { return; }

		// Setup the filters for the new login form
		add_filter( 'wp_redirect', array( &$this, 'filterLoginUrl' ) );
		add_filter( 'network_site_url', array( &$this, 'filterLoginUrl' ) );
		add_filter( 'site_url', array( &$this, 'filterLoginUrl' ) );

		// We need to get the URL
		// This means we need to take the current URL,
		// strip it of an WordPress path (if the blog is located @ /blog/)
		// And then remove the query string
		// We also need to remove the index.php from the URL if it exists
		// The blog's URL
		$blog_url = trailingslashit( get_bloginfo( 'url' ) );

		// The Current URL
		$schema = is_ssl() ? 'https://' : 'http://';
		$current_url = $schema . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		$request_url = str_replace( $blog_url, '', $current_url );
		$request_url = str_replace( 'index.php/', '', $request_url );

		$url_parts = explode( '?', $request_url, 2 );
		$base = $url_parts[0];

		// Remove trailing slash
		$base = rtrim( $base,'/' );
		$exp = explode( '/', $base, 2 );
		$super_base = end( $exp );

		// Are they visiting wp-login.php?
		if ( 'wp-login.php' === $super_base ) {
			$this->throw404();
		}

		// Is this the "login" url?
		if ( $base !== $this->getLoginBase() ) {
			return false;
		}

		// We dont' want a WP plugin caching this page
		@define( 'NO_CACHE', true );
		@define( 'WTC_IN_MINIFY', true );
		@define( 'WP_CACHE', false );

		// Hook onto this
		do_action( 'ld_login_page' );

		// Prevent errors from defining constants again
		error_reporting( E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR );

		include ABSPATH . '/wp-login.php';
		exit;
	}

	/**
	 * Filters out wp-login to whatever they named it
	 *
	 * @access public
	 */
	public function filterLoginUrl( $str = '' ) {
		return str_replace( 'wp-login.php', $this->getLoginBase(), $str );
	}

	/**
	 * Setting up the HTTP Auth
	 * Here, we only check if it's enabled
	 *
	 * @access protected
	 */
	protected function setupHttpCheck( $option = null ) {
		// We save what type of auth we're doing here.
		if ( ! $option ) {
			$option = $this->getHttpAuth();
		}

		// What type of auth are we doing?
		switch ( $option ) {
			// HTTP auth is going to ask for their WordPress creds.
			case 'wp_creds' :
				$creds = $this->retrieveAuthCredentials();

				if ( ! $creds ) {
					$this->unauthorizedArea(); // Invalid credentials
				}
				// Are they already logged in as this?
				$current_uid = get_current_user_id();

				// We fixed this for use with non WP-MS sites
				$requested_user = get_user_by( 'login', $creds['username'] );

				// Not a valid user.
				if ( ! $requested_user ) {
					$this->unauthorizedArea(); }

				// The correct User ID.
				$requested_uid = (int) $requested_user->ID;

				// Already logged in?
				if ( $current_uid === $requested_uid ) {
					return $this->instance->passed( true ); }

				// Attempt to sign them in if they aren't already
				if ( ! is_user_logged_in() ) :
					// Try it via wp_signon
					$creds = array();
					$creds['user_login'] = $creds['username'];
					$creds['user_password'] = $creds['password'];
					$creds['remember'] = true;
					$user = wp_signon( $creds, false );

					// In error
					if ( is_wp_error( $user ) ) {
						return $this->unauthorizedArea(); }
				endif;

				// They passed!
				$this->passed( true );
			break;

			// Private list of users to check
			case 'private' :
				$users = $this->getPrivateUsers();

				// We want a user to exist.
				// If nobody is found, we won't lock them out!
				if ( ! $users || ! is_array( $users ) ) {
					return; }

				// Let's NOT lock everybody out
				if ( count( $users ) < 1 ) {
					return; }

				// Get the HTTP auth creds
				$creds = $this->retrieveAuthCredentials();

				// Invalid creds
				if ( ! $creds ) {
					$this->unauthorizedArea();
				}

				// Did they enter a valid user?
				if ( $this->matchUserToArray( $users, $creds['username'], $creds['password'] ) ) {
					$this->instance->passed( true );
					return $this->setUser( $creds['username'] );
				} else {
					return $this->unauthorizedArea();
				}

			break;

			// Unknown type of auth
			default :
				$this->instance->passed( true );
				return false;
		}
	}

	/**
	 * Send headers to the browser that are going to ask for a username/pass
	 * from the browser.
	 *
	 * @access private
	 * @return void
	 */
	protected function unauthorizedArea() {
		// Disable if there is a text file there.
		if ( file_exists( LD_PLUGIN_DIR . '/disable_auth.txt' ) ) {
			return;
		}

		header( 'WWW-Authenticate: Basic realm="'. esc_attr( $this->instance->relm ) . '"' );
		header( 'HTTP/1.0 401 Unauthorized' );
		echo '<h1>Authorization Required.</h1>';
		exit;
	}

	/**
	 * Set the current user
	 *
	 * @access private
	 * @param  string $username
	 */
	public function setUser( $username ) {
		$users = $this->getPrivateUsers();
		foreach ( $users as $key => $val ) {
			if ( $val['user'] === $username ) {
				$this->current_user = $key;
				return $this->current_user;
			}
		}
	}

	/**
	 * Get the current file name
	 *
	 * @return string JUST the file name
	 */
	public function retrieveFile() {
		// We're gonna hide it.
		$no_check_files = array( 'async-upload.php' );
		$no_check_files = apply_filters( 'no_check_files', $no_check_files );

		$script_filename = empty( $_SERVER['SCRIPT_FILENAME'] )
			? $_SERVER['PATH_TRANSLATED']
			: $_SERVER['SCRIPT_FILENAME'];
		$explode = explode( '/', $script_filename );
		return end( $explode );
	}

	/**
	 * Check an internal array of users against a passed user and pass
	 *
	 * @access protected
	 * @return bool
	 *
	 * @param array  $array The array of users
	 * @param string $user The username to check for
	 * @param string $pass The password to check for (plain text)
	 */
	protected function matchUserToArray( $array, $user, $pass ) {
		foreach ( $array as $key => $val ) {
			if ( ! isset( $val['user'] ) || ! isset( $val['pass'] ) ) {
				continue;
			}

			if ( $val['user'] === $user && md5( $pass ) === $val['pass'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * See if a login base is suggested from being used
	 *
	 * @return boolean
	 */
	public function isSuggestedAgainst() {
		return in_array( $this->login_base, array(
			'login',
			'admin',
			'user-login',
		) );
	}

	/**
	 * Retrieve the login base
	 *
	 * @return string
	 * @param  string Default
	 */
	public function getLoginBase( $default = '' ) {
		return ( $this->login_base ) ? $this->login_base : $default;
	}

	/**
	 * Update and save the login base
	 *
	 * @param  string
	 */
	public function setLoginBase( $base = '' ) {
		update_option( 'ld_login_base', $base );
		$this->login_base = $base;
		return $this;
	}

	/**
	 * Retrieve the HTTP Auth setting
	 *
	 * @return string
	 */
	public function getHttpAuth() {
		return get_option( 'ld_http_auth' );
	}

	/**
	 * Set the HTTP Auth usage
	 *
	 * @param string
	 */
	public function setHttpAuth( $type = 'none' ) {
		update_option( 'ld_http_auth', $type );
	}

	/**
	 * Set the option to hide WP Admin
	 *
	 * @param bool True for enabled, otherwise false
	 */
	public function setHideWpAdmin( $status ) {
		update_option( 'ld_hide_wp_admin', (bool) $status );
	}

	/**
	 * Get a username and password from the Basic HTTP auth
	 *
	 * @return array|bool
	 */
	public function retrieveAuthCredentials() {
		// Since PHP saves the HTTP Password in a bunch of places, we have to be able to test for all of them
		$username = $password = null;

		// mod_php
		if ( isset( $_SERVER['PHP_AUTH_USER'] ) ) {
			$username = ( isset( $_SERVER['PHP_AUTH_USER'] ) ) ? $_SERVER['PHP_AUTH_USER'] : null;
			$password = ( isset( $_SERVER['PHP_AUTH_PW'] ) ) ? $_SERVER['PHP_AUTH_PW'] : null;
		} // most other servers
		elseif ( $_SERVER['HTTP_AUTHENTICATION'] ) {
			if ( 0 === strpos( strtolower( $_SERVER['HTTP_AUTHENTICATION'] ), 'basic' ) ) {
				list( $username, $password ) = explode( ':', base64_decode( substr( $_SERVER['HTTP_AUTHENTICATION'], 6 ) ) );
			}
		}

		// Check them - if they're null a/o empty, they're invalid.
		if ( is_null( $username ) OR is_null( $password ) OR empty( $username ) OR empty( $password ) ) {
			return false;
		} else {
			return compact( 'username', 'password' );
		}
	}

	/**
	 * Get the users for the private creds
	 *
	 * @access private
	 */
	public function getPrivateUsers() {
		return (array) get_option( 'ld_private_users' );
	}

	/**
	 * Set the private users
	 *
	 * @param array $users
	 */
	public function setPrivateUsers( array $users ) {
		return update_option( 'ld_private_users', $users );
	}

	/**
	 * Add Private User
	 *
	 * @param  string $username
	 * @param  string $password
	 * @throws Exception
	 */
	public function addPrivateUser( $username, $password ) {
		$users = $this->getPrivateUsers();

		// Ensure the data is valid
		if ( empty( $username ) || empty( $password ) ) {
			throw new Exception( __( 'Username/password is empty.', 'lockdown-wp-admin' ) );
		}

		if ( $this->doesUsernameExist( $username ) ) {
			throw new Exception( __( 'Username already exists.', 'lockdown-wp-admin' ) );
		} else {
			$users[] = array(
				'user' => sanitize_user( $username ),
				'pass' => trim( md5( $password ) ),
			);
		}

		$this->setPrivateUsers( $users );
	}

	/**
	 * See if a user exists in the array
	 *
	 * @access public
	 * @return boolean
	 * @param  string $username
	 */
	public function doesUsernameExist( $username ) {
		$users = $this->getPrivateUsers();

		if ( empty( $users ) ) {
			return false;
		} else {
			return in_array( $username, wp_list_pluck( $users, 'user' ) );
		}
	}

	/**
	 * Determine if we're hiding wp-admin
	 * The use of 'yep' is deprecated.
	 *
	 * @return bool
	 */
	public function is_hiding_admin() {
		$opt = get_option( 'ld_hide_wp_admin' );
		return ( 'yep' === $opt || '1' == $opt );
	}
}
