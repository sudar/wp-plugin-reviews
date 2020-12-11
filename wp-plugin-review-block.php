<?php
/**
 * Registers all block assets so that they can be enqueued through the block editor
 * in the corresponding context.
 *
 * @see https://developer.wordpress.org/block-editor/tutorials/block-tutorial/applying-styles-with-stylesheets/
 */
class WPPluginReviewBlock {
	/**
	 * Registers review block.
	 */
	public function register() {
		$this->register_block();
	}

	/**
	 * Registers review block and its assets.
	 */
	protected function register_block() {
		$dir = dirname( __FILE__ );

		$script_asset_path = "$dir/build/index.asset.php";
		if ( ! file_exists( $script_asset_path ) ) {
			throw new Error(
				'You need to run `npm start` or `npm run build` for the "sudar/wp-plugin-review-block" block first.'
			);
		}
		$index_js     = 'build/index.js';
		$script_asset = require( $script_asset_path );
		wp_register_script(
			'wp-plugin-review-block-editor',
			plugins_url( $index_js, __FILE__ ),
			$script_asset['dependencies'],
			$script_asset['version']
		);
		wp_set_script_translations( 'wp-plugin-review-block-editor', 'wp-plugin-reviews' );

		$editor_css = 'build/index.css';
		wp_register_style(
			'wp-plugin-review-block-editor',
			plugins_url( $editor_css, __FILE__ ),
			array(),
			filemtime( "$dir/$editor_css" )
		);

		$style_css = 'build/style-index.css';
		wp_register_style(
			'create-block-review-block-block',
			plugins_url( $style_css, __FILE__ ),
			array(),
			filemtime( "$dir/$style_css" )
		);

		register_block_type( 'sudar/wp-plugin-review-block', array(
			'editor_script'   => 'wp-plugin-review-block-editor',
			'editor_style'    => 'wp-plugin-review-block-editor',
			'style'           => 'wp-plugin-review-block',
			'render_callback' => [ $this, 'get_plugin_reviews_render_callback' ]
		) );
	}

	/**
	 * Renders plugin reviews.
	 *
	 * @param array $attributes Values entered by user.
	 * @return array Reviews of specified plugin.
	 */
	public function get_plugin_reviews_render_callback( $attributes ) {
		return get_plugin_reviews( $attributes['pluginName'], absint( $attributes['reviewCount'] ) );
	}

}
