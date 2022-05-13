<?php

/**
 * Sensei Analysis Unit Tests
 *
 * @covers Sensei_Analysis
 */
class Sensei_Analysis_Test extends WP_UnitTestCase {
	private static $initial_hook_suffix;

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		self::$initial_hook_suffix = $GLOBALS['hook_suffix'] ?? null;
		$GLOBALS['hook_suffix']    = null;
	}

	public static function tearDownAfterClass() {
		parent::tearDownAfterClass();
		$GLOBALS['hook_suffix'] = self::$initial_hook_suffix;
	}

	/**
	 * Test load_data_object returns an expected list table instance
	 *
	 * @param string $name
	 * @param string $data
	 * @param string $expected_class
	 *
	 * @dataProvider providerLoadDataObject_ParamsGiven_ReturnsExpectedInstance
	 */
	public function testLoadDataObject_ParamsGiven_ReturnsExpectedInstance( $name, $data, $expected_class ) {
		$analysis    = new Sensei_Analysis( 'a' );
		$data_object = $analysis->load_data_object( $name, $data );
		$this->assertInstanceOf( $expected_class, $data_object );
	}

	public function providerLoadDataObject_ParamsGiven_ReturnsExpectedInstance(): array {
		return [
			'overview courses' => [
				'Overview',
				'courses',
				'Sensei_Reports_Overview_List_Table_Courses',
			],
			'user profile'     => [
				'User_Profile',
				1,
				'Sensei_Analysis_User_Profile_List_Table',
			],
			'course'           => [
				'Course',
				2,
				'Sensei_Analysis_Course_List_Table',
			],
			'lesson'           => [
				'Lesson',
				3,
				'Sensei_Analysis_Lesson_List_Table',
			],
		];
	}
}