<?php
class LockdownApplicationTest extends PHPUnit_Framework_TestCase {
	protected $object;

	protected function setUp()
	{
		$this->object = ld_setup_auth();
	}

	public function testMatchUserToArray()
	{
		$users = array(
			array(
				'user' => 'admin',
				'pass' => md5('password')
			),

			array(
				'user' => 'stan',
				'pass' => md5('marsh')
			)
		);

		// Should pass
		$this->assertTrue(
			$this->invokeMethod($this->object->application, 'matchUserToArray', array($users, 'admin', 'password'))
		);

		// Both should fail
		$this->assertFalse(
			$this->invokeMethod($this->object->application, 'matchUserToArray', array($users, 'admin', 'notpassword'))
		);

		$this->assertFalse(
			$this->invokeMethod($this->object->application, 'matchUserToArray', array(array(), 'admin', 'notpassword'))
		);
	}

	public function testNoSettingsPassed()
	{
		$this->invokeMethod($this->object->application, 'setupHttpCheck', array(null));

		$this->assertTrue($this->object->getAuthPassed());
	}

	/**
	 * Call protected/private method of a class.
	 *
	 * @param object &$object    Instantiated object that we will run method on.
	 * @param string $methodName Method name to call
	 * @param array  $parameters Array of parameters to pass into method.
	 *
	 * @return mixed Method return.
	 */
	public function invokeMethod(&$object, $methodName, array $parameters = array())
	{
		$reflection = new \ReflectionClass(get_class($object));
		$method = $reflection->getMethod($methodName);
		$method->setAccessible(true);

		return $method->invokeArgs($object, $parameters);
	}
}