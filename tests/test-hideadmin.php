<?php
class LockdownAdminTest extends PHPUnit_Framework_TestCase {
	protected $object;

	protected function setUp()
	{
		global $wp_filter;
		$wp_filter = array();
		
		$this->object = ld_setup_auth();
	}
}

/**
 * @ignore
 */
class LdProxyObject extends WP_LockAuth { }
