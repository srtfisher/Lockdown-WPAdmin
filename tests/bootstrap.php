<?php
/**
 * Unit Testing for Lockdown WP Admin
 *
 * Not ready for continous intergration yet but will be soon!
 * Have to make it non-dependant upon the WordPress in the parent directory
 *
 * @package lockdown-wpadmin
 */
if ( getenv( 'WP_TESTS_DIR' ) ) :
	require_once getenv( 'WP_TESTS_DIR' ) . '/includes/functions.php';

	function _manually_load_plugin() {
		require dirname( __FILE__ ) . '/../sample-plugin.php';
	}
	tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

	require getenv( 'WP_TESTS_DIR' ) . '/includes/bootstrap.php';
else :
	// Default back to local testing
	if (! class_exists('PHPUnit_Framework_TestCase')) :
		if (! file_exists(dirname(dirname(__FILE__)).'/vendor/autoload.php'))
			die('Composer not initialized (PHPUnit not installed)');
		else
			require_once dirname(dirname(__FILE__)).'/vendor/autoload.php';
	endif;

	// Still dependant upon loading WP Core
	require(dirname(__FILE__).'/../../../../wp-load.php');

	error_reporting(-1);
	ini_set('display_errors', 'on');
	$_SERVER['HTTP_HOST'] = 'localhost';

	require_once dirname(__FILE__).'/../lockdown-wp-admin.php';
endif;