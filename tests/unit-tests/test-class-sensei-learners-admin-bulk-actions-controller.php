<?php

require_once SENSEI_TEST_FRAMEWORK_DIR . '/trait-sensei-course-enrolment-test-helpers.php';

/**
 * Tests for Sensei_Learners_Admin_Bulk_Actions_Controller class.
 *
 * @group bulk-actions
 */
class Sensei_Learners_Admin_Bulk_Actions_Controller_Test extends WP_UnitTestCase {
	use Sensei_Course_Enrolment_Test_Helpers;
	use Sensei_Test_Login_Helpers;

	private $controller;

	public function setUp() {
		parent::setUp();

		$this->factory = new Sensei_Factory();
		self::resetEnrolmentProviders();

		$this->controller = $this->getMockBuilder( Sensei_Learners_Admin_Bulk_Actions_Controller::class )
			->setConstructorArgs( [ new Sensei_Learner_Management( '' ), Sensei_Learner::instance() ] )
			->setMethods( [ 'check_nonce', 'is_current_page', 'redirect_to_learner_admin_index' ] )
			->getMock();

		$this->controller->method( 'is_current_page' )->willReturn( true );
	}

	/**
	 * Clean up after all tests.
	 */
	public static function tearDownAfterClass() {
		parent::tearDownAfterClass();
		self::resetEnrolmentProviders();
	}

	/**
	 * Tests that the user gets redirected when the action is invalid.
	 */
	public function testUserIsRedirectedWhenActionIsWrong() {
		$this->login_as_teacher();

		$users   = $this->factory->user->create_many( 2 );
		$courses = $this->factory->course->create_many( 2 );

		$_POST['sensei_bulk_action']     = 'random_action';
		$_POST['bulk_action_course_ids'] = implode( ',', $courses );
		$_POST['bulk_action_user_ids']   = implode( ',', $users );

		$this->controller->expects( $this->once() )
			->method( 'redirect_to_learner_admin_index' )
			->with( $this->equalTo( 'error-invalid-action' ) );

		$this->controller->handle_http_post();
	}

	/**
	 * Tests that the user gets redirected when they don't have access to the course.
	 */
	public function testUserIsRedirectedWhenNotTheirCourse() {
		$this->login_as_teacher();

		$users   = $this->factory->user->create_many( 2 );
		$courses = $this->factory->course->create_many( 2 );

		$this->login_as_teacher_b();

		$_POST['sensei_bulk_action']     = Sensei_Learners_Admin_Bulk_Actions_Controller::ENROL_RESTORE_ENROLMENT;
		$_POST['bulk_action_course_ids'] = implode( ',', $courses );
		$_POST['bulk_action_user_ids']   = implode( ',', $users );

		$this->controller->expects( $this->once() )
							->method( 'redirect_to_learner_admin_index' )
							->with( $this->equalTo( 'error-invalid-course' ) );

		$this->controller->handle_http_post();
	}

	/**
	 * Tests that the user gets redirected when the course does not exist.
	 */
	public function testUserIsRedirectedWhenCourseIsWrong() {
		$this->login_as_teacher();

		$user   = $this->factory->user->create();
		$course = $this->factory->course->create();

		$_POST['sensei_bulk_action']     = Sensei_Learners_Admin_Bulk_Actions_Controller::ENROL_RESTORE_ENROLMENT;
		$_POST['bulk_action_course_ids'] = $course + 1;
		$_POST['bulk_action_user_ids']   = $user;

		$this->controller->expects( $this->once() )
			->method( 'redirect_to_learner_admin_index' )
			->with( $this->equalTo( 'error-invalid-course' ) );

		$this->controller->handle_http_post();
	}

