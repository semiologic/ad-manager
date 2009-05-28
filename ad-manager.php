<?php
/*
Plugin Name: Ad Manager
Plugin URI: http://www.semiologic.com/software/ad-manager/
Description: A widget-based ad unit manager. Combine with inline widgets and widget contexts to get the most of it.
Version: 1.1 alpha
Author: Denis de Bernardy
Author URI: http://www.getsemiologic.com
Text Domain: ad-manager-info
Domain Path: /lang
*/

/*
Terms of use
------------

This software is copyright Mesoconcepts (http://www.mesoconcepts.com), and is distributed under the terms of the Mesoconcepts license. In a nutshell, you may freely use it for any purpose, but may not redistribute it without written permission.

http://www.mesoconcepts.com/license/
**/


class ad_manager
{
	#
	# init()
	#
	
	function init()
	{
		add_action('widgets_init', array('ad_manager', 'widgetize'), 0);
		
		if ( !is_admin() )
		{
			add_action('init', array('ad_manager', 'set_cookie'));
		}
	} # init()
	
	
	#
	# widgetize()
	#
	
	function widgetize()
	{
		$options = ad_manager::get_options();
		
		$widget_options = array('classname' => 'ad_unit', 'description' => __( "A generic ad unit") );
		$control_options = array('width' => 500, 'id_base' => 'ad_unit');
		
		$id = false;

		# registered widgets
		foreach ( array_keys($options) as $o )
		{
			if ( !is_numeric($o) ) continue;
			$id = "ad_unit-$o";

			wp_register_sidebar_widget($id, __('Ad Unit'), array('ad_manager', 'display_widget'), $widget_options, array( 'number' => $o ));
			wp_register_widget_control($id, __('Ad Unit'), array('ad_manager_admin', 'widget_control'), $control_options, array( 'number' => $o ) );
		}
		
		# default widget if none were registered
		if ( !$id )
		{
			$id = "ad_unit-1";
			wp_register_sidebar_widget($id, __('Ad Unit'), array('ad_manager', 'display_widget'), $widget_options, array( 'number' => -1 ));
			wp_register_widget_control($id, __('Ad Unit'), array('ad_manager_admin', 'widget_control'), $control_options, array( 'number' => -1 ) );
		}
	} # widgetize()
	
	
	#
	# set_cookie()
	#
	
	function set_cookie()
	{
		if ( !is_feed() && !current_user_can('unfiltered_html') && !defined('WP_CACHE') )
		{
			if ( !$_COOKIE['am_visit_counted_' . COOKIEHASH] )
			{
				# count visit (and expire in a month)
				setcookie(
					'am_visits_' . COOKIEHASH,
					$_COOKIE['am_visits_' . COOKIEHASH]++,
					time() + 14 * 86400,
					COOKIEPATH,
					COOKIE_DOMAIN
					);
				
				$_COOKIE['am_visit_counted_' . COOKIEHASH] = 1;
				
				setcookie(
					'am_visits_' . COOKIEHASH,
					1,
					null,
					COOKIEPATH,
					COOKIE_DOMAIN
					);
			}
		}
	} # set_cookie()
	
	
	#
	# display_widget()
	#
	
