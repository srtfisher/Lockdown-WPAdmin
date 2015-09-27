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
			$this->invokeMethod( $this->object->application, 'matchUserToArray', array($users, 'admin', 'password'))
		);

		// Both should fail
		$this->assertFalse(
			$this->invokeMethod( $this->object->application, 'matchUserToArray', array($users, 'admin', 'notpassword'))
		);

		$this->assertFalse(
			$this->invokeMethod( $this->object->application, 'matchUserToArray', array(array(), 'admin', 'notpassword'))
		);
	}

	public function testIsHidingAdmin() {
		update_option( 'ld_hide_wp_admin', '0' );
		$this->assertFalse( $this->object->application->is_hiding_admin() );

		update_option( 'ld_hide_wp_admin', '1' );
		$this->assertTrue( $this->object->application->is_hiding_admin() );

		update_option( 'ld_hide_wp_admin', 'yep' );
		$this->assertTrue( $this->object->application->is_hiding_admin() );
	}

	public function testAddPrivateUser() {
		$this->object->application->addPrivateUser( 'add', 'user' );
		$this->assertTrue( $this->object->application->doesUsernameExist( 'add' ) );
	}

	/**
	 * @expectedException Exception
	 */
	public function testAddExistingPrivateUser() {
		$this->object->application->addPrivateUser( 'double', 'user' );
		$this->object->application->addPrivateUser( 'double', 'user' );
	}

	/**
	 * Test if a username exists
	 */
	public function testDoesUsernameExist() {
		$this->invokeMethod( $this->object->application, 'setPrivateUsers', array( array() ) );
		$this->assertFalse( $this->object->application->doesUsernameExist( 'username' ) );

		$this->invokeMethod( $this->object->application, 'setPrivateUsers', array( array( array( 'user' => 'username', 'pass' => '123' ) ) ) );
		$this->assertTrue( $this->object->application->doesUsernameExist( 'username' ) );
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
