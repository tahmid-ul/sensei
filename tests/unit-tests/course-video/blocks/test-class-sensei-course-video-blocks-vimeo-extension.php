<?php

class Test_Sensei_Course_Video_Blocks_Vimeo_Extension extends WP_UnitTestCase {
	/**
	 * Test if only Vimeo video embeds are wrapped in the block.
	 *
	 * @dataProvider provider_wraps_only_vimeo_embeds
	 */
	public function test_wrap_only_vimeo_embed( $iframe, $url, $expected ) {
		$vimeo_extension = Sensei_Course_Video_Blocks_Vimeo_Extension::instance();

		$result = $vimeo_extension->wrap_video( $iframe, $url );

		self::assertSame( $expected, $result );
	}

	public function provider_wraps_only_vimeo_embeds() {
		return array(
			'player.vimeo.com' => array(
				'<iframe src="https://player.vimeo.com/abc"></iframe>',
				'https://player.vimeo.com/abc',
				'<div class=\'sensei-course-video-container vimeo-extension\'><iframe src="https://player.vimeo.com/abc"></iframe></div>',
			),
			'vimeo.com'        => array(
				'<iframe src="https://player.vimeo.com/abc"></iframe>',
				'https://vimeo.com/video-id',
				'<div class=\'sensei-course-video-container vimeo-extension\'><iframe src="https://player.vimeo.com/abc"></iframe></div>',
			),
			'youtube.com'      => array(
				'<iframe src="https://www.youtube.com/embed/video-id"></iframe>',
				'https://www.youtube.com/watch?v=video-id',
				'<iframe src="https://www.youtube.com/embed/video-id"></iframe>',
			),
		);
	}
}
