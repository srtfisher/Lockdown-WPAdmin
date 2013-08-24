<?php
class LockdownTest extends PHPUnit_Framework_TestCase {
	protected function setUp()
	{
		do_action('init');
	}

	/**
	 * Test that the application has added an action to init
	 */
	public function testActionAdded()
	{
		$this->assertTrue(has_action('init'));
	}
}

