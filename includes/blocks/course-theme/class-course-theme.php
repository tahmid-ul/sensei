<?php
/**
 * File containing the class Course_Theme.
 *
 * @package sensei
 */

namespace Sensei\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Sensei_Blocks_Initializer;
use \Sensei_Course_Theme_Option;
use \Sensei\Blocks\Course_Theme as Blocks;

/**
 * Class Course_Theme
 */
class Course_Theme extends Sensei_Blocks_Initializer {
	/**
	 * Course_Theme constructor.
	 */
	public function __construct() {
		parent::__construct( [ 'lesson', 'course', 'quiz' ] );
	}

	/**
	 * Enqueue frontend and editor assets.
	 *
	 * @access private
	 */
	public function enqueue_block_assets() {
	}

	/**
	 * Enqueue editor assets.
	 *
	 * @access private
	 */
	public function enqueue_block_editor_assets() {
	}

	/**
	 * Check if it should initialize the blocks.
	 */
	protected function should_initialize_blocks() {
		return Sensei_Course_Theme_Option::instance()->should_use_sensei_theme();
	}

	/**
	 * Initializes the blocks.
	 */
	public function initialize_blocks() {
		new Blocks\Course_Title();
		new Blocks\Site_Logo();
		new Blocks\Notices();
		new Blocks\Focus_Mode();
		new Blocks\Post_Title();
		new Blocks\Lesson_Module();
		new Blocks\Course_Content();
		new Blocks\Prev_Lesson();
		new Blocks\Next_Lesson();
		new Blocks\Prev_Next_Lesson();
		new Blocks\Course_Progress_Counter();
		new Blocks\Course_Progress_Bar();
		new Blocks\Lesson_Actions();
		new Blocks\Quiz_Back_To_Lesson();
	}
}