	/**
	 * Tests that the user gets enrolled when the action is ENROL_RESTORE_ENROLMENT and the user is not already manually enroled.
	 */
	public function testUsersAreEnroledWhenActionIsManualEnrol() {
		$this->login_as_teacher();

		$users   = $this->factory->user->create_many( 2 );
		$courses = $this->factory->course->create_many( 2 );

		$_POST['sensei_bulk_action']     = Sensei_Learners_Admin_Bulk_Actions_Controller::ENROL_RESTORE_ENROLMENT;
		$_POST['bulk_action_course_ids'] = implode( ',', $courses );
		$_POST['bulk_action_user_ids']   = implode( ',', $users );

		$mock_provider = $this->getMockBuilder( Sensei_Course_Manual_Enrolment_Provider::class )
			->disableOriginalConstructor()
			->setMethods( [ 'enrol_learner', 'is_enrolled' ] )
			->getMock();

		$mock_provider->method( 'is_enrolled' )->willReturn( true, true, false, false );
		$this->registerMockProvider( $mock_provider );

		$mock_provider->expects( $this->exactly( 2 ) )
			->method( 'enrol_learner' )
			->withConsecutive(
				[ $users[1], $courses[0] ],
				[ $users[1], $courses[1] ]
			);

		$this->controller->handle_http_post();
	}

	/**
	 * Tests that the enrolment is restored when the action is ENROL_RESTORE_ENROLMENT and the user is
	 * enroled by any provider.
	 */
	public function testRestoreProviderEnrolments() {
		$this->login_as_teacher();

		$users   = $this->factory->user->create_many( 2 );
		$courses = $this->factory->course->create_many( 2 );

		$_POST['sensei_bulk_action']     = Sensei_Learners_Admin_Bulk_Actions_Controller::ENROL_RESTORE_ENROLMENT;
		$_POST['bulk_action_course_ids'] = implode( ',', $courses );
		$_POST['bulk_action_user_ids']   = implode( ',', $users );

		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Always_Provides::class );
		$this->prepareEnrolmentManager();

		foreach ( $courses as $course_id ) {
			$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
			foreach ( $users as $user_id ) {
				$this->assertTrue( $course_enrolment->is_enrolled( $user_id ) );
				$course_enrolment->remove_learner( $user_id );
				$this->assertFalse( $course_enrolment->is_enrolled( $user_id ) );
			}
		}

		$this->controller->handle_http_post();

