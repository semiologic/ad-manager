<?php
/*
Plugin Name: Ad Manager
Plugin URI: http://www.semiologic.com/software/ad-manager/
Description: A widget-based ad unit manager. Combine with inline widgets and widget contexts to get the most of it.
Version: 2.0 RC2
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
Text Domain: ad-manager
Domain Path: /lang
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/


load_plugin_textdomain('ad-manager', false, dirname(plugin_basename(__FILE__)) . '/lang');


/**
 * ad_manager
 *
 * @package Ad Manager
 **/

add_action('widgets_init', array('ad_manager', 'widgets_init'));

if ( !is_admin() ) {
	add_action('wp', array('ad_manager', 'set_cookie'));
} else {
	add_action('admin_print_styles-widgets.php', array('ad_manager', 'admin_styles'));
}

class ad_manager extends WP_Widget {
	/**
	 * init()
	 *
	 * @return void
	 **/

	function init() {
		if ( get_option('widget_ad_unit') === false ) {
			foreach ( array(
				'ad_manager' => 'upgrade',
				) as $ops => $method ) {
				if ( get_option($ops) !== false ) {
					$this->alt_option_name = $ops;
					add_filter('option_' . $ops, array(get_class($this), $method));
					break;
				}
			}
		}
	} # init()
	
	
	/**
	 * set_cookie()
	 *
	 * @return void
	 **/

	function set_cookie() {
		if ( is_feed() || is_404() || current_user_can('unfiltered_html') || is_preview() || defined('WP_CACHE') )
			return;
		
		if ( !isset($_COOKIE['am_visit_counted_' . COOKIEHASH]) ) {
			return;
			
			# count visit (and expire in two weeks)
			setcookie(
				'am_visits_' . COOKIEHASH,
				$_COOKIE['am_visits_' . COOKIEHASH]++,
				time() + 14 * 86400,
				COOKIEPATH,
				COOKIE_DOMAIN
				);
			
			setcookie(
				'am_visit_counted_' . COOKIEHASH,
				1,
				null,
				COOKIEPATH,
				COOKIE_DOMAIN
				);
		}
	} # set_cookie()
	
	
	/**
	 * widgets_init()
	 *
	 * @return void
	 **/

	function widgets_init() {
		register_widget('ad_manager');
	} # widgets_init()
	
	
	/**
	 * ad_manager()
	 *
	 * @return void
	 **/

	function ad_manager() {
		$widget_ops = array(
			'classname' => 'ad_unit',
			'description' => __('An ad unit. Combine with Inline Widgets for use in posts.', 'ad-manager'),
			);
		$control_ops = array(
			'width' => 430,
			);
		
		$this->init();
		$this->WP_Widget('ad_unit', __('Ad Widget', 'ad-manager'), $widget_ops, $control_ops);
	} # ad_manager()
	
	
	/**
	 * widget()
	 *
	 * @param array $args
	 * @param array $instance
	 * @return void
	 **/

