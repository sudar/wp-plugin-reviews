<?php
/**
 * Registers all block assets so that they can be enqueued through the block editor
 * in the corresponding context.
 *
 * @see https://developer.wordpress.org/block-editor/tutorials/block-tutorial/applying-styles-with-stylesheets/
 */
function create_block_review_block_block_init() {
	$dir = dirname( __FILE__ );

	$script_asset_path = "$dir/build/index.asset.php";
	if ( ! file_exists( $script_asset_path ) ) {
		throw new Error(
			'You need to run `npm start` or `npm run build` for the "create-block/review-block" block first.'
		);
	}
	$index_js     = 'build/index.js';
	$script_asset = require( $script_asset_path );
	wp_register_script(
		'create-block-review-block-block-editor',
		plugins_url( $index_js, __FILE__ ),
		$script_asset['dependencies'],
		$script_asset['version']
	);
	wp_set_script_translations( 'create-block-review-block-block-editor', 'review-block' );

	$editor_css = 'build/index.css';
	wp_register_style(
		'create-block-review-block-block-editor',
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

	register_block_type( 'create-block/review-block', array(
		'editor_script'   => 'create-block-review-block-block-editor',
		'editor_style'    => 'create-block-review-block-block-editor',
		'style'           => 'create-block-review-block-block',
		'render_callback' => 'get_plugin_reviews_render_callback'
	) );
}

function get_plugin_reviews_render_callback( $attributes ) {
	return get_plugin_reviews( $attributes['pluginName'] );
}
add_action( 'init', 'create_block_review_block_block_init' );
