<?php
use \Mockery as m;
class LockdownAdminTest extends PHPUnit_Framework_TestCase {
	protected $object;

	protected function setUp()
	{
		$this->object = ld_setup_auth();
	}

	protected function tearDown()
	{
		m::close();
	}

	public function testWhitelist()
	{
		add_filter('no_check_files', function($a) { $a[] = 'wp-activate.php'; return $a; });
		$this->object->application->setHideWpAdmin( true );

		// Mocking a request to wp-activate.php
		$_SERVER['SCRIPT_FILENAME'] = ABSPATH.'/wp-activate.php';

		// Set it back up again so we can test if it passed
		$this->setUp();
		$this->assertTrue( $this->object->getAuthPassed() );

		remove_all_filters( 'no_check_files' );
	}

	public function testWhitelistToBlock()
	{
		$this->object->application->setHideWpAdmin( true );

		// Mocking a request to wp-activate.php
		$_SERVER['SCRIPT_FILENAME'] = ABSPATH.'/wp-login.php';

		// Set it back up again so we can test if it passed
		$this->setUp();
		$this->assertFalse( $this->object->getAuthPassed() );
	}

	//
	// public function testConceal() {
	// 	global $current_screen;
	// 	$this->assertFalse( is_admin() );
	// 	$screen = WP_Screen::get( 'admin_init' );
	// 	$current_screen = $screen;
	//
	// 	$this->object->application->setHideWpAdmin( true );
	// 	$this->assertTrue( is_admin() );
	//
	// 	// Add action to get called
	// 	$mock = $this->getMock('stdClass', array( 'onInvalidAccess' ));
	// 	$mock->expects($this->once())
	// 		->method( 'onInvalidAccess' )
	// 		->will( $this->returnValue( true ) );
	//
	// 	$app = new Lockdown_Application( $this->object );
	//
	// 	// Reset current screen
	// 	$current_screen = null;
	// }
}