	function widget($args, $instance) {
		extract($args, EXTR_SKIP);
		$instance = wp_parse_args($instance, ad_manager::defaults());
		extract($instance, EXTR_SKIP);
		
		if ( is_admin() ) {
			echo $before_widget
				. ( $title
					? ( $before_title . $title . $after_title )
					: ''
					)
				. $after_widget;
			return;
		} elseif ( !$code || is_404() || is_feed() )
			return;
		
		# check preconditions from the least to most expensive in CPU cycles
		
		# precondition: irregular visitor
		
		if ( $casual_visitor ) {
			if ( !defined('WP_CACHE') && $_COOKIE['am_visits_' . COOKIEHASH] > 3 )
				return;
		}
		
		# precondition: old post
		
		if ( $old_post && !is_page() ) {
			if ( in_the_loop() ) {
				global $post;
			} elseif ( is_single() ) {
				global $wp_the_query;
				$post = $wp_the_query->get_queried_object();
			} else {
				$post = false;
			}
			
			if ( $post ) {
				$post_date = mysql2date('U', $post->post_date_gmt);
				$two_weeks_ago = gmdate('U') - 14 * 86400;
				if ( $post_date > $two_weeks_ago )
					return;
			}
		}

		# precondition: search engine
		
		if ( $search_engine && !defined('WP_CACHE') && isset($_SERVER['HTTP_REFERER'])
			&& strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) === false ) {
			if ( preg_match("/^
					https?:\/\/
					[^\/]*
					(?:					# an easy to spot search engine
						\b
						(?:
							google
						|
							yahoo
						|
							search
						|
							soso
						)
						\b
					|					# a frequently used pattern: site.com slash search
						\/
						search
						\b
					|
						.+
						(?:[?&])		# a frequently used parameter
						(?:s|q)
						=
					)
				/ix", $_SERVER['HTTP_REFERER'])
				)
				return;
		}
		
		# precondition: php
		
		if ( $php_condition ) {
		#	dump($php_code);
			eval("\$bool = ( $php_code );");
			
			if ( !$bool )
				return;
		}
		
		# precondition: AUP
		
		# https://www.google.com/adsense/support/bin/answer.py?answer=48182#pla
		static $adsense_units = 0;
		static $adsense_images = 0;
		static $adsense_links = 0;
		static $adsense_referrals = 0;
		static $google_site_searches = 0;
		
		# https://publisher.yahoo.com/legal/prog_policy.php
		static $ypn_units = 0;
		
		if ( strpos($code, '<!-- SiteSearch Google -->') !== false ) {
			if ( ++$google_site_searches > 2 )
				return;
		} elseif ( strpos($code, 'google_ad_client') !== false ) {
			# adsense referral: google_ad_format = "ref_text";
			# adsense link: google_ad_format = "120x90_0ads_al_s";
			
			if ( preg_match("/
				google_ad_format
				\s*
				=
				\s*
				([\"'])(.*?)\\1
				/ix", $code, $match)
				) {
				$type = strtolower(end($match));

				if ( $type == 'ref_text' ) {
					if ( ++$adsense_referrals > 3 )
						return;
				} elseif ( strpos($type, 'ads_al') !== false ) {
					if ( ++$adsense_links > 3 )
						return;
				} else {
					if ( ++$adsense_units > 3 )
						return;
				}
			}
		} elseif ( strpos($code, 'ctxt_ad_partner') !== false ) {
			if ( ++$ypn_units > 3 )
				return;
		}
		
		# editors see place holders so they don't click on their own ads, as do authors on drafts
		
		if ( current_user_can('unfiltered_html') || is_preview() ) {
			$code = '<div style="color: Black; background: GhostWhite; border: dotted 1px SteelBlue; padding: 20px;">' . "\n"
				. sprintf(__('Please log out to see this ad unit (<code>%s</code>).', 'ad-manager'), $title)
				. '</div>' . "\n";
		}
		
		# apply style preferences

		if ( $float && in_array($id, array('inline_widgets', 'the_entry')) ) {
			$code = '<div style="'
				. ( $float == 'left'
					? 'float: left; margin: 0px .5em .1em 0px;'
					: 'float: right; margin: 0px 0px .1em .5em;'
					)
				. '">' . "\n"
				. $code
				. '</div>' . "\n";
		}
		
		echo $before_widget
			. $code
			. $after_widget;
	} # widget()
	
	
	/**
	 * update()
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array $instance
	 **/

	function update($new_instance, $old_instance) {
		$instance = array();
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['code'] = current_user_can('unfiltered_html')
			? $new_instance['code']
			: $old_instance['code'];
		foreach ( array('search_engine', 'old_post', 'casual_visitor', 'php_condition') as $var )
			$instance[$var] = isset($new_instance[$var]);
		$instance['php_code'] = current_user_can('unfiltered_html')
			? $new_instance['php_code']
			: $old_instance['php_code'];
		$instance['float'] = in_array($new_instance['float'], array('left', 'right'))
			? $new_instance['float']
			: false;
		
		if ( $instance['php_condition'] && !trim($instance['php_code']) )
			$instance['php_condition'] = false;
		
		return $instance;
	} # update()
	
	
	/**
	 * form()
	 *
	 * @param array $instance
	 * @return void
	 **/

	function form($instance) {
		$instance = wp_parse_args($instance, ad_manager::defaults());
		extract($instance, EXTR_SKIP);
	
		echo '<p>'
			. '<label>'
			. __('Title (never displayed to visitors)', 'ad-manager') . '<br />'
			. '<input type="text" class="widefat"'
				. ' id="' . $this->get_field_id('title') . '"'
				. ' name="' . $this->get_field_name('title') . '"'
				. ' value="' . esc_attr($title) . '"'
				. ' />'
			. '</label>'
			. '</p>' . "\n";
		
		echo '<textarea class="widefat code" rows="8" cols="20"'
				. ' name="' . $this->get_field_name('code') . '"'
				. ( !current_user_can('unfiltered_html')
					? ' disabled="disabled"'
					: ''
					)
				. '>'
			. esc_html($code)
			. '</textarea>' . "\n";
		
		echo '<h3>' . __('Ad Unit Context', 'ad-manager') . '</h3>' . "\n";
		
		echo '<p>'
			. __('Only display this ad unit when any of these conditions are met:', 'ad-manager')
			. '</p>' . "\n";
		
		echo '<p>'
			. '<label>'
			. '<input type="checkbox" name="' . $this->get_field_name('search_engine') . '"'
				. ( $search_engine
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. '&nbsp;'
			. __('The visitor comes from a search engine', 'ad-manager')
			. '</label>'
			. '</p>' . "\n";
		
		echo '<p>'
			. '<label>'
			. '<input type="checkbox" name="' . $this->get_field_name('old_post') . '"'
				. ( $old_post
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. '&nbsp;'
			. __('The post (not page) is more that 2 weeks old', 'ad-manager')
			. '</label>'
			. '</p>' . "\n";
		
		echo '<p>'
			. '<label>'
			. '<input type="checkbox" name="' . $this->get_field_name('casual_visitor') . '"'
				. ( $casual_visitor
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. '&nbsp;'
			. __('The visitor is not a regular reader (three visits in the past two weeks)', 'ad-manager')
			. '</label>'
			. '</p>' . "\n";
		
		echo '<p>'
			. '<label>'
			. '<input type="checkbox" name="' . $this->get_field_name('php_condition') . '"'
				. ' id="' . $this->get_field_id('php_condition') . '"'
				. ( $php_condition
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. '&nbsp;'
			. __('The following php condition (<a href="http://codex.wordpress.org/Conditional_Tags" target="_blank">WordPress conditional tags</a>) is met:', 'ad-manager')
			. '</label>' . '</p>' . "\n";
		
		echo '<textarea class="widefat code" rows="3" cols="20" name="ad_unit[' . $number .'][php_code]"'
				. ' onchange="if ( this.value ) document.getElementById(\'' . $this->get_field_id('php_condition') . '\').checked = true; else document.getElementById(\'' . $this->get_field_id('php_condition') . '\').checked = false;"'
				. ( !current_user_can('unfiltered_html')
					? ' disabled="disabled"'
					: ''
					)
				. '>'
			. esc_html($php_code)
			. '</textarea>' . "\n";
		
		echo '<div class="ad_manager_style">' . "\n";
		
		echo '<h3>' . __('Ad Unit Style', 'ad-manager') . '</h3>' . "\n";
		
		echo '<p>'
			. '<label>'
			. '<input type="radio" name="' . $this->get_field_name('float') . '" value=""'
				. checked($float, '', false)
				. ' />'
			. '&nbsp;'
			. __('Occupy the full available width', 'ad-manager')
			. '</label>'
			. '<br />' . "\n"
			. '<label>'
			. '<input type="radio" name="' . $this->get_field_name('float') . '" value="left"'
				. checked($float, 'left', false)
				. ' />'
			. '&nbsp;'
			. __('Float this ad unit to the left', 'ad-manager')
			. '</label>'
			. '<br />' . "\n"
			. '<label>'
			. '<input type="radio" name="' . $this->get_field_name('float') . '" value="right"'
				. checked($float, 'right', false)
				. ' />'
			. '&nbsp;'
			. __('Float this ad unit to the right', 'ad-manager')
			. '</label>'
			. '</p>' . "\n";
		
		echo '</div>' . "\n";
	} # form()
	
	
	/**
	 * admin_styles()
	 *
	 * @return void
	 **/

	function admin_styles() {
		echo <<<EOS

<style type="text/css">
.ad_manager_style {
	display: none;
}

#inline_widgets .ad_manager_style,
#the_entry .ad_manager_style {
	display: block;
}
</style>

EOS;
	} # admin_styles()
	
	
	/**
	 * defaults()
	 *
	 * @return array $instance
	 **/

	function defaults() {
		return array(
			'title' => __('Ad Unit', 'ad-manager'),
			'code' => __('Enter your ad unit code here', 'ad-manager'),
			'search_engine' => false,
			'old_post' => false,
			'casual_visitor' => false,
			'php_condition' => false,
			'php_code' => '',
			'float' => false,
			);
	} # defaults()
	
	
	/**
	 * upgrade()
	 *
	 * @param array $ops
	 * @return array $ops
	 **/

	function upgrade($ops) {
		$widget_contexts = class_exists('widget_contexts')
			? get_option('widget_contexts')
			: false;
		
		foreach ( $ops as $k => $o ) {
			if ( isset($widget_contexts['ad_unit-' . $k]) ) {
				$ops[$k]['widget_contexts'] = $widget_contexts['ad_unit-' . $k];
			}
		}
		
		return $ops;
	} # upgrade()
} # ad_manager
?>