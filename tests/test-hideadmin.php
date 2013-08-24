<?php
class LockdownAdminTest extends PHPUnit_Framework_TestCase {
	protected $object;

	protected function setUp()
	{
		$this->object = ld_setup_auth();
	}

	public function testWhitelist()
	{
		add_filter('no_check_files', function($a) { $a[] = 'wp-activate.php'; return $a; });
		update_option('ld_hide_wp_admin', 'yep');
		
		// Mocking a request to wp-activate.php
		$_SERVER['SCRIPT_FILENAME'] = ABSPATH.'/wp-activate.php';

		// Set it back up again so we can test if it passed
		$this->setUp();
		$this->assertTrue($this->object->getAuthPassed());
	}

	public function testWhitelistToBlock()
	{
		update_option('ld_hide_wp_admin', 'yep');
		
		// Mocking a request to wp-activate.php
		$_SERVER['SCRIPT_FILENAME'] = ABSPATH.'/wp-login.php';

		// Set it back up again so we can test if it passed
		$this->setUp();
		$this->assertFalse($this->object->getAuthPassed());
	}
}