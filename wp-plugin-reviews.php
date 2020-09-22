<?php
/**
Plugin Name: WP Plugin Reviews
Plugin URI: http://sudarmuthu.com/wordpress/wp-plugin-reviews
Description: Displays the latest reviews of a WordPress Plugin in the sidebar
Author: Sudar
Version: 0.4
Author URI: http://sudarmuthu.com/
Donate Link: http://sudarmuthu.com/if-you-wanna-thank-me
Text Domain: wp-plugin-reviews
Domain Path: languages/

=== RELEASE NOTES ===
2013-02-11 - v0.1 - (Dev Time: 3 hours)
                  - Initial Release
2013-02-16 - v0.2 - (Dev Time: 0.5 hour)
                  - Generated Pot file
2013-04-24 - v0.3 - (Dev Time: 1 hour)
				  - Added documentation
2013-04-26 - v0.4 - (Dev Time: 0.5 hour)
				  - Fixed some typos and added documentation
*/

/*  Copyright 2013  Sudar Muthu  (email : sudar@sudarmuthu.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * The main Plugin class
 *
 * @package WP_Plugin_Reviews
 * @subpackage default
 * @author Sudar
 */
class WP_Plugin_Reviews {

    const CACHE_KEY_SLUG = 'plugin-review-';
    const REVIEW_BASE_URL = 'http://wordpress.org/support/rss/view/plugin-reviews/';

    /**
     * Initalize the plugin by registering the hooks
     */
    function __construct() {

        // Load localization domain
        load_plugin_textdomain( 'wp-plugin-reviews', false, dirname(plugin_basename(__FILE__)) .  '/languages' );

        // Register hooks and filters
        add_filter('plugin-reviews-title', array(&$this, 'filter_title'), 10, 3);
    }

    /**
     * filter title
     */
    function filter_title($title, $plugin) {
        $title = str_replace($title, "[plugin]", $plugin);
        return $title;
    }

    /**
     * Get the reviews of a Plugin
     */
    public function get_plugin_reviews($plugin, $count = 5) {

        $output = '';

        if ($plugin == '' ) {
            return $output;
        }

        $key = self::CACHE_KEY_SLUG . $plugin;

        if (false === ( $output = get_transient( $key ) ) ) {
            require_once(ABSPATH . WPINC . '/feed.php');
            $feed_url = 'https://wordpress.org/support/plugin/' . $plugin . '/reviews/feed/';
            $rss = fetch_feed( $feed_url );

            if (!is_wp_error( $rss ) ) { // Checks that the object is created correctly
                // Figure out how many total items there are, but limit it to 5.
                $maxitems = $rss->get_item_quantity($count);

                // Build an array of all the items, starting with element 0 (first element).
                $rss_items = $rss->get_items(0, $maxitems);

                // TODO: Make it plugable
                $output = '<div class = "plugin-reviews">';
                foreach($rss_items as $rss_item) {

                    $content = trim($rss_item->get_content());
                    if ($content == '' || $content == '<br />') {
                        $title = $rss_item->get_title();
                        if (preg_match('/&quot;([^&]+)&quot;/', $title, $m)) {
                            $content = $m[1];
                        } else {
                            $content = $title;
                        }
                    }

                    $author_name = 'A user';
                    if ($author = $rss_item->get_author()) {
                        $author_name = sanitize_user($author->get_name());
                    }

                    $output .= '<div class = "plugin-review">';
                    $output .= '<blockquote class = "plugin-review-text">' . $content . '</blockquote>';
                    $output .= '<span class = "plugin-review-author" style = "float:right">' . '- <a href = "' . esc_url($rss_item->get_permalink()) . '">' . $author_name . '</a></span><br> ';
                    //TODO: Provide this as an option
                    //$output .= __('on', 'wp-plugin-reviews') . ' ' . $rss_item->get_date('j F Y | g:i a');
                    $output .= '</div>';
                }

                $output .= '</div>';
                set_transient($key, $output, 12 * HOUR_IN_SECONDS);
            }
        }

        return $output;
    }
}

// Start this plugin once all other plugins are fully loaded
add_action( 'init', 'WP_Plugin_Reviews' ); function WP_Plugin_Reviews() { global $wp_plugin_reviews; $wp_plugin_reviews = new WP_Plugin_Reviews(); }

// register WP_Plugin_Review_Widget widget
add_action( 'widgets_init', $callback = function() { return register_widget("WP_Plugin_Review_Widget"); } );

/**
 * WP_Plugin_Review_Widget Class
 *
 * @package WP_Plugin_Reviews
 * @subpackage widget
 * @author Sudar
 */
class WP_Plugin_Review_Widget extends WP_Widget {
    /** constructor */
    function __construct() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'WP_Plugin_Review_Widget', 'description' => __('Reviews of a WordPress Plugin', 'wp-plugin-reviews'));

		/* Widget control settings. */
		$control_ops = array('id_base' => 'wp-plugin-reviews' );

		/* Create the widget. */
		parent::__construct( 'wp-plugin-reviews', __('WP Plugin Reviews', 'wp-plugin-reviews'), $widget_ops, $control_ops );
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {
        extract( $args );

		/* Set up some default widget settings. */
		$defaults = array( 'title' => __('Recent Reviews', 'wp-plugin-reviews'), 'plugin' => '', 'count' => 5);
		$instance = wp_parse_args( (array) $instance, $defaults );

        $title = $instance['title'];
        $plugin = $instance['plugin'];
        $count = absint($instance['count']);

        $title = apply_filters('plugin-reviews-title', $title, $plugin);

        echo $before_widget;
        echo $before_title;
        echo $title;
        echo $after_title;

        echo get_plugin_reviews($plugin, $count);

        echo $after_widget;
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
		$instance = $old_instance;

        // validate data
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['plugin'] = strip_tags($new_instance['plugin']);
        $instance['count'] = absint($new_instance['count']);

        return $instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {

		/* Set up some default widget settings. */
		$defaults = array( 'title' => __('Recent Reviews', 'wp-plugin-reviews'), 'plugin' => '', 'count' => 5);
		$instance = wp_parse_args( (array) $instance, $defaults );

        $title = esc_attr($instance['title']);
		$plugin = $instance['plugin'];
        $count = absint($instance['count']);
?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'wp-plugin-reviews'); ?>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('plugin'); ?>"><?php _e('Plugin Name:', 'wp-plugin-reviews'); ?>
            <input class="widefat" id="<?php echo $this->get_field_id('plugin'); ?>" name="<?php echo $this->get_field_name('plugin'); ?>" type="text" value="<?php echo $plugin; ?>" /></label>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('count'); ?>"><?php _e('No of reviews to show:', 'wp-plugin-reviews'); ?>
            <input class="widefat" id="<?php echo $this->get_field_id('count'); ?>" name="<?php echo $this->get_field_name('count'); ?>" type="text" value="<?php echo $count; ?>" /></label>
        </p>

<?php
    }
} // class WP_Plugin_Review_Widget

/**
 * Template function to display the reviews
 *
 * @param string $plugin
 */
function get_plugin_reviews($plugin, $count = 5) {
    global $wp_plugin_reviews;
    return $wp_plugin_reviews->get_plugin_reviews($plugin, $count);
}
?>
