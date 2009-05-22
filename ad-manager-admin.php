<?php

class ad_manager_admin
{
	#
	# widget_control()
	#
	
	function widget_control($widget_args)
	{
		global $wp_registered_widgets;
		static $updated = false;

		if ( is_numeric($widget_args) )
			$widget_args = array( 'number' => $widget_args );
		$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
		extract( $widget_args, EXTR_SKIP );

		$options = ad_manager::get_options();
		
		if ( !$updated && !empty($_POST['sidebar']) )
		{
			$sidebar = (string) $_POST['sidebar'];

			$sidebars_widgets = wp_get_sidebars_widgets();
			if ( isset($sidebars_widgets[$sidebar]) )
				$this_sidebar =& $sidebars_widgets[$sidebar];
			else
				$this_sidebar = array();

			foreach ( $this_sidebar as $_widget_id ) {
				if ( array('ad_manager', 'display_widget') == $wp_registered_widgets[$_widget_id]['callback'] && isset($wp_registered_widgets[$_widget_id]['params'][0]['number']) ) {
					$widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
					if ( !in_array( "ad_unit-$widget_number", $_POST['widget-id'] ) ) // the widget has been removed.
						unset($options[$widget_number]);
				}
			}
			
			foreach ( (array) $_POST['ad_unit'] as $widget_number => $widget_ad ) {
				
				$title = strip_tags(stripslashes($widget_ad['title']));
				
				if ( current_user_can('unfiltered_html') )
				{
					$code = stripslashes( $widget_ad['code'] );
				}
				elseif ( isset($options[$widget_number]['code']) )
				{
					$code = $options[$widget_number]['code'];
				}
				else
				{
					$code = '';
				}
				
				$search_engine = isset($widget_ad['search_engine']);
				$old_post = isset($widget_ad['old_post']);
				$casual_visitor = isset($widget_ad['casual_visitor']);
				$php_condition = isset($widget_ad['php_condition']);
				
				if ( current_user_can('unfiltered_html') )
				{
					$php_code = stripslashes( $widget_ad['php_code'] );
					$php_code = str_replace(';', '', $php_code); # someone is trying to hack his way in
					$php_code = str_replace('is_post', 'is_single', $php_code); # frequent mistake
				}
				elseif ( isset($options[$widget_number]['php_code']) )
				{
					$php_code = $options[$widget_number]['php_code'];
				}
				else
				{
					$php_code = '';
				}
				
				$php_condition = $php_condition && $php_code;
				
				$float = stripslashes($widget_ad['float']);
				
				if ( !in_array($float, array('left', 'right')) )
				{
					$float = false;
				}
				
				$options[$widget_number] = compact( 'title', 'code', 'max_units', 'search_engine', 'old_post', 'casual_visitor', 'php_condition', 'php_code', 'float' );
			}

			update_option('ad_manager', $options);
			$updated = true;
		}

		if ( -1 == $number )
		{
			$number = '%i%';
			$options = ad_manager::default_options();
		}
		else
		{
			$options = $options[$number];
		}

		extract($options);
		
		$title = esc_attr($title);
		$code = format_to_edit($code);
		$php_code = format_to_edit($php_code);

		echo '<h3>' . 'Code' . '</h3>' . "\n";
		
		echo '<p>'
			. '<label>'
			. 'Title (never displayed to visitors)' . '<br />'
			. '<input class="widefat" name="ad_unit[' . $number .'][title]" type="text"'
				. ' value="' . $title . '"'
				. ' />'
			. '</label>'
			. '</p>' . "\n";

		echo '<textarea class="widefat code" rows="8" cols="20" name="ad_unit[' .  $number . '][code]"'
				. ( !current_user_can('unfiltered_html')
					? ' disabled="disabled"'
					: ''
					)
				. '>'
			. $code
			. '</textarea>';
			
		echo '<h3>' . 'Context' . '</h3>' . "\n";

		echo '<p>'
			. 'Only display this ad unit when any of these conditions are met:'
			. '</p>' . "\n";

		echo '<p>'
			. '<label>'
			. '<input type="checkbox" name="ad_unit[' . $number .'][search_engine]"'
				. ( $search_engine
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. '&nbsp;'
			. 'The visitor comes from a search engine'
			. '</label>'
			. '</p>' . "\n";
		
		echo '<p>'
			. '<label>'
			. '<input type="checkbox" name="ad_unit[' . $number .'][old_post]"'
				. ( $old_post
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. '&nbsp;'
			. 'The post (not page) is more that 2 weeks old'
			. '</label>'
			. '</p>' . "\n";
		
		echo '<p>'
			. '<label>'
			. '<input type="checkbox" name="ad_unit[' . $number .'][casual_visitor]"'
				. ( $casual_visitor
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. '&nbsp;'
			. 'The visitor is not a regular reader (3 visits in 2 weeks)'
			. '</label>'
			. '</p>' . "\n";
		
		echo '<p>'
			. '<label>'
			. '<input type="checkbox" name="ad_unit[' . $number .'][php_condition]"'
				. ' id="ad_unit__' . $number .'__php_condition"'
				. ( $php_condition
					? ' checked="checked"'
					: ''
					)
				. ' />'
			. '&nbsp;'
			. 'The following php condition (<a href="http://codex.wordpress.org/Conditional_Tags" target="_blank">WordPress conditional tags reference</a>) is met:'
			. '</label>' . '</p>' . "\n";
		
		echo '<table width="95%">' . "\n"
			. '<tr valign="middle" align="center">' . "\n"
			. '<td style="width: 50px;">'
			. '<code>if&nbsp;(</code>'
			. '</td>' . "\n"
			. '<td>'
			. '<textarea class="widefat code" rows="3" cols="20" name="ad_unit[' . $number .'][php_code]"'
				. ' onchange="if ( this.value ) document.getElementById(\'ad_unit__' . $number .'__php_condition\').checked = true; else document.getElementById(\'ad_unit__' . $number .'__php_condition\').checked = false;"'
				. ( !current_user_can('unfiltered_html')
					? ' disabled="disabled"'
					: ''
					)
				. '>'
			. $php_code
			. '</textarea>'
			. '</td>' . "\n"
			. '<td style="width: 25px;">'
			. '<code>)</code>'
			. '</td>' . "\n"
			. '</tr>' . "\n"
			. '</table>' . "\n";

		echo '<h3>' . 'Style' . '</h3>' . "\n";
		
		echo '<p>'
			. '<label>'
			. '<input type="radio" name="ad_unit[' . $number .'][float]" value=""'
				. ( !$float
					? ' checked="checked"'
					: ''
					)
				. '>'
			. '&nbsp;'
			. 'Occupy all of the available width'
			. '</label>'
			. '</p>' . "\n";

		echo '<p>'
			. '<label>'
			. '<input type="radio" name="ad_unit[' . $number .'][float]" value="left"'
				. ( $float == 'left'
					? ' checked="checked"'
					: ''
					)
				. '>'
			. '&nbsp;'
			. 'Float to the left, and wrap text around it'
			. '</label>'
			. '</p>' . "\n";

		echo '<p>'
			. '<label>'
			. '<input type="radio" name="ad_unit[' . $number .'][float]" value="right"'
				. ( $float == 'right'
					? ' checked="checked"'
					: ''
					)
				. '>'
			. '&nbsp;'
			. 'Float to the right, and wrap text around it'
			. '</label>'
			. '</p>' . "\n";
	} # widget_control()
} # ad_manager_admin

?>