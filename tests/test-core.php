<?php
class LockdownTest extends PHPUnit_Framework_TestCase {
	protected $object;

	protected function setUp()
	{
		$this->object = ld_setup_auth();
	}

	/**
	 * Test that the application has added an action to init
	 */
	public function testActionAdded()
	{
		$this->assertTrue(has_action('init'));
	}

	/**
	 * The ability to overwrite the Lockdown WP Admin Object
	 */
	public function testOverwriteLdObject()
	{
		add_filter('ld_class', function() { return 'LdProxyObject'; });
		$setup = ld_setup_auth();
		$this->assertEquals('LdProxyObject', get_class($setup));
		$this->assertEquals('WP_LockAuth', get_class($this->object));
	}

	public function testFiltersWithoutBase()
	{
		remove_all_actions('wp_redirect');
		remove_all_actions('network_site_url');
		remove_all_actions('site_url');

		update_option('ld_login_base', null);
		$this->object->redo_login_form();

		$this->assertFalse(has_action('wp_redirect'));
		$this->assertFalse(has_action('network_site_url'));
		$this->assertFalse(has_action('site_url'));
	}

	public function testFiltersWithBase()
	{
		remove_all_actions('wp_redirect');
		remove_all_actions('network_site_url');
		remove_all_actions('site_url');
		
		update_option('ld_login_base', 'login');
		$this->object->redo_login_form();

		$this->assertTrue(has_action('wp_redirect'));
		$this->assertTrue(has_action('network_site_url'));
		$this->assertTrue(has_action('site_url'));
	}

	public function testLoginBase()
	{
		update_option('ld_login_base', 'login');
		$this->object->redo_login_form();

		$this->assertEquals('login', $this->object->getLoginBase());
	}
}

/**
 * @ignore
 */
class LdProxyObject extends WP_LockAuth { }