		foreach ( $courses as $course_id ) {
			$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
			foreach ( $users as $user_id ) {
				$this->assertTrue( $course_enrolment->is_enrolled( $user_id ) );
			}
		}
	}

	/**
	 * Tests that the manual enrolment is called to be removed when the action is REMOVE_ENROLMENT].
	 */
	public function testUsersAreUnEnroledWhenActionIsRemoveManualEnrolment() {
		$this->login_as_teacher();

		$users   = $this->factory->user->create_many( 2 );
		$courses = $this->factory->course->create_many( 2 );

		$_POST['sensei_bulk_action']     = Sensei_Learners_Admin_Bulk_Actions_Controller::REMOVE_ENROLMENT;
		$_POST['bulk_action_course_ids'] = implode( ',', $courses );
		$_POST['bulk_action_user_ids']   = implode( ',', $users );

		$this->mockCourseEnrolmentIsEnrolled( $courses[0], false );
		$this->mockCourseEnrolmentIsEnrolled( $courses[1], false );

		$mock_provider = $this->getMockBuilder( Sensei_Course_Manual_Enrolment_Provider::class )
			->disableOriginalConstructor()
			->setMethods( [ 'withdraw_learner' ] )
			->getMock();

		$this->registerMockProvider( $mock_provider );

		$mock_provider->expects( $this->exactly( 4 ) )
			->method( 'withdraw_learner' )
			->withConsecutive(
				[ $users[0], $courses[0] ],
				[ $users[0], $courses[1] ],
				[ $users[1], $courses[0] ],
				[ $users[1], $courses[1] ]
			);

		$this->controller->handle_http_post();
	}

	/**
	 * Tests that the enrolment is removed when the action is REMOVE_ENROLMENT and the user is
	 * enroled by any provider.
	 */
	public function testRemoveProviderEnrolments() {
		$this->login_as_teacher();

		$users   = $this->factory->user->create_many( 2 );
		$courses = $this->factory->course->create_many( 2 );

		$_POST['sensei_bulk_action']     = Sensei_Learners_Admin_Bulk_Actions_Controller::REMOVE_ENROLMENT;
		$_POST['bulk_action_course_ids'] = implode( ',', $courses );
		$_POST['bulk_action_user_ids']   = implode( ',', $users );

		$this->addEnrolmentProvider( Sensei_Test_Enrolment_Provider_Always_Provides::class );
		$this->prepareEnrolmentManager();

		foreach ( $courses as $course_id ) {
			$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
			foreach ( $users as $user_id ) {
				$this->assertTrue( $course_enrolment->is_enrolled( $user_id ) );
			}
		}

		$this->controller->handle_http_post();

		foreach ( $courses as $course_id ) {
			$course_enrolment = Sensei_Course_Enrolment::get_course_instance( $course_id );
			foreach ( $users as $user_id ) {
				$this->assertFalse( $course_enrolment->is_enrolled( $user_id ) );
			}
		}
	}

	/**
	 * Tests that the progress is removed from the course when the action is REMOVE_PROGRESS and the user has progress.
	 */
	public function testUsersAreRemovedFromCourse() {
		$this->login_as_teacher();

		$users  = $this->factory->user->create_many( 2 );
		$course = $this->factory->course->create();

		$_POST['sensei_bulk_action']     = Sensei_Learners_Admin_Bulk_Actions_Controller::REMOVE_PROGRESS;
		$_POST['bulk_action_course_ids'] = $course;
		$_POST['bulk_action_user_ids']   = implode( ',', $users );

		add_filter(
			'sensei_user_started_course',
			function( $user_started_course, $course_id, $user_id ) use ( $users ) {
				return $user_id === $users[0];
			},
			10,
			3
		);

		$users_removed = [];
		add_action(
			'sensei_user_course_reset',
			function( $user_id, $course_id ) use ( &$users_removed ) {
				$users_removed[] = $user_id;
			},
			10,
			2
		);

		$this->controller->handle_http_post();

		$this->assertEquals( [ $users[0] ], $users_removed, 'The user which started the course should be removed.' );
	}

	/**
	 * Helper method to register a mock provider.
	 *
	 * @param Sensei_Course_Enrolment_Provider_Interface $mock_provider The provider
	 *
	 * @throws ReflectionException
	 */
	private function registerMockProvider( $mock_provider ) {
		$property = new ReflectionProperty( 'Sensei_Course_Enrolment_Manager', 'enrolment_providers' );
		$property->setAccessible( true );
		$property->setValue( Sensei_Course_Enrolment_Manager::instance(), [ $mock_provider->get_id() => $mock_provider ] );
	}

	/**
	 * Helper method to mock is_enrolled method in the Sensei_Course_Enrolment class.
	 *
	 * @param int  $course_id   Course ID.
	 * @param bool $is_enrolled Value that is_enrolled method will return.
	 *
	 * @throws ReflectionException
	 */
	private function mockCourseEnrolmentIsEnrolled( $course_id, $is_enrolled ) {
		$mock = $this->getMockBuilder( Sensei_Course_Enrolment::class )
			->disableOriginalConstructor()
			->setMethods( [ 'is_enrolled' ] )
			->getMock();

		$mock->method( 'is_enrolled' )->willReturn( $is_enrolled );

		$instances_property = new ReflectionProperty( 'Sensei_Course_Enrolment', 'instances' );
		$instances_property->setAccessible( true );

		$instances               = $instances_property->getValue();
		$instances[ $course_id ] = $mock;
		$instances_property->setValue( $instances );

		$course_id_property = new ReflectionProperty( 'Sensei_Course_Enrolment', 'course_id' );
		$course_id_property->setAccessible( true );
		$course_id_property->setValue( $mock, $course_id );
	}
}