	function display_widget($args, $widget_args = 1)
	{
		extract( $args, EXTR_SKIP );
		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract( $widget_args, EXTR_SKIP );

		$options = ad_manager::get_options();
		
		if ( !isset($options[$number]) )
			return;

		extract($options[$number]);
		
		# admin: just return a title
		
		if ( is_admin() )
		{
			echo $before_widget . $before_title . $title . $after_title . $after_widget;
			return;
		}
		
		# bypass all of this if there's no code at all or if we're on a 404 or in a feed
		
		if ( !$code || is_404() || is_feed() ) return;
		
		# check preconditions from the least to most expensive in CPU cycles
		
		# precondition: irregular visitor
		
		if ( $casual_visitor )
		{
			if ( !defined('WP_CACHE') && $_COOKIE['am_visits_' . COOKIEHASH] > 3 ) return;
		}
		
		# precondition: old post
		
		if ( $old_post )
		{
			if ( is_single() )
			{
				$post = get_post($GLOBALS['wp_the_query']->get_queried_object_id());
			}
			elseif ( in_the_loop() && ( is_home() && !is_front_page() || !is_page() ) )
			{
				$post = get_post(get_the_ID());
			}
			
			if ( $post )
			{
				$post_date = mysql2date('U', $post->post_date_gmt);
				
				$two_weeks_ago = gmdate('U') - 14 * 86400;

				if ( $post_date > $two_weeks_ago ) return;
			}
		}

		# precondition: search engine
		
		if ( $search_engine )
		{
			if ( !defined('WP_CACHE')
				&& strpos($_SERVER['HTTP_REFERER'], $_SERVER['REQUEST_URI']) === false
				&& preg_match("/^
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
						\b				# a frequently used parameter
						(s|q)
						=
					)
				/ix", $_SERVER['HTTP_REFERER'])
				)
			{
				return;
			}
		}
		
		# precondition: php
		
		if ( $php_condition )
		{
		#	dump($php_code);
			eval("\$bool = ( $php_code );");
			
			if ( !$bool ) return;
		}
		
		# make sure it's a valid ad before displaying anything
		
		# https://www.google.com/adsense/support/bin/answer.py?answer=48182#pla
		static $adsense_units = 0;
		static $adsense_images = 0;
		static $adsense_links = 0;
		static $adsense_referrals = 0;
		static $google_site_searches = 0;
		
		# https://publisher.yahoo.com/legal/prog_policy.php
		static $ypn_units = 0;
		
		if ( strpos($code, '<!-- SiteSearch Google -->') !== false )
		{
			if ( ++$google_site_searches > 2 ) return;
		}
		elseif ( strpos($code, 'google_ad_client') !== false )
		{
			# adsense referral: google_ad_format = "ref_text";
			# adsense link: google_ad_format = "120x90_0ads_al_s";
			
			if ( !preg_match("/
				google_ad_format
				\s*
				=
				\s*
				([\"'])(.*)\\1
				/Uix", $code, $match)
				)
			{
			}

			$type = strtolower(end($match));
			
			if ( $type == 'ref_text' )
			{
				if ( ++$adsense_referrals > 3 ) return;
			}
			elseif ( strpos($type, 'ads_al') !== false )
			{
				if ( ++$adsense_links > 3 ) return;
			}
			else
			{
				if ( ++$adsense_units > 3 ) return;
			}
		}
		elseif ( strpos($code, 'ctxt_ad_partner') !== false )
		{
			if ( ++$ypn_units > 3 ) return;
		}
		
		# editors see place holders so they don't click on their own ads, as do authors on drafts
		
		if ( current_user_can('unfiltered_html') || is_preview() )
		{
			$code = '<div style="'
				. 'color: Black; background: GhostWhite; border: dotted 1px SteelBlue; padding: 20px;'
				. '">' . "\n"
				. sprintf('<strong>%s</strong> place holder. To see the "real" ad, either log off, or open this url in a different browser.', $title)
				. '</div>' . "\n";
		}
		
		# apply style preferences
		
		if ( $float )
		{
			$code = '<div style="'
				. ( $float == 'left'
					? 'float: left; margin: 0px .5em .1em 0px;'
					: 'float: right; margin: 0px 0px .1em .5em;'
					)
					. '">' . "\n"
				. $code
				. '</div>' . "\n";
		}
		
		# display
		
		echo $before_widget . "\n"
			. $code
			. $after_widget . "\n";
	} # display_widget()
	
	
	#
	# get_options()
	#
	
	function get_options()
	{
		if ( !( $o = get_option('ad_manager') ) )
		{
			$o = array();
			update_option('ad_manager', $o);
		}
		
		return $o;
	} # get_options()
	
	
	#
	# new_widget()
	#
	
	function new_widget($args = null)
	{
		$o = ad_manager::get_options();
		$k = time();
		$o[$k] = ad_manager::default_options();
		$o[$k] = array_merge($o[$k], (array) $args);
		
		update_option('ad_manager', $o);
		
		return 'ad_unit-' . $k;
	} # new_widget()
	
	
	#
	# default_options()
	#
	
	function default_options()
	{
		return array(
			'title' => 'Ad Unit',
			'code' => 'Enter your ad unit code here',
			'search_engine' => false,
			'old_post' => false,
			'casual_visitor' => false,
			'php_condition' => false,
			'php_code' => '',
			'float' => false,
			);
	} # default_options()
} # ad_manager

ad_manager::init();

if ( is_admin() )
{
	include dirname(__FILE__) . '/ad-manager-admin.php';
}
?